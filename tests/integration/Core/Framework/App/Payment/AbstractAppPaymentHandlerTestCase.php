<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Payment;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundStates;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Checkout\Payment\Cart\PaymentRefundProcessor;
use Cicada\Core\Checkout\Payment\PaymentProcessor;
use Cicada\Core\Checkout\Payment\PaymentService;
use Cicada\Core\Checkout\Payment\PreparedPaymentService;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Lifecycle\AppLifecycle;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Cicada\Core\System\StateMachine\StateMachineRegistry;
use Cicada\Core\Test\Integration\Builder\Customer\CustomerBuilder;
use Cicada\Core\Test\Integration\Builder\Order\OrderBuilder;
use Cicada\Core\Test\Integration\Builder\Order\OrderTransactionBuilder;
use Cicada\Core\Test\Integration\Builder\Order\OrderTransactionCaptureBuilder;
use Cicada\Core\Test\Integration\Builder\Order\OrderTransactionCaptureRefundBuilder;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Cicada\Tests\Integration\Core\Framework\App\GuzzleTestClientBehaviour;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
abstract class AbstractAppPaymentHandlerTestCase extends TestCase
{
    use GuzzleTestClientBehaviour;

    final public const ERROR_MESSAGE = 'testError';

    protected PaymentService $paymentService;

    protected PreparedPaymentService $preparedPaymentService;

    protected PaymentProcessor $paymentProcessor;

    protected PaymentRefundProcessor $paymentRefundProcessor;

    protected ShopIdProvider $shopIdProvider;

    protected string $shopUrl;

    protected AppEntity $app;

    protected IdsCollection $ids;

    /**
     * @var EntityRepository<OrderCollection>
     */
    protected EntityRepository $orderRepository;

    /**
     * @var EntityRepository<OrderTransactionCollection>
     */
    protected EntityRepository $orderTransactionRepository;

    private EntityRepository $customerRepository;

    private EntityRepository $paymentMethodRepository;

    private StateMachineRegistry $stateMachineRegistry;

    private InitialStateIdLoader $initialStateIdLoader;

    private AbstractSalesChannelContextFactory $salesChannelContextFactory;

    private EntityRepository $orderTransactionCaptureRepository;

    /**
     * @var EntityRepository<OrderTransactionCaptureRefundCollection>
     */
    private EntityRepository $orderTransactionCaptureRefundRepository;

    private Context $context;

    protected function setUp(): void
    {
        $this->orderRepository = static::getContainer()->get('order.repository');
        $this->customerRepository = static::getContainer()->get('customer.repository');
        $this->paymentMethodRepository = static::getContainer()->get('payment_method.repository');
        $this->orderTransactionRepository = static::getContainer()->get('order_transaction.repository');
        $this->orderTransactionCaptureRepository = static::getContainer()->get('order_transaction_capture.repository');
        $this->orderTransactionCaptureRefundRepository = static::getContainer()->get('order_transaction_capture_refund.repository');
        $this->stateMachineRegistry = static::getContainer()->get(StateMachineRegistry::class);
        $this->initialStateIdLoader = static::getContainer()->get(InitialStateIdLoader::class);
        $this->salesChannelContextFactory = static::getContainer()->get(SalesChannelContextFactory::class);
        $this->shopUrl = $_SERVER['APP_URL'];
        $this->shopIdProvider = static::getContainer()->get(ShopIdProvider::class);
        $this->paymentService = static::getContainer()->get(PaymentService::class);
        $this->paymentProcessor = static::getContainer()->get(PaymentProcessor::class);
        $this->preparedPaymentService = static::getContainer()->get(PreparedPaymentService::class);
        $this->paymentRefundProcessor = static::getContainer()->get(PaymentRefundProcessor::class);
        $this->context = Context::createDefaultContext();

        $manifest = Manifest::createFromXmlFile(__DIR__ . '/_fixtures/testPayments/manifest.xml');

        $appLifecycle = static::getContainer()->get(AppLifecycle::class);
        $appLifecycle->install($manifest, true, $this->context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'testPayments'));
        /** @var EntityRepository<AppCollection> $appRepository */
        $appRepository = static::getContainer()->get('app.repository');

        $app = $appRepository->search($criteria, $this->context)->getEntities()->first();
        static::assertNotNull($app);
        $this->app = $app;
        $this->ids = new IdsCollection();

