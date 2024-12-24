<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Payment\Payload;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Api\Serializer\JsonEntityEncoder;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Hmac\Guzzle\AuthMiddleware;
use Cicada\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Cicada\Core\Framework\App\Payload\AppPayloadStruct;
use Cicada\Core\Framework\App\Payment\Payload\PaymentPayloadService;
use Cicada\Core\Framework\App\Payment\Payload\Struct\PaymentPayload;
use Cicada\Core\Framework\App\Payment\Payload\Struct\PaymentPayloadInterface;
use Cicada\Core\Framework\App\Payment\Response\PaymentResponse;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Serializer\StructNormalizer;
use Cicada\Core\Framework\Test\Store\StaticInAppPurchaseFactory;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PaymentPayloadService::class)]
class PaymentPayloadServiceTest extends TestCase
{
    private ClientInterface&MockObject $client;

    private AppPayloadServiceHelper&MockObject $helper;

    private PaymentPayloadService $service;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
        $this->client = $this->createMock(ClientInterface::class);
        $this->helper = $this->createMock(AppPayloadServiceHelper::class);
        $this->service = new PaymentPayloadService($this->helper, $this->client);
    }

    public function testRequest(): void
    {
        $definition = new OrderTransactionDefinition();
        $definition->compile($this->createMock(DefinitionInstanceRegistry::class));

        $definitionInstanceRegistry = $this->createMock(DefinitionInstanceRegistry::class);
        $definitionInstanceRegistry
            ->method('getByEntityName')
            ->willReturn($definition);

        $shopIdProvider = $this->createMock(ShopIdProvider::class);
        $shopIdProvider
            ->method('getShopId')
            ->willReturn($this->ids->get('shop-id'));

        $entityEncoder = new JsonEntityEncoder(
            new Serializer([new StructNormalizer()], [new JsonEncoder()])
        );

        $appPayloadServiceHelper = new AppPayloadServiceHelper(
            $definitionInstanceRegistry,
            $entityEncoder,
            $shopIdProvider,
            StaticInAppPurchaseFactory::createWithFeatures(),
            'https://test-shop.com'
        );

        $response = \json_encode(['status' => 'paid'], \JSON_THROW_ON_ERROR);

        $client = new Client(['handler' => new MockHandler([new Response(200, [], $response)])]);

        $transaction = new OrderTransactionEntity();
        $transaction->setId($this->ids->get('transaction'));
        $payload = new PaymentPayload($transaction, new OrderEntity());

        $app = new AppEntity();
        $app->setName('foo');
        $app->setId($this->ids->get('app'));
        $app->setVersion('1.0.0');
        $app->setAppSecret('devsecret');

        $service = new PaymentPayloadService($appPayloadServiceHelper, $client);

        $gatewayResponse = $service->request(
            'https://example.com',
            $payload,
            $app,
            PaymentResponse::class,
            Context::createDefaultContext()
        );

        static::assertInstanceOf(PaymentResponse::class, $gatewayResponse);
        static::assertSame('paid', $gatewayResponse->getStatus());
    }

    public function testRequestReturnsExpectedResponse(): void
    {
        $payload = $this->createMock(PaymentPayloadInterface::class);
        $app = new AppEntity();
        $app->setName('InsecureApp');
        $app->setVersion('1.0.0');
        $app->setAppSecret('secret');

        $context = Context::createDefaultContext();

        $this->helper
            ->expects(static::once())
            ->method('createRequestOptions')
            ->with($payload, $app)
            ->willReturn($this->buildTestPayload($context));

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with('POST', 'http://example.com', [
                AuthMiddleware::APP_REQUEST_CONTEXT => $context,
                AuthMiddleware::APP_REQUEST_TYPE => [
                    AuthMiddleware::APP_SECRET => 'secret',
                    AuthMiddleware::VALIDATED_RESPONSE => true,
                ],
                'timeout' => PaymentPayloadService::PAYMENT_REQUEST_TIMEOUT,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => '[]',
            ])
            ->willReturn(new Response(200, [], json_encode(['message' => 'foo'], \JSON_THROW_ON_ERROR)));

        $response = $this->service->request(
            'http://example.com',
            $payload,
            $app,
            PaymentResponse::class,
            $context,
        );

        static::assertInstanceOf(PaymentResponse::class, $response);
        static::assertSame('foo', $response->getErrorMessage());
    }

    private function buildTestPayload(Context $context): AppPayloadStruct
    {
        return new AppPayloadStruct([
            AuthMiddleware::APP_REQUEST_CONTEXT => $context,
            AuthMiddleware::APP_REQUEST_TYPE => [
                AuthMiddleware::APP_SECRET => 'secret',
                AuthMiddleware::VALIDATED_RESPONSE => true,
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 20,
            'body' => '[]',
        ]);
    }
}
