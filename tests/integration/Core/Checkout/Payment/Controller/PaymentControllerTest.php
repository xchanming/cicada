<?php
declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Payment\Controller;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Cicada\Core\Checkout\Payment\Cart\Token\JWTFactoryV2;
use Cicada\Core\Checkout\Payment\Cart\Token\TokenStruct;
use Cicada\Core\Checkout\Payment\PaymentProcessor;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Cicada\Core\Test\Integration\PaymentHandler\TestPaymentHandler;
use Cicada\Core\Test\Integration\Traits\OrderFixture;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use OrderFixture;

    private JWTFactoryV2 $tokenFactory;

    private EntityRepository $orderRepository;

    private EntityRepository $orderTransactionRepository;

    private EntityRepository $paymentMethodRepository;

    private PaymentProcessor $paymentProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenFactory = static::getContainer()->get(JWTFactoryV2::class);
        $this->orderRepository = static::getContainer()->get('order.repository');
        $this->orderTransactionRepository = static::getContainer()->get('order_transaction.repository');
        $this->paymentMethodRepository = static::getContainer()->get('payment_method.repository');
        $this->paymentProcessor = static::getContainer()->get(PaymentProcessor::class);
    }

    public function testCallWithoutToken(): void
    {
        $client = $this->getBrowser();

        $client->request('GET', '/payment/finalize-transaction');

        static::assertIsString($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('errors', $response);
        static::assertSame('FRAMEWORK__MISSING_REQUEST_PARAMETER', $response['errors'][0]['code']);
    }

    public function testCallWithInvalidToken(): void
    {
        $client = $this->getBrowser();

        $client->request('GET', '/payment/finalize-transaction?_sw_payment_token=abc');

        static::assertIsString($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('errors', $response);
        static::assertSame('CHECKOUT__INVALID_PAYMENT_TOKEN', $response['errors'][0]['code']);
    }

    public function testValidTokenWithInvalidOrder(): void
    {
        $client = $this->getBrowser();

        $tokenStruct = new TokenStruct(null, null, Uuid::randomHex(), Uuid::randomHex(), 'testFinishUrl');
        $token = $this->tokenFactory->generateToken($tokenStruct);

        $client->request('GET', '/payment/finalize-transaction?_sw_payment_token=' . $token);

        static::assertIsString($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('errors', $response);
        static::assertSame('CHECKOUT__INVALID_PAYMENT_TOKEN', $response['errors'][0]['code']);
    }

    public function testValid(): void
    {
        $transaction = $this->createValidOrderTransaction();

        $tokenStruct = new TokenStruct(null, null, $transaction->getPaymentMethodId(), $transaction->getId(), 'testFinishUrl');
        $token = $this->tokenFactory->generateToken($tokenStruct);

        $client = $this->getBrowser();

        $client->request('GET', '/payment/finalize-transaction?_sw_payment_token=' . $token);

        $response = $client->getResponse();
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertStringContainsString('testFinishUrl', $response->getTargetUrl());
        static::assertTrue($response->isRedirection());
    }

    public function testCancelledPayment(): void
    {
        $transaction = $this->createValidOrderTransaction();

        $tokenStruct = new TokenStruct(null, null, $transaction->getPaymentMethodId(), $transaction->getId(), 'testFinishUrl', null, 'testErrorUrl');
        $token = $this->tokenFactory->generateToken($tokenStruct);

        $client = $this->getBrowser();

        $client->request('GET', '/payment/finalize-transaction?_sw_payment_token=' . $token . '&cancel=1');

        $response = $client->getResponse();
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertStringContainsString('testErrorUrl', $response->getTargetUrl());
        static::assertTrue($response->isRedirection());
    }

    private function getBrowser(): KernelBrowser
    {
        return KernelLifecycleManager::createBrowser(KernelLifecycleManager::getKernel(), false);
    }

    private function getSalesChannelContext(string $paymentMethodId): SalesChannelContext
    {
        return static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL, [
                SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId,
            ]);
    }

    private function createTransaction(
        string $orderId,
        string $paymentMethodId,
        Context $context
    ): string {
        $id = Uuid::randomHex();
        $transaction = [
            'id' => $id,
            'orderId' => $orderId,
            'paymentMethodId' => $paymentMethodId,
            'stateId' => static::getContainer()->get(InitialStateIdLoader::class)->get(OrderTransactionStates::STATE_MACHINE),
            'amount' => new CalculatedPrice(100, 100, new CalculatedTaxCollection(), new TaxRuleCollection(), 1),
            'payload' => '{}',
        ];

        $this->orderTransactionRepository->upsert([$transaction], $context);

        return $id;
    }

    private function createOrder(Context $context): string
    {
        $orderId = Uuid::randomHex();

        $order = $this->getOrderData($orderId, $context);
        $this->orderRepository->upsert($order, $context);

        return $orderId;
    }

    private function createPaymentMethod(
        Context $context,
        string $handlerIdentifier = TestPaymentHandler::class
    ): string {
        $id = Uuid::randomHex();
        $payment = [
            'id' => $id,
            'handlerIdentifier' => $handlerIdentifier,
            'name' => 'Test Payment',
            'technicalName' => 'payment_test',
            'description' => 'Test payment handler',
            'active' => true,
        ];

        $this->paymentMethodRepository->upsert([$payment], $context);

        return $id;
    }

    private function createValidOrderTransaction(): OrderTransactionEntity
    {
        $context = Context::createDefaultContext();

        $paymentMethodId = $this->createPaymentMethod($context);
        $orderId = $this->createOrder($context);
        $transactionId = $this->createTransaction($orderId, $paymentMethodId, $context);

        $salesChannelContext = $this->getSalesChannelContext($paymentMethodId);

        $response = $this->paymentProcessor->pay($orderId, new Request(), $salesChannelContext);

        static::assertNotNull($response);
        static::assertEquals(TestPaymentHandler::REDIRECT_URL, $response->getTargetUrl());

        $transaction = new OrderTransactionEntity();
        $transaction->setId($transactionId);
        $transaction->setPaymentMethodId($paymentMethodId);
        $transaction->setOrderId($orderId);
        $transaction->setStateId(Uuid::randomHex());

        return $transaction;
    }
}
