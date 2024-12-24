<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Administration\Controller;

use Cicada\Administration\Controller\AdminExtensionApiController;
use Cicada\Administration\Controller\Exception\AppByNameNotFoundException;
use Cicada\Core\Framework\App\ActionButton\AppAction;
use Cicada\Core\Framework\App\ActionButton\Executor;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Exception\AppNotFoundException;
use Cicada\Core\Framework\App\Hmac\QuerySigner;
use Cicada\Core\Framework\App\Manifest\Exception\UnallowedHostException;
use Cicada\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class AdminExtensionApiControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const EXISTING_APP_NAME = 'existingappname';
    private const NONE_EXISTING_APP_NAME = 'noneexistingappname';
    private const APP_URI = 'https://app.infra?location-id=settings-item&privileges=%7B%22read%22%3A%5B%22custom_entity_store%22%5D%2C%22create%22%3A%5B%22custom_entity_store%22%5D%2C%22update%22%3A%5B%22custom_entity_store%22%5D%2C%22delete%22%3A%5B%22custom_entity_store%22%5D%7D';

    private Context $context;

    private AdminExtensionApiController $adminExtensionApiController;

    private EntityRepository $appRepository;

    private MockObject&Executor $executor;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->appRepository = $container->get('app.repository');
        $this->executor = $this->createMock(Executor::class);
        $this->context = Context::createDefaultContext();

        $this->adminExtensionApiController = new AdminExtensionApiController(
            $this->executor,
            $container->get(AppPayloadServiceHelper::class),
            $this->appRepository,
            $container->get(QuerySigner::class)
        );
    }

    /**
     * @param list<string> $hosts
     */
    #[DataProvider('providerRunAction')]
    public function testRunAction(string $appName, ?string $targetUrl = null, array $hosts = []): void
    {
        $this->appRepository->create([
            [
                'name' => self::EXISTING_APP_NAME,
                'path' => \sprintf('custom/apps/%s', self::EXISTING_APP_NAME),
                'active' => true,
                'configurable' => false,
                'version' => '0.0.1',
                'label' => 'PHPUnit',
                'appSecret' => 'PHPUnit',
                'integration' => [
                    'label' => 'PHPUnit',
                    'accessKey' => 'foo',
                    'secretAccessKey' => 'bar',
                ],
                'aclRole' => [
                    'name' => self::EXISTING_APP_NAME,
                    'privileges' => [],
                ],
                'allowedHosts' => $hosts,
            ],
        ], $this->context);

        $requestDataBag = new RequestDataBag([
            'appName' => $appName,
            'entity' => 'customer',
            'action' => 'PHPUnit',
            'ids' => [],
        ]);

        if ($targetUrl) {
            $requestDataBag->set('url', $targetUrl);
        }

        if (!($appName === self::EXISTING_APP_NAME)) {
            if (!Feature::isActive('v6.7.0.0')) {
                $this->expectException(AppByNameNotFoundException::class);
                $this->expectExceptionMessage(\sprintf('The provided name %s is invalid and no app could be found.', $appName));
            } else {
                $this->expectException(AppNotFoundException::class);
                $this->expectExceptionMessage(\sprintf('Could not find app with name "%s"', $appName));
            }

            $this->adminExtensionApiController->runAction($requestDataBag, $this->context);

            return;
        }

        if (empty($hosts)) {
            if (!Feature::isActive('v6.7.0.0')) {
                $this->expectException(UnallowedHostException::class);
            } else {
                $this->expectException(AppException::class);
                $this->expectExceptionMessage(\sprintf('The host "%s" you tried to call is not listed in the allowed hosts in the manifest file for app "%s".', $targetUrl, $appName));
            }
        } else {
            $this->executor->expects(static::once())->method('execute')->with(static::callback(static fn (AppAction $action) => $action->getTargetUrl() === $targetUrl))->willReturn(new Response());
        }

        $response = $this->adminExtensionApiController->runAction($requestDataBag, $this->context);
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @return array<array{0: string, 1?: string|null, 2?: list<string>|null}>
     */
    public static function providerRunAction(): array
    {
        return [
            [
                self::NONE_EXISTING_APP_NAME,
            ],
            [
                self::EXISTING_APP_NAME,
                'https://example.com',
            ],
            [
                self::EXISTING_APP_NAME,
                'https://example.com',
                [
                    'example.com',
                ],
            ],
        ];
    }

    #[DataProvider('providerSignUri')]
    public function testSignUri(RequestDataBag $requestDataBag, bool $expectAppNotFoundError): void
    {
        $this->appRepository->create([
            [
                'name' => self::EXISTING_APP_NAME,
                'path' => \sprintf('custom/apps/%s', self::EXISTING_APP_NAME),
                'active' => true,
                'configurable' => false,
                'version' => '0.0.1',
                'label' => 'PHPUnit',
                'appSecret' => 'PHPUnit',
                'integration' => [
                    'label' => 'PHPUnit',
                    'accessKey' => 'foo',
                    'secretAccessKey' => 'bar',
                ],
                'aclRole' => [
                    'name' => self::EXISTING_APP_NAME,
                    'privileges' => [],
                ],
            ],
        ], $this->context);

        if ($expectAppNotFoundError) {
            if (!Feature::isActive('v6.7.0.0')) {
                $this->expectException(AppByNameNotFoundException::class);
            } else {
                $this->expectException(AppNotFoundException::class);
            }
        }

        $response = $this->adminExtensionApiController->signUri($requestDataBag, $this->context);

        if ($expectAppNotFoundError) {
            return;
        }

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        static::assertIsString($content);
        $data = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        static::assertIsArray($data);
        static::assertArrayHasKey('uri', $data);
        static::assertStringStartsWith(self::APP_URI, $data['uri']);
        static::assertStringContainsString('shop-id=', $data['uri']);
        static::assertStringContainsString('shop-url=', $data['uri']);
        static::assertStringContainsString('timestamp=', $data['uri']);
        static::assertStringContainsString('sw-version=', $data['uri']);
        static::assertStringContainsString('sw-context-language=', $data['uri']);
        static::assertStringContainsString('sw-user-language=', $data['uri']);
        static::assertStringContainsString('cicada-shop-signature=', $data['uri']);
    }

    /**
     * @return array<array{0: RequestDataBag, 1:bool}>
     */
    public static function providerSignUri(): array
    {
        return [
            [
                new RequestDataBag([
                    'appName' => self::NONE_EXISTING_APP_NAME,
                    'uri' => self::APP_URI,
                ]),
                true,
            ],
            [
                new RequestDataBag([
                    'appName' => self::EXISTING_APP_NAME,
                    'uri' => self::APP_URI,
                ]),
                false,
            ],
        ];
    }
}
