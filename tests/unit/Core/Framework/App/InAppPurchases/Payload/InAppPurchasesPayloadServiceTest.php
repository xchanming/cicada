<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\InAppPurchases\Payload;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Api\Serializer\JsonEntityEncoder;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Exception\AppRegistrationException;
use Cicada\Core\Framework\App\InAppPurchases\Payload\InAppPurchasesPayload;
use Cicada\Core\Framework\App\InAppPurchases\Payload\InAppPurchasesPayloadService;
use Cicada\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Cicada\Core\Framework\App\Payload\AppPayloadStruct;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\Store\StaticInAppPurchaseFactory;
use Cicada\Core\Test\Stub\Framework\IdsCollection;

/**
 * @internal
 */
#[CoversClass(InAppPurchasesPayloadService::class)]
#[Package('checkout')]
class InAppPurchasesPayloadServiceTest extends TestCase
{
    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
    }

    public function testRequest(): void
    {
        $shopIdProvider = $this->createMock(ShopIdProvider::class);
        $shopIdProvider
            ->method('getShopId')
            ->willReturn($this->ids->get('shop-id'));

        $appPayloadServiceHelper = $this->createMock(AppPayloadServiceHelper::class);
        $appPayloadServiceHelper->expects(static::once())
            ->method('createRequestOptions')
            ->willReturn(new AppPayloadStruct([
                'app_request_context' => Context::createDefaultContext(),
                'request_type' => [
                    'app_secret' => 'very-secret',
                    'validated_response' => true,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => '{"purchases":["purchase-1","purchase-2"]}',
            ]));

        $context = Context::createDefaultContext();
        $responseContent = \json_encode([
            'purchases' => [
                'purchase-1',
                'purchase-2',
            ],
        ], \JSON_THROW_ON_ERROR);

        static::assertNotFalse($responseContent);

        $filterPayloadService = new InAppPurchasesPayloadService(
            $appPayloadServiceHelper,
            new Client(['handler' => new MockHandler([new Response(200, [], $responseContent)])]),
        );

        $url = 'https://example.com/filter-mah-features';

        $app = new AppEntity();
        $app->setName('TestApp');
        $app->setId($this->ids->get('app'));
        $app->setVersion('6.6-dev');
        $app->setAppSecret('very-secret');
        $app->setInAppPurchasesGatewayUrl($url);

        $payload = new InAppPurchasesPayload(['purchase-1', 'purchase-2']);

        $filterResponse = $filterPayloadService->request(
            'https://example.com/filter-mah-features',
            $payload,
            $app,
            $context
        );

        $actualPurchases = $filterResponse->purchases;
        static::assertCount(2, $actualPurchases);
        static::assertSame('purchase-1', $actualPurchases[0]);
        static::assertSame('purchase-2', $actualPurchases[1]);
    }

    public function testRequestReceiveFilteredResponse(): void
    {
        $shopIdProvider = $this->createMock(ShopIdProvider::class);
        $shopIdProvider
            ->method('getShopId')
            ->willReturn($this->ids->get('shop-id'));

        $appPayloadServiceHelper = $this->createMock(AppPayloadServiceHelper::class);
        $appPayloadServiceHelper->expects(static::once())
            ->method('createRequestOptions')
            ->willReturn(new AppPayloadStruct([
                'app_request_context' => Context::createDefaultContext(),
                'request_type' => [
                    'app_secret' => 'very-secret',
                    'validated_response' => true,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => '{"purchases":["purchase-1","purchase-2"]}',
            ]));

        $context = Context::createDefaultContext();
        $responseContent = \json_encode([
            'purchases' => [
                'purchase-2',
            ],
        ], \JSON_THROW_ON_ERROR);

        static::assertNotFalse($responseContent);

        $filterPayloadService = new InAppPurchasesPayloadService(
            $appPayloadServiceHelper,
            new Client(['handler' => new MockHandler([new Response(200, [], $responseContent)])]),
        );

        $url = 'https://example.com/filter-mah-features';

        $app = new AppEntity();
        $app->setName('TestApp');
        $app->setId($this->ids->get('app'));
        $app->setVersion('6.6-dev');
        $app->setAppSecret('very-secret');
        $app->setInAppPurchasesGatewayUrl($url);

        $payload = new InAppPurchasesPayload(['purchase-1', 'purchase-2']);

        $filterResponse = $filterPayloadService->request(
            'https://example.com/filter-mah-features',
            $payload,
            $app,
            $context
        );

        $actualPurchases = $filterResponse->purchases;
        static::assertCount(1, $actualPurchases);
        static::assertSame('purchase-2', $actualPurchases[0]);
    }

    public function testAppSecretMissing(): void
    {
        $definitionInstanceRegistry = $this->createMock(DefinitionInstanceRegistry::class);

        $shopIdProvider = $this->createMock(ShopIdProvider::class);
        $shopIdProvider
            ->method('getShopId')
            ->willReturn($this->ids->get('shop-id'));

        $appPayloadServiceHelper = new AppPayloadServiceHelper(
            $definitionInstanceRegistry,
            $this->createMock(JsonEntityEncoder::class),
            $shopIdProvider,
            StaticInAppPurchaseFactory::createWithFeatures(),
            'https://test-shop.com'
        );

        $app = new AppEntity();
        $app->setName('TestApp');
        $app->setId($this->ids->get('app'));
        $app->setVersion('6.5-dev');
        $app->setName('Test app');
        $app->setInAppPurchasesGatewayUrl('https://example.com/filter-mah-features');

        $filterPayloadService = new InAppPurchasesPayloadService(
            $appPayloadServiceHelper,
            new Client(),
        );

        $payload = $this->createMock(InAppPurchasesPayload::class);

        $this->expectException(AppRegistrationException::class);
        $this->expectExceptionMessage('App secret is missing');

        $filterPayloadService->request(
            'https://example.com/filter-mah-features',
            $payload,
            $app,
            Context::createDefaultContext()
        );
    }

    public function testClientIsUsingPostMethod(): void
    {
        $appPayloadServiceHelper = $this->createMock(AppPayloadServiceHelper::class);
        $appPayloadServiceHelper->expects(static::once())
            ->method('createRequestOptions')
            ->willReturn(new AppPayloadStruct([
                'app_request_context' => Context::createDefaultContext(),
                'request_type' => [
                    'app_secret' => 'very-secret',
                    'validated_response' => true,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => '{"purchases":["purchase-1","purchase-2"]}',
            ]));

        /** @phpstan-ignore cicada.mockingSimpleObjects (it is literally tested, if post method is used) */
        $client = $this->createMock(Client::class);
        $client
            ->expects(static::once())
            ->method('post')
            ->willReturn(new Response(200, [], \json_encode([
                'purchases' => [
                    'purchase-2',
                ],
            ], \JSON_THROW_ON_ERROR)));

        $inAppPayloadServiceHelper = new InAppPurchasesPayloadService($appPayloadServiceHelper, $client);
        $inAppPayloadServiceHelper->request('https://example.com', new InAppPurchasesPayload([]), new AppEntity(), Context::createDefaultContext());
    }
}
