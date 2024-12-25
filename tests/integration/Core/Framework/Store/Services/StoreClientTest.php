<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Services;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Services\StoreClient;
use Cicada\Core\Framework\Store\Struct\ExtensionCollection;
use Cicada\Core\Framework\Store\Struct\ExtensionStruct;
use Cicada\Core\Framework\Test\Store\StoreClientBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
#[Package('checkout')]
class StoreClientTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StoreClientBehaviour;

    private StoreClient $storeClient;

    private SystemConfigService $configService;

    private Context $storeContext;

    protected function setUp(): void
    {
        $this->configService = static::getContainer()->get(SystemConfigService::class);
        $this->storeClient = static::getContainer()->get(StoreClient::class);

        $this->setLicenseDomain('cicada-test');

        $this->storeContext = $this->createAdminStoreContext();
    }

    public function testSignPayloadWithAppSecret(): void
    {
        $this->getStoreRequestHandler()->append(new Response(200, [], '{"signature": "signed"}'));

        static::assertEquals('signed', $this->storeClient->signPayloadWithAppSecret('[this can be anything]', 'testApp'));

        $lastRequest = $this->getStoreRequestHandler()->getLastRequest();
        static::assertInstanceOf(RequestInterface::class, $lastRequest);

        static::assertEquals('/swplatform/generatesignature', $lastRequest->getUri()->getPath());

        static::assertEquals([
            'cicadaVersion' => $this->getCicadaVersion(),
            'language' => 'en-GB',
            'domain' => 'cicada-test',
        ], Query::parse($lastRequest->getUri()->getQuery()));

        static::assertEquals([
            'appName' => 'testApp',
            'payload' => '[this can be anything]',
        ], \json_decode($lastRequest->getBody()->getContents(), true, flags: \JSON_THROW_ON_ERROR));
    }

    public function testItUpdatesUserTokenAfterLogin(): void
    {
        $responseBody = \file_get_contents(__DIR__ . '/../_fixtures/responses/login.json');
        static::assertIsString($responseBody);

        $this->getStoreRequestHandler()->append(
            new Response(200, [], $responseBody)
        );

        $this->storeClient->loginWithCicadaId('cicadaId', 'password', $this->storeContext);

        $lastRequest = $this->getStoreRequestHandler()->getLastRequest();
        static::assertInstanceOf(RequestInterface::class, $lastRequest);

        static::assertEquals([
            'cicadaVersion' => $this->getCicadaVersion(),
            'language' => 'en-GB',
            'domain' => 'cicada-test',
        ], Query::parse($lastRequest->getUri()->getQuery()));

        $contextSource = $this->storeContext->getSource();
        static::assertInstanceOf(AdminApiSource::class, $contextSource);

        static::assertEquals([
            'cicadaId' => 'cicadaId',
            'password' => 'password',
            'cicadaUserId' => $contextSource->getUserId(),
        ], \json_decode($lastRequest->getBody()->getContents(), true, flags: \JSON_THROW_ON_ERROR));

        // token from login.json
        static::assertEquals(
            'updated-token',
            $this->getStoreTokenFromContext($this->storeContext)
        );

        // secret from login.json
        static::assertEquals(
            'shop.secret',
            $this->configService->get('core.store.shopSecret')
        );
    }

    public function testItRequestsUpdatesForLoggedInUser(): void
    {
        $pluginList = new ExtensionCollection();
        $pluginList->add((new ExtensionStruct())->assign([
            'name' => 'TestExtension',
            'version' => '1.0.0',
        ]));

        $this->getStoreRequestHandler()->append(new Response(200, [], \json_encode([
            'data' => [],
        ], \JSON_THROW_ON_ERROR)));

        $updateList = $this->storeClient->getExtensionUpdateList($pluginList, $this->storeContext);

        static::assertEquals([], $updateList);

        $lastRequest = $this->getStoreRequestHandler()->getLastRequest();
        static::assertInstanceOf(RequestInterface::class, $lastRequest);

        static::assertEquals(
            $this->getStoreTokenFromContext($this->storeContext),
            $lastRequest->getHeader('X-Cicada-Platform-Token')[0],
        );
    }

    public function testItRequestsUpdateForNotLoggedInUser(): void
    {
        $contextSource = $this->storeContext->getSource();
        static::assertInstanceOf(AdminApiSource::class, $contextSource);

        $this->getUserRepository()->update([
            [
                'id' => $contextSource->getUserId(),
                'storeToken' => null,
            ],
        ], Context::createDefaultContext());

        $pluginList = new ExtensionCollection();
        $pluginList->add((new ExtensionStruct())->assign([
            'name' => 'TestExtension',
            'version' => '1.0.0',
        ]));

        $this->getStoreRequestHandler()->append(new Response(200, [], \json_encode([
            'data' => [
                [
                    'name' => 'TestExtension',
                    'version' => '1.1.0',
                ],
            ],
        ], \JSON_THROW_ON_ERROR)));

        $updateList = $this->storeClient->getExtensionUpdateList($pluginList, $this->storeContext);

        static::assertCount(1, $updateList);
        static::assertEquals('TestExtension', $updateList[0]->getName());
        static::assertEquals('1.1.0', $updateList[0]->getVersion());

        $lastRequest = $this->getStoreRequestHandler()->getLastRequest();
        static::assertInstanceOf(RequestInterface::class, $lastRequest);

        static::assertFalse($lastRequest->hasHeader('X-Cicada-Platform-Token'));
    }

    public function testItReturnsUserInfo(): void
    {
        $userInfo = [
            'name' => 'John Doe',
            'email' => 'john.doe@cicada.com',
            'avatarUrl' => 'https://avatar.cicada.com/john-doe.png',
        ];

        $this->getStoreRequestHandler()->append(new Response(200, [], \json_encode($userInfo, \JSON_THROW_ON_ERROR)));

        $returnedUserInfo = $this->storeClient->userInfo($this->storeContext);

        $lastRequest = $this->getStoreRequestHandler()->getLastRequest();
        static::assertInstanceOf(RequestInterface::class, $lastRequest);

        static::assertEquals('/swplatform/userinfo', $lastRequest->getUri()->getPath());
        static::assertEquals('GET', $lastRequest->getMethod());
        static::assertEquals($userInfo, $returnedUserInfo);
    }

    public function testMissingConnectionBecauseYouAreInGermanCellularInternet(): void
    {
        $this->getStoreRequestHandler()->append(new ConnectException(
            'cURL error 7: Failed to connect to api.cicada.com port 443 after 4102 ms: Network is unreachable (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://api.cicada.com/swplatform/pluginupdates?cicadaVersion=6.4.12.0&language=zh-CN&domain=',
            $this->createMock(RequestInterface::class)
        ));

        $pluginList = new ExtensionCollection();
        $pluginList->add((new ExtensionStruct())->assign([
            'name' => 'TestExtension',
            'version' => '1.0.0',
        ]));

        $returnedUserInfo = $this->storeClient->getExtensionUpdateList($pluginList, $this->storeContext);

        static::assertSame([], $returnedUserInfo);
    }
}