        $this->resetHistory();
    }

    protected function createCustomer(): string
    {
        $customerId = $this->ids->get('customer');
        $addressId = $this->ids->get('address');

        $customer = (new CustomerBuilder($this->ids, '1337'))
            ->name('Max')
            ->nickname('Mustermann')
            ->add('id', $this->ids->get('customer'))
            ->add('email', Uuid::randomHex() . '@example.com')
            ->add('salesChannelId', TestDefaults::SALES_CHANNEL)
            ->add('password', '12345678')
            ->defaultShippingAddress('address')
            ->defaultBillingAddress('address', [
                'id' => $addressId,
                'customerId' => $customerId,
                'countryId' => $this->getValidCountryId(),
                'salutationId' => $this->getValidSalutationId(),
                'name' => 'Max',
                'street' => 'Ebbinghoff 10',
                'zipcode' => '48624',
                'city' => 'Schöppingen',
            ])
            ->customerGroup(TestDefaults::FALLBACK_CUSTOMER_GROUP);

        if (!Feature::isActive('v6.7.0.0')) {
            $customer->add('defaultPaymentMethodId', $this->getValidPaymentMethodId());
        }

        $this->customerRepository->upsert([$customer->build()], $this->context);

        return $customerId;
    }

    protected function createOrder(string $paymentMethodId): string
    {
        $orderId = $this->ids->get('order');
        $addressId = $this->ids->get('address');

        $this->ids->set(
            'state',
            static::getContainer()->get(InitialStateIdLoader::class)->get(OrderStates::STATE_MACHINE)
        );

        $stateId = $this->ids->get('state');
        $customerId = $this->createCustomer();

        $order = (new OrderBuilder($this->ids, '10000'))
            ->add('id', $this->ids->get('order'))
            ->add('orderDateTime', (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT))
            ->add('price', new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET))
            ->add('shippingCosts', new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()))
            ->add('orderCustomer', [
                'customerId' => $customerId,
                'email' => 'test@example.com',
                'salutationId' => $this->getValidSalutationId(),
                'name' => 'Max',
            ])
            ->add('stateId', $stateId)
            ->add('paymentMethodId', $paymentMethodId)
            ->add('currencyId', Defaults::CURRENCY)
            ->add('currencyFactor', 1.0)
            ->add('salesChannelId', TestDefaults::SALES_CHANNEL)
            ->addAddress('address', [
                'id' => $addressId,
                'salutationId' => $this->getValidSalutationId(),
                'name' => 'Max',
                'street' => 'Ebbinghoff 10',
                'zipcode' => '48624',
                'city' => 'Schöppingen',
                'countryId' => $this->getValidCountryId(),
            ])
            ->add('billingAddressId', $addressId)
            ->add('shippingAddressId', $addressId)
            ->add('context', '{}')
            ->add('payload', '{}')
            ->build();

        $this->orderRepository->upsert([$order], $this->context);

        return $orderId;
    }

    protected function createTransaction(string $orderId, string $paymentMethodId): string
    {
        $this->ids->set(
            'transaction_state',
            $this->initialStateIdLoader->get(OrderTransactionStates::STATE_MACHINE)
        );

        $transaction = (new OrderTransactionBuilder($this->ids, 'transaction'))
            ->add('orderId', $orderId)
            ->add('paymentMethodId', $paymentMethodId)
            ->add('stateId', $this->ids->get('transaction_state'))
            ->add('payload', '{}')
            ->amount(100)
            ->build();

        $this->orderTransactionRepository->upsert([$transaction], $this->context);

        return $this->ids->get('transaction');
    }

    protected function createCapture(string $orderTransactionId): string
    {
        $capture = (new OrderTransactionCaptureBuilder($this->ids, 'capture', $orderTransactionId))
            ->build();

        $this->orderTransactionCaptureRepository->upsert([$capture], $this->context);

        return $this->ids->get('capture');
    }

    protected function createRefund(string $captureId): string
    {
        $refund = (new OrderTransactionCaptureRefundBuilder($this->ids, 'refund', $captureId))
            ->build();

        $this->orderTransactionCaptureRefundRepository->upsert([$refund], $this->context);

        return $this->ids->get('refund');
    }

    protected function getPaymentMethodId(string $name): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', \sprintf('app\\testPayments_%s', $name)));
        $id = $this->paymentMethodRepository->searchIds($criteria, $this->context)->firstId();
        static::assertNotNull($id);

        return $id;
    }

    protected function getSalesChannelContext(string $paymentMethodId, ?string $customerId = null): SalesChannelContext
    {
        return $this->salesChannelContextFactory->create(
            Uuid::randomHex(),
            TestDefaults::SALES_CHANNEL,
            [
                SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId,
                SalesChannelContextService::CUSTOMER_ID => $customerId,
            ]
        );
    }

    /**
     * @param array<int|string, mixed> $content
     */
    protected function signResponse(array $content): ResponseInterface
    {
        $json = \json_encode($content, \JSON_THROW_ON_ERROR);
        static::assertIsString($json);

        $secret = $this->app->getAppSecret();
        static::assertNotNull($secret);

        $hmac = \hash_hmac('sha256', $json, $secret);

        return new Response(
            200,
            [
                'cicada-app-signature' => $hmac,
            ],
            $json
        );
    }

    protected function assertOrderTransactionState(string $state, string $transactionId): void
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('state');

        $transaction = $this->orderTransactionRepository->search($criteria, $this->context)->getEntities()->first();
        static::assertNotNull($transaction);

        $states = $this->stateMachineRegistry->getStateMachine(OrderTransactionStates::STATE_MACHINE, $this->context)->getStates();
        static::assertNotNull($states);
        $actualState = $states->get($transaction->getStateId());
        static::assertNotNull($actualState);
        static::assertSame($state, $actualState->getTechnicalName());
    }

    protected function assertRefundState(string $state, string $refundId): void
    {
        $criteria = new Criteria([$refundId]);
        $criteria->addAssociation('state');

        $refund = $this->orderTransactionCaptureRefundRepository->search($criteria, $this->context)->getEntities()->first();
        static::assertNotNull($refund);

        $states = $this->stateMachineRegistry->getStateMachine(OrderTransactionCaptureRefundStates::STATE_MACHINE, $this->context)->getStates();
        static::assertNotNull($states);
        $actualState = $states->get($refund->getStateId());
        static::assertNotNull($actualState);
        static::assertSame($state, $actualState->getTechnicalName());
    }
}
