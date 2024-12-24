<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Routing;

use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Framework\Api\Exception\MissingPrivilegeException;
use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\AdminApiTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\AppSystemTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
class ApiRequestContextResolverAppTest extends TestCase
{
    use AdminApiTestBehaviour;
    use AppSystemTestBehaviour;
    use IntegrationTestBehaviour;

    private static string $fixturesPath = __DIR__ . '/../App/Manifest/_fixtures';

    public function testCanReadWithPermission(): void
    {
        $this->loadAppsFromDir(self::$fixturesPath . '/test');

        $browser = $this->createClient();
        $this->authorizeBrowserWithIntegrationByAppName($this->getBrowser(), 'test');

        $browser->request('GET', '/api/product');
        $response = $browser->getResponse();

        static::assertIsString($response->getContent());
        static::assertEquals(200, $response->getStatusCode(), $response->getContent());
    }

    public function testCantReadWithoutPermission(): void
    {
        $this->loadAppsFromDir(self::$fixturesPath . '/test');

        $browser = $this->createClient();
        $this->authorizeBrowserWithIntegrationByAppName($browser, 'test');

        $browser->request('GET', '/api/media');

        static::assertEquals(403, $browser->getResponse()->getStatusCode());
    }

    public function testCantReadWithoutAnyPermission(): void
    {
        $this->loadAppsFromDir(self::$fixturesPath . '/minimal');

        $browser = $this->createClient();
        $this->authorizeBrowserWithIntegrationByAppName($browser, 'minimal');

        $browser->request('GET', '/api/product');

        static::assertEquals(403, $browser->getResponse()->getStatusCode());
    }

    public function testCanNotWriteWithoutPermissions(): void
    {
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $this->loadAppsFromDir(self::$fixturesPath . '/minimal');

        $browser = $this->createClient();
        $this->authorizeBrowserWithIntegrationByAppName($browser, 'minimal');

        $browser->request(
            'POST',
            '/api/product',
            [],
            [],
            [],
            json_encode($this->getProductData($productId, $context), \JSON_THROW_ON_ERROR)
        );
        $response = $browser->getResponse();

        static::assertIsString($response->getContent());
        static::assertEquals(403, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertEquals(MissingPrivilegeException::MISSING_PRIVILEGE_ERROR, $data['errors'][0]['code']);
    }

    public function testCanWriteWithPermissionsSet(): void
    {
        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $this->loadAppsFromDir(self::$fixturesPath . '/test');

        $browser = $this->createClient();
        $this->authorizeBrowserWithIntegrationByAppName($browser, 'test');

        $browser->request(
            'POST',
            '/api/product',
            [],
            [],
            [],
            json_encode($this->getProductData($productId, $context), \JSON_THROW_ON_ERROR)
        );

        static::assertEquals(204, $browser->getResponse()->getStatusCode());

        $product = $productRepository->search(new Criteria(), $context)->getEntities()->get($productId);

        static::assertNotNull($product);
    }

    public function testItCanUpdateAnExistingProduct(): void
    {
        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');
        $productId = Uuid::randomHex();
        $newName = 'i got a new name';
        $context = Context::createDefaultContext();

        $productRepository->create([$this->getProductData($productId, $context)], $context);

        $this->loadAppsFromDir(self::$fixturesPath . '/test');

        $browser = $this->createClient();
        $this->authorizeBrowserWithIntegrationByAppName($browser, 'test');

        $browser->request(
            'PATCH',
            '/api/product/' . $productId,
            [],
            [],
            [],
            json_encode([
                'name' => $newName,
            ], \JSON_THROW_ON_ERROR)
        );

        static::assertEquals(204, $browser->getResponse()->getStatusCode());

        /** @var ProductEntity $product */
        $product = $productRepository->search(new Criteria(), $context)->getEntities()->get($productId);

        static::assertNotNull($product);
        static::assertEquals($newName, $product->getName());
    }

    private function authorizeBrowserWithIntegrationByAppName(KernelBrowser $browser, string $appName): void
    {
        $app = $this->fetchApp($appName);
        if (!$app) {
            throw new \RuntimeException('No app found with name: ' . $appName);
        }

        $accessKey = AccessKeyHelper::generateAccessKey('integration');
        $secret = AccessKeyHelper::generateSecretAccessKey();

        $this->setAccessTokenForIntegration($app->getIntegrationId(), $accessKey, $secret);

        $authPayload = [
            'grant_type' => 'client_credentials',
            'client_id' => $accessKey,
            'client_secret' => $secret,
        ];

        $browser->request('POST', '/api/oauth/token', $authPayload, [], [], json_encode($authPayload, \JSON_THROW_ON_ERROR));

        static::assertIsString($browser->getResponse()->getContent());
        $data = json_decode($browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        if (!\array_key_exists('access_token', $data)) {
            throw new \RuntimeException(
                'No token returned from API: ' . ($data['errors'][0]['detail'] ?? 'unknown error' . print_r($data, true))
            );
        }

        $accessToken = $data['access_token'];
        \assert(\is_string($accessToken));
        $browser->setServerParameter('HTTP_Authorization', \sprintf('Bearer %s', $accessToken));
    }

    /**
     * @return array{id: string, name: string, productNumber: string, stock: int, manufacturer: array<string, string>, price: list<array<string, mixed>>, tax: array<string, string>}
     */
    private function getProductData(string $productId, Context $context): array
    {
        return [
            'id' => $productId,
            'name' => 'created by integration',
            'productNumber' => 'SWC-1000',
            'stock' => 100,
            'manufacturer' => [
                'name' => 'app creator',
            ],
            'price' => [
                [
                    'gross' => 100,
                    'net' => 200,
                    'linked' => false,
                    'currencyId' => $context->getCurrencyId(),
                ],
            ],
            'tax' => [
                'name' => 'luxury',
                'taxRate' => '25',
            ],
        ];
    }

    private function fetchApp(string $appName): ?AppEntity
    {
        /** @var EntityRepository<AppCollection> $appRepository */
        $appRepository = static::getContainer()->get('app.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $appName));

        return $appRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
    }

    private function setAccessTokenForIntegration(string $integrationId, string $accessKey, string $secret): void
    {
        /** @var EntityRepository $integrationRepository */
        $integrationRepository = static::getContainer()->get('integration.repository');

        $integrationRepository->update([
            [
                'id' => $integrationId,
                'accessKey' => $accessKey,
                'secretAccessKey' => $secret,
            ],
        ], Context::createDefaultContext());
    }
}
