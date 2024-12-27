<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Gateway\SalesChannel;

use Cicada\Core\Checkout\Gateway\SalesChannel\CheckoutGatewayRoute;
use Cicada\Core\Framework\App\Hmac\RequestSigner;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Integration\App\GuzzleHistoryCollector;
use Cicada\Core\Test\Integration\App\TestAppServer;
use Cicada\Core\Test\Integration\PaymentHandler\TestPaymentHandler;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Tests\Integration\Core\Framework\App\GuzzleTestClientBehaviour;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversClass(CheckoutGatewayRoute::class)]
#[Group('store-api')]
#[Package('checkout')]
class CheckoutGatewayRouteTest extends TestCase
{
    use GuzzleTestClientBehaviour;
    use SalesChannelApiTestBehaviour;

    private IdsCollection $ids;

    private KernelBrowser $browser;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->createData();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
            'paymentMethodId' => $this->ids->get('payment'),
            'paymentMethods' => [
                ['id' => $this->ids->get('payment')],
            ],
        ]);

        $historyCollector = static::getContainer()->get(GuzzleHistoryCollector::class);
        static::assertInstanceOf(GuzzleHistoryCollector::class, $historyCollector);
        $historyCollector->resetHistory();
        $mockHandler = static::getContainer()->get(MockHandler::class);
        static::assertInstanceOf(MockHandler::class, $mockHandler);
        $mockHandler->reset();
        $testServer = static::getContainer()->get(TestAppServer::class);
        static::assertInstanceOf(TestAppServer::class, $testServer);
        $testServer->reset();
    }

    public function testLoad(): void
    {
        $body = \json_encode([
            [
                'command' => 'add-payment-method',
                'payload' => [
                    'paymentMethodTechnicalName' => 'payment_new-test',
                ],
            ],
        ], flags: \JSON_THROW_ON_ERROR);

        $secret = \hash_hmac('sha256', $body, 'secret');

        $this->appendNewResponse(new Response(200, [RequestSigner::CICADA_APP_SIGNATURE => $secret], $body));

        $this->browser->request('GET', '/store-api/checkout/gateway');
        static::assertNotFalse($this->browser->getResponse()->getContent());
        $response = \json_decode($this->browser->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('payments', $response, 'Response has probably errors');
        static::assertIsArray($response['payments']);

        $payments = $response['payments'];

        static::assertCount(2, $payments);
        static::assertArrayHasKey('technicalName', $payments[0]);
        static::assertSame('payment_test', $payments[0]['technicalName']);
        static::assertArrayHasKey('technicalName', $payments[1]);
        static::assertSame('payment_new-test', $payments[1]['technicalName']);
    }

    public function testLoadWithHandlerError(): void
    {
        $body = \json_encode([
            [
                'command' => 'add-payment-method',
                'payload' => [
                    'paymentMethodTechnicalName' => 'this-payment-method-does-not-exist',
                ],
            ],
        ], flags: \JSON_THROW_ON_ERROR);

        $secret = \hash_hmac('sha256', $body, 'secret');

        $this->appendNewResponse(new Response(200, [RequestSigner::CICADA_APP_SIGNATURE => $secret], $body));

        $this->browser->request('GET', '/store-api/checkout/gateway');
        static::assertNotFalse($this->browser->getResponse()->getContent());
        $response = \json_decode($this->browser->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertIsArray($response['errors']);

        $errors = $response['errors'];

        static::assertCount(1, $errors);
        static::assertArrayHasKey('code', $errors[0]);
        static::assertSame('CHECKOUT_GATEWAY__HANDLER_EXCEPTION', $errors[0]['code']);
        static::assertArrayHasKey('detail', $errors[0]);
        static::assertSame('Payment method "this-payment-method-does-not-exist" not found', $errors[0]['detail']);
    }

    public function testLoadWithMultipleCommands(): void
    {
        $body = \json_encode([
            [
                'command' => 'add-payment-method',
                'payload' => [
                    'paymentMethodTechnicalName' => 'payment_new-test',
                ],
            ],
            [
                'command' => 'remove-payment-method',
                'payload' => [
                    'paymentMethodTechnicalName' => 'payment_test',
                ],
            ],
        ], flags: \JSON_THROW_ON_ERROR);

        $secret = \hash_hmac('sha256', $body, 'secret');

        $this->appendNewResponse(new Response(200, [RequestSigner::CICADA_APP_SIGNATURE => $secret], $body));

        $this->browser->request('GET', '/store-api/checkout/gateway');
        static::assertNotFalse($this->browser->getResponse()->getContent());
        $response = \json_decode($this->browser->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('payments', $response, 'Response has probably errors');
        static::assertIsArray($response['payments']);

        $payments = $response['payments'];

        static::assertCount(1, $payments);

        static::assertArrayHasKey('technicalName', $payments[0]);
        static::assertSame('payment_new-test', $payments[0]['technicalName']);
    }

    private function createData(): void
    {
        $app = [
            'id' => Uuid::randomHex(),
            'name' => 'Test app',
            'path' => 'test-app',
            'version' => '0.0.1',
            'active' => true,
            'checkoutGatewayUrl' => 'https://test-app.com/checkout-gateway',
            'appSecret' => 'secret',
            'integration' => [
                'label' => 'Test app',
                'accessKey' => 'foo',
                'secretAccessKey' => 'bar',
            ],
            'aclRole' => [
                'name' => 'foo',
                'privileges' => [
                    'checkout-gateway:read',
                ],
            ],
            'translations' => [
                'en-GB' => [
                    'label' => 'Test app',
                ],
            ],
        ];

        static::getContainer()
            ->get('app.repository')
            ->create([$app], Context::createDefaultContext());

        $payments = [
            [
                'id' => $this->ids->create('payment'),
                'name' => 'Payment 1',
                'technicalName' => 'payment_test',
                'active' => true,
                'handlerIdentifier' => TestPaymentHandler::class,
            ],
            [
                'id' => $this->ids->create('new-payment'),
                'name' => 'Payment 2',
                'technicalName' => 'payment_new-test',
                'active' => true,
                'handlerIdentifier' => TestPaymentHandler::class,
            ],
        ];

        static::getContainer()
            ->get('payment_method.repository')
            ->create($payments, Context::createDefaultContext());
    }
}
