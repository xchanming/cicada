<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Payment;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Payment\PreparedPaymentService;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Integration\PaymentHandler\PreparedTestPaymentHandler;
use Cicada\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('checkout')]
class PreparedPaymentServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private PreparedPaymentService $paymentService;

    /**
     * @var EntityRepository<OrderCollection>
     */
    private EntityRepository $orderRepository;

    /**
     * @var EntityRepository<CustomerCollection>
     */
    private EntityRepository $customerRepository;

    /**
     * @var EntityRepository<OrderTransactionCollection>
     */
    private EntityRepository $orderTransactionRepository;

    /**
     * @var EntityRepository<PaymentMethodCollection>
     */
    private EntityRepository $paymentMethodRepository;

    private Context $context;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);

        PreparedTestPaymentHandler::$preOrderPaymentStruct = null;
        PreparedTestPaymentHandler::$fail = false;

        $this->paymentService = static::getContainer()->get(PreparedPaymentService::class);
        $this->orderTransactionStateHandler = static::getContainer()->get(OrderTransactionStateHandler::class);
        $this->orderRepository = $this->getRepository(OrderDefinition::ENTITY_NAME);
        $this->customerRepository = $this->getRepository(CustomerDefinition::ENTITY_NAME);
        $this->orderTransactionRepository = $this->getRepository(OrderTransactionDefinition::ENTITY_NAME);
        $this->paymentMethodRepository = $this->getRepository(PaymentMethodDefinition::ENTITY_NAME);
        $this->context = Context::createDefaultContext();
    }

    public function testHandlePreOrderPayment(): void
    {
        $paymentMethodId = $this->createPaymentMethod($this->context);
        $cart = Generator::createCart();
        $salesChannelContext = $this->getSalesChannelContext($paymentMethodId);

        $struct = $this->paymentService->handlePreOrderPayment($cart, new RequestDataBag(), $salesChannelContext);

        static::assertInstanceOf(ArrayStruct::class, $struct);
        static::assertSame(PreparedTestPaymentHandler::TEST_STRUCT_CONTENT, $struct->all());
    }

    public function testHandlePreOrderPaymentFails(): void
    {
        $paymentMethodId = $this->createPaymentMethod($this->context);
        $cart = Generator::createCart();
        $salesChannelContext = $this->getSalesChannelContext($paymentMethodId);
        PreparedTestPaymentHandler::$fail = true;

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The validation process of the prepared payment was interrupted due to the following error:' . \PHP_EOL . 'this is supposed to fail');
        $this->paymentService->handlePreOrderPayment($cart, new RequestDataBag(), $salesChannelContext);
    }

    public function testHandlePreOrderPaymentNoPaymentHandler(): void
    {
        $paymentMethodId = $this->createPaymentMethod($this->context, Uuid::randomHex());
        $cart = Generator::createCart();
        $salesChannelContext = $this->getSalesChannelContext($paymentMethodId);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(\sprintf('Could not find payment method with id "%s"', $paymentMethodId));
        $this->paymentService->handlePreOrderPayment($cart, new RequestDataBag(), $salesChannelContext);
    }

    public function testHandlePostOrderPayment(): void
    {
        $paymentMethodId = $this->createPaymentMethod($this->context);
        $customerId = $this->createCustomer($this->context);
        $salesChannelContext = $this->getSalesChannelContext($paymentMethodId);
        $orderId = $this->createOrder($customerId, $paymentMethodId, $salesChannelContext->getContext());
        $this->createTransaction($orderId, $paymentMethodId, $salesChannelContext->getContext());
        $order = $this->loadOrder($orderId, $salesChannelContext);
        $struct = new ArrayStruct(['testStruct']);

        $this->paymentService->handlePostOrderPayment($order, new RequestDataBag(), $salesChannelContext, $struct);

        static::assertSame($struct, PreparedTestPaymentHandler::$preOrderPaymentStruct);
    }

    public function testHandlePostOrderPaymentWithoutStruct(): void
    {
        $paymentMethodId = $this->createPaymentMethod($this->context);
        $customerId = $this->createCustomer($this->context);
        $salesChannelContext = $this->getSalesChannelContext($paymentMethodId);
        $orderId = $this->createOrder($customerId, $paymentMethodId, $salesChannelContext->getContext());
        $this->createTransaction($orderId, $paymentMethodId, $salesChannelContext->getContext());
        $order = $this->loadOrder($orderId, $salesChannelContext);

        $this->paymentService->handlePostOrderPayment($order, new RequestDataBag(), $salesChannelContext, null);
        static::assertNull(PreparedTestPaymentHandler::$preOrderPaymentStruct);
    }

    public function testHandlePostOrderPaymentFails(): void
    {
        $paymentMethodId = $this->createPaymentMethod($this->context);
        $customerId = $this->createCustomer($this->context);
        $salesChannelContext = $this->getSalesChannelContext($paymentMethodId);
        $orderId = $this->createOrder($customerId, $paymentMethodId, $salesChannelContext->getContext());
        $this->createTransaction($orderId, $paymentMethodId, $salesChannelContext->getContext());
        $order = $this->loadOrder($orderId, $salesChannelContext);
        $struct = new ArrayStruct(['testStruct']);
        PreparedTestPaymentHandler::$fail = true;

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The capture process of the prepared payment was interrupted due to the following error:' . \PHP_EOL . 'this is supposed to fail');

        $this->paymentService->handlePostOrderPayment($order, new RequestDataBag(), $salesChannelContext, $struct);
    }

    public function testHandlePostOrderPaymentNoPaymentHandler(): void
    {
        $paymentMethodId = $this->createPaymentMethod($this->context, Uuid::randomHex());
        $customerId = $this->createCustomer($this->context);
        $salesChannelContext = $this->getSalesChannelContext($paymentMethodId);
        $orderId = $this->createOrder($customerId, $paymentMethodId, $salesChannelContext->getContext());
        $this->createTransaction($orderId, $paymentMethodId, $salesChannelContext->getContext());
        $order = $this->loadOrder($orderId, $salesChannelContext);
        $struct = new ArrayStruct(['testStruct']);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(\sprintf('Could not find payment method with id "%s"', $paymentMethodId));
        $this->paymentService->handlePostOrderPayment($order, new RequestDataBag(), $salesChannelContext, $struct);
    }

    public function testHandlePostOrderPaymentNoTransaction(): void
    {
        $paymentMethodId = $this->createPaymentMethod($this->context);
        $customerId = $this->createCustomer($this->context);
        $salesChannelContext = $this->getSalesChannelContext($paymentMethodId);
        $orderId = $this->createOrder($customerId, $paymentMethodId, $salesChannelContext->getContext());
        $order = $this->loadOrder($orderId, $salesChannelContext);
        $struct = new ArrayStruct(['testStruct']);

        $this->paymentService->handlePostOrderPayment($order, new RequestDataBag(), $salesChannelContext, $struct);
        static::assertNull(PreparedTestPaymentHandler::$preOrderPaymentStruct);
    }

    public function testHandlePostOrderPaymentNoTransactionLoaded(): void
    {
        $paymentMethodId = $this->createPaymentMethod($this->context);
        $customerId = $this->createCustomer($this->context);
        $salesChannelContext = $this->getSalesChannelContext($paymentMethodId);
        $orderId = $this->createOrder($customerId, $paymentMethodId, $salesChannelContext->getContext());
        $order = $this->loadOrder($orderId, $salesChannelContext, false);
        $struct = new ArrayStruct(['testStruct']);

        $this->expectException(PaymentException::class);

        $this->paymentService->handlePostOrderPayment($order, new RequestDataBag(), $salesChannelContext, $struct);
    }

    public function testHandlePostOrderPaymentTransactionNonInitialState(): void
    {
        $paymentMethodId = $this->createPaymentMethod($this->context);
        $customerId = $this->createCustomer($this->context);
        $salesChannelContext = $this->getSalesChannelContext($paymentMethodId);
        $orderId = $this->createOrder($customerId, $paymentMethodId, $salesChannelContext->getContext());
        $transactionId = $this->createTransaction($orderId, $paymentMethodId, $salesChannelContext->getContext());
        $this->orderTransactionStateHandler->process($transactionId, $salesChannelContext->getContext());
        $order = $this->loadOrder($orderId, $salesChannelContext);
        $struct = new ArrayStruct(['testStruct']);

        $this->paymentService->handlePostOrderPayment($order, new RequestDataBag(), $salesChannelContext, $struct);
        static::assertNull(PreparedTestPaymentHandler::$preOrderPaymentStruct);
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

    private function createOrder(
        string $customerId,
        string $paymentMethodId,
        Context $context
    ): string {
        $orderId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $order = [
            'id' => $orderId,
            'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'orderNumber' => Uuid::randomHex(),
            'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
            'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'orderCustomer' => [
                'customerId' => $customerId,
                'email' => 'test@example.com',
                'salutationId' => $this->getValidSalutationId(),
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
            ],
            'stateId' => static::getContainer()->get(InitialStateIdLoader::class)->get(OrderTransactionStates::STATE_MACHINE),
            'paymentMethodId' => $paymentMethodId,
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1.0,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'billingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'salutationId' => $this->getValidSalutationId(),
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                    'countryId' => $this->getValidCountryId(),
                ],
            ],
            'lineItems' => [],
            'deliveries' => [],
            'context' => '{}',
            'payload' => '{}',
        ];

        $this->orderRepository->upsert([$order], $context);

        return $orderId;
    }

    private function createCustomer(Context $context): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'customerNumber' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'email' => Uuid::randomHex() . '@example.com',
            'password' => TestDefaults::HASHED_PASSWORD,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'customerId' => $customerId,
                    'countryId' => $this->getValidCountryId(),
                    'salutationId' => $this->getValidSalutationId(),
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                ],
            ],
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        $this->customerRepository->upsert([$customer], $context);

        return $customerId;
    }

    private function createPaymentMethod(
        Context $context,
        string $handlerIdentifier = PreparedTestPaymentHandler::class
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

    private function getRepository(string $entityName): EntityRepository
    {
        $repository = static::getContainer()->get(\sprintf('%s.repository', $entityName));
        static::assertInstanceOf(EntityRepository::class, $repository);

        return $repository;
    }

    private function loadOrder(string $orderId, SalesChannelContext $context, bool $withTransactions = true): OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria
            ->addAssociation('orderCustomer.customer')
            ->addAssociation('orderCustomer.salutation')
            ->addAssociation('deliveries.shippingMethod')
            ->addAssociation('deliveries.shippingOrderAddress.country')
            ->addAssociation('lineItems.cover')
            ->addAssociation('currency')
            ->addAssociation('addresses.country');
        if ($withTransactions) {
            $criteria
                ->addAssociation('transactions.paymentMethod.appPaymentMethod.app')
                ->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));
        }

        $order = $this->orderRepository->search($criteria, $context->getContext())->getEntities()->first();
        static::assertNotNull($order);

        return $order;
    }
}
