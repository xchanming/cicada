<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Checkout\Payload;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Checkout\Gateway\AppCheckoutGatewayResponse;
use Cicada\Core\Framework\App\Checkout\Payload\AppCheckoutGatewayPayload;
use Cicada\Core\Framework\App\Checkout\Payload\AppCheckoutGatewayPayloadService;
use Cicada\Core\Framework\App\Hmac\Guzzle\AuthMiddleware;
use Cicada\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Cicada\Core\Framework\App\Payload\AppPayloadStruct;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\ExceptionLogger;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(AppCheckoutGatewayPayloadService::class)]
#[Package('checkout')]
class AppCheckoutGatewayPayloadServiceTest extends TestCase
{
    public function testRequest(): void
    {
        $context = Generator::createSalesChannelContext();
        $cart = Generator::createCart();
        $paymentMethods = ['paymentMethod-1', 'paymentMethod-2'];
        $shippingMethods = ['shippingMethod-1', 'shippingMethod-2'];

        $app = new AppEntity();
        $app->setVersion('1.0.0');
        $app->setAppSecret('devsecret');

        $payload = new AppCheckoutGatewayPayload($context, $cart, $paymentMethods, $shippingMethods);
        $encodedPayload = \json_encode($this->encodePayload($payload), \JSON_THROW_ON_ERROR);

        $helper = $this->createMock(AppPayloadServiceHelper::class);
        $helper
            ->expects(static::once())
            ->method('createRequestOptions')
            ->with($payload, $app, $context->getContext())
            ->willReturn($this->buildTestPayload($context->getContext(), $encodedPayload));

        $response = [
            [
                'command' => 'test-command',
                'payload' => ['test-payload'],
            ],
        ];

        $handler = new MockHandler();
        $handler->append($this->buildResponse($response));

        $client = new Client(['handler' => $handler]);

        $service = new AppCheckoutGatewayPayloadService(
            $helper,
            $client,
            $this->createMock(ExceptionLogger::class),
        );

        $gatewayResponse = $service->request('https://example.com', $payload, $app);

        static::assertInstanceOf(AppCheckoutGatewayResponse::class, $gatewayResponse);
        static::assertSame($response, $gatewayResponse->getCommands());
    }

    public function testRequestAppThrowsException(): void
    {
        $context = Generator::createSalesChannelContext();
        $cart = Generator::createCart();
        $paymentMethods = ['paymentMethod-1', 'paymentMethod-2'];
        $shippingMethods = ['shippingMethod-1', 'shippingMethod-2'];

        $app = new AppEntity();
        $app->setVersion('1.0.0');
        $app->setAppSecret('devsecret');

        $payload = new AppCheckoutGatewayPayload($context, $cart, $paymentMethods, $shippingMethods);

        $e = new BadResponseException('Bad', new Request('POST', 'https://example.com'), new Response());

        $handler = new MockHandler();
        $handler->append($e);

        $client = new Client(['handler' => $handler]);

        $logger = $this->createMock(ExceptionLogger::class);
        $logger
            ->expects(static::once())
            ->method('logOrThrowException')
            ->with($e);

        $service = new AppCheckoutGatewayPayloadService(
            $this->createMock(AppPayloadServiceHelper::class),
            $client,
            $logger,
        );

        $gatewayResponse = $service->request('https://example.com', $payload, $app);

        static::assertNull($gatewayResponse);
    }

    /**
     * @return array<string, mixed>
     */
    private function encodePayload(AppCheckoutGatewayPayload $payload): array
    {
        return [
            'salesChannelContext' => $payload->getSalesChannelContext()->jsonSerialize(),
            'cart' => $payload->getCart()->jsonSerialize(),
            'paymentMethods' => $payload->getPaymentMethods(),
            'shippingMethods' => $payload->getShippingMethods(),
        ];
    }

    /**
     * @param array<array-key, mixed> $body
     */
    private function buildResponse(array $body): ResponseInterface
    {
        return new Response(200, [], \json_encode($body, \JSON_THROW_ON_ERROR));
    }

    private function buildTestPayload(Context $context, string $payload): AppPayloadStruct
    {
        return new AppPayloadStruct([
            AuthMiddleware::APP_REQUEST_CONTEXT => $context,
            AuthMiddleware::APP_REQUEST_TYPE => [
                AuthMiddleware::APP_SECRET => 'some-secret',
                AuthMiddleware::VALIDATED_RESPONSE => true,
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => $payload,
        ]);
    }
}
