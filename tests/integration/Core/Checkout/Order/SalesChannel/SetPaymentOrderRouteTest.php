<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Order\SalesChannel;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Cicada\Core\Checkout\Order\Event\OrderPaymentMethodChangedCriteriaEvent;
use Cicada\Core\Checkout\Order\Event\OrderPaymentMethodChangedEvent;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\MailTemplateTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class SetPaymentOrderRouteTest extends TestCase
{
    use CustomerTestTrait;
    use IntegrationTestBehaviour;
    use MailTemplateTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    private ?OrderPaymentMethodChangedEvent $paymentMethodChangedEventResult;

    private ?OrderPaymentMethodChangedCriteriaEvent $paymentMethodChangedCriteriaEventResult;

    private ?StateMachineTransitionEvent $transactionStateEventResult;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
            'paymentMethods' => [
                ['id' => $this->getAvailablePaymentMethodId()],
                ['id' => $this->getAvailablePaymentMethodId(1)],
            ],
        ]);

        $this->assignSalesChannelContext($this->browser);

        $email = Uuid::randomHex() . '@example.com';
        $customerId = $this->createCustomer($email);

        $this->ids->set('order-1', $this->createOrder($customerId));
        $this->ids->set('order-2', $this->createOrder($this->createCustomer('test-other@test.de')));

        $this->browser->request(
            'POST',
            '/store-api/account/login',
            [
                'email' => $email,
                'password' => '12345678',
            ]
        );
        $response = $this->browser->getResponse();

        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);

        $this->paymentMethodChangedEventResult = null;
        $this->catchEvent(OrderPaymentMethodChangedEvent::class, $this->paymentMethodChangedEventResult);

        $this->paymentMethodChangedCriteriaEventResult = null;
        $this->catchEvent(OrderPaymentMethodChangedCriteriaEvent::class, $this->paymentMethodChangedCriteriaEventResult);

        $this->transactionStateEventResult = null;
        $this->catchEvent(StateMachineTransitionEvent::class, $this->transactionStateEventResult);
    }

    public function testSetPaymentMethodOwnOrderOtherPaymentMethodOpen(): void
    {
        $this->sendValidRequest($this->ids->get('order-1'), $this->getAvailablePaymentMethodId(1));
        $transactions = $this->getTransactions($this->ids->get('order-1'));
        static::assertCount(2, $transactions);
        $firstTransaction = $transactions->first();
        static::assertNotNull($firstTransaction);
        $lastTransaction = $transactions->last();
        static::assertNotNull($lastTransaction);
        static::assertNotSame($firstTransaction->getId(), $lastTransaction->getId());

        static::assertNotNull($firstTransaction->getStateMachineState());
        static::assertNotNull($lastTransaction->getStateMachineState());
        static::assertSame('cancelled', $firstTransaction->getStateMachineState()->getTechnicalName());
        static::assertSame('open', $lastTransaction->getStateMachineState()->getTechnicalName());

        static::assertNotNull($this->paymentMethodChangedCriteriaEventResult);
        static::assertNotNull($this->paymentMethodChangedEventResult);
        static::assertSame($lastTransaction->getId(), $this->paymentMethodChangedEventResult->getOrderTransaction()->getId());
        static::assertNotNull($this->transactionStateEventResult);
        static::assertSame($firstTransaction->getId(), $this->transactionStateEventResult->getEntityId());
        static::assertSame('open', $this->transactionStateEventResult->getFromPlace()->getTechnicalName());
        static::assertSame('cancelled', $this->transactionStateEventResult->getToPlace()->getTechnicalName());
    }

    public function testSetPaymentMethodOwnOrderOtherPaymentMethodCancelled(): void
    {
        $this->setFirstTransactionState($this->ids->get('order-1'));
        $this->sendValidRequest($this->ids->get('order-1'), $this->getAvailablePaymentMethodId(1));
        $transactions = $this->getTransactions($this->ids->get('order-1'));
        static::assertCount(2, $transactions);
        $firstTransaction = $transactions->first();
        static::assertNotNull($firstTransaction);
        $lastTransaction = $transactions->last();
        static::assertNotNull($lastTransaction);
        static::assertNotSame($firstTransaction->getId(), $lastTransaction->getId());

        static::assertNotNull($firstTransaction->getStateMachineState());
        static::assertNotNull($lastTransaction->getStateMachineState());
        static::assertSame('cancelled', $firstTransaction->getStateMachineState()->getTechnicalName());
        static::assertSame('open', $lastTransaction->getStateMachineState()->getTechnicalName());

        static::assertNotNull($this->paymentMethodChangedEventResult);
        static::assertNotNull($this->paymentMethodChangedCriteriaEventResult);
        static::assertSame($lastTransaction->getId(), $this->paymentMethodChangedEventResult->getOrderTransaction()->getId());
        static::assertNull($this->transactionStateEventResult);
    }

    public function testSetPaymentMethodOwnOrderWithSamePaymentMethodOpen(): void
    {
        $this->sendValidRequest($this->ids->get('order-1'), $this->getAvailablePaymentMethodId());

        $transactions = $this->getTransactions($this->ids->get('order-1'));
        static::assertCount(1, $transactions);
        $lastTransaction = $transactions->last();
        static::assertNotNull($lastTransaction);

        static::assertNotNull($lastTransaction->getStateMachineState());
        static::assertSame('open', $lastTransaction->getStateMachineState()->getTechnicalName());
        static::assertNotNull($this->paymentMethodChangedCriteriaEventResult);
        static::assertNull($this->paymentMethodChangedEventResult);
        static::assertNull($this->transactionStateEventResult);
    }

    public function testSetPaymentMethodOwnOrderWithSamePaymentMethodCancelled(): void
    {
        $this->setFirstTransactionState($this->ids->get('order-1'));
        $this->sendValidRequest($this->ids->get('order-1'), $this->getAvailablePaymentMethodId());

        $transactions = $this->getTransactions($this->ids->get('order-1'));
        static::assertCount(1, $transactions);
        $lastTransaction = $transactions->last();
        static::assertNotNull($lastTransaction);

        static::assertNotNull($lastTransaction->getStateMachineState());
        static::assertSame('open', $lastTransaction->getStateMachineState()->getTechnicalName());
        static::assertNotNull($this->paymentMethodChangedCriteriaEventResult);
        static::assertNull($this->paymentMethodChangedEventResult);
        static::assertNotNull($this->transactionStateEventResult);
        static::assertSame($lastTransaction->getId(), $this->transactionStateEventResult->getEntityId());
        static::assertSame('cancelled', $this->transactionStateEventResult->getFromPlace()->getTechnicalName());
        static::assertSame('open', $this->transactionStateEventResult->getToPlace()->getTechnicalName());
    }

    public function testSetPaymentMethodOwnOrderWithSamePaymentMethodInNotMostRecentTransaction(): void
    {
        $this->sendValidRequest($this->ids->get('order-1'), $this->getAvailablePaymentMethodId(1));
        $this->setFirstTransactionState($this->ids->get('order-1'), OrderTransactionStates::STATE_OPEN);
        $this->sendValidRequest($this->ids->get('order-1'), $this->getAvailablePaymentMethodId());

        $transactions = $this->getTransactions($this->ids->get('order-1'));
        static::assertCount(3, $transactions);
        $firstTransaction = $transactions->first();
        static::assertNotNull($firstTransaction);
        $lastTransaction = $transactions->last();
        static::assertNotNull($lastTransaction);

        $paymentMethodChangedCriteriaEventResult = $this->paymentMethodChangedCriteriaEventResult;
        $paymentMethodChangedEventResult = $this->paymentMethodChangedEventResult;
        $transactionStateEventResult = $this->transactionStateEventResult;
        static::assertNotNull($paymentMethodChangedEventResult);
        static::assertNotNull($transactionStateEventResult);
        static::assertNotNull($paymentMethodChangedCriteriaEventResult);
        static::assertNotNull($lastTransaction->getStateMachineState());
        static::assertSame('open', $lastTransaction->getStateMachineState()->getTechnicalName());
        static::assertSame($lastTransaction->getId(), $paymentMethodChangedEventResult->getOrderTransaction()->getId());
        static::assertNotSame($firstTransaction->getId(), $transactionStateEventResult->getEntityId());
        static::assertNotSame($lastTransaction->getId(), $transactionStateEventResult->getEntityId());
        static::assertSame('open', $transactionStateEventResult->getFromPlace()->getTechnicalName());
        static::assertSame('cancelled', $transactionStateEventResult->getToPlace()->getTechnicalName());
    }

    public function testSetPaymentMethodRandomOrder(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/order/payment',
                [
                    'orderId' => Uuid::randomHex(),
                    'paymentMethodId' => $this->getAvailablePaymentMethodId(1),
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(Response::HTTP_NOT_FOUND, $this->browser->getResponse()->getStatusCode());
        static::assertSame('FRAMEWORK__ENTITY_NOT_FOUND', $response['errors'][0]['code']);
        static::assertNotNull($this->paymentMethodChangedCriteriaEventResult);
        static::assertNull($this->paymentMethodChangedEventResult);
        static::assertNull($this->transactionStateEventResult);
    }

    public function testSetPaymentMethodOtherUsersOrder(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/order/payment',
                [
                    'orderId' => $this->ids->get('order-2'),
                    'paymentMethodId' => $this->getAvailablePaymentMethodId(1),
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(Response::HTTP_NOT_FOUND, $this->browser->getResponse()->getStatusCode());
        static::assertSame('FRAMEWORK__ENTITY_NOT_FOUND', $response['errors'][0]['code']);
        static::assertNotNull($this->paymentMethodChangedCriteriaEventResult);
        static::assertNull($this->paymentMethodChangedEventResult);
        static::assertNull($this->transactionStateEventResult);
    }

    public function testSetPaymentMethodWithoutLogin(): void
    {
        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => Uuid::randomHex(),
        ]);

        $this->browser
            ->request(
                'POST',
                '/store-api/order/payment',
                [
                    'orderId' => $this->ids->get('order-2'),
                    'paymentMethodId' => $this->getAvailablePaymentMethodId(1),
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(Response::HTTP_FORBIDDEN, $this->browser->getResponse()->getStatusCode());
        static::assertSame(RoutingException::CUSTOMER_NOT_LOGGED_IN_CODE, $response['errors'][0]['code']);
        static::assertNull($this->paymentMethodChangedCriteriaEventResult);
        static::assertNull($this->paymentMethodChangedEventResult);
        static::assertNull($this->transactionStateEventResult);
    }

    public function testSetPaymentMethodValidatePaymentStateValidChange(): void
    {
        $this->sendValidRequest($this->ids->get('order-1'), $this->getAvailablePaymentMethodId(1));
        $transactions = $this->getTransactions($this->ids->get('order-1'));
        static::assertCount(2, $transactions);
        $firstTransaction = $transactions->first();
        static::assertNotNull($firstTransaction);
        $lastTransaction = $transactions->last();
        static::assertNotNull($lastTransaction);
        static::assertNotSame($firstTransaction->getId(), $lastTransaction->getId());

        static::assertNotNull($firstTransaction->getStateMachineState());
        static::assertNotNull($lastTransaction->getStateMachineState());
        static::assertSame('cancelled', $firstTransaction->getStateMachineState()->getTechnicalName());
        static::assertSame('open', $lastTransaction->getStateMachineState()->getTechnicalName());

        static::assertNotNull($this->paymentMethodChangedCriteriaEventResult);
        static::assertNotNull($this->paymentMethodChangedEventResult);
        static::assertSame($lastTransaction->getId(), $this->paymentMethodChangedEventResult->getOrderTransaction()->getId());
        static::assertNotNull($this->transactionStateEventResult);
        static::assertSame($firstTransaction->getId(), $this->transactionStateEventResult->getEntityId());
        static::assertSame('open', $this->transactionStateEventResult->getFromPlace()->getTechnicalName());
        static::assertSame('cancelled', $this->transactionStateEventResult->getToPlace()->getTechnicalName());
    }

    public function testSetPaymentMethodValidatePaymentStateInvalidChange(): void
    {
        $orderId = $this->ids->get('order-1');
        $this->setFirstTransactionState($orderId, OrderTransactionStates::STATE_AUTHORIZED);

        $this->browser
            ->request(
                'POST',
                '/store-api/order/payment',
                [
                    'orderId' => $orderId,
                    'paymentMethodId' => $this->getAvailablePaymentMethodId(1),
                ]
            );

        static::assertSame(Response::HTTP_BAD_REQUEST, $this->browser->getResponse()->getStatusCode());
    }

    private function createOrder(string $customerId): string
    {
        $id = Uuid::randomHex();

        static::getContainer()->get('order.repository')->create(
            [[
                'id' => $id,
                'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
                'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
                'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
                'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
                'orderCustomer' => [
                    'customerId' => $customerId,
                    'email' => 'test@example.com',
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => 'Max',
                ],
                'orderNumber' => 'anOrderNumber',
                'stateId' => $this->getStateMachineState(),
                'currencyId' => Defaults::CURRENCY,
                'currencyFactor' => 1.0,
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'billingAddressId' => $billingAddressId = Uuid::randomHex(),
                'addresses' => [
                    [
                        'id' => $billingAddressId,
                        'salutationId' => $this->getValidSalutationId(),
                        'name' => 'Max',
                        'street' => 'Ebbinghoff 10',
                        'zipcode' => '48624',
                        'countryId' => $this->getValidCountryId(),
                    ],
                ],
                'lineItems' => [
                    [
                        'id' => $this->ids->get('VoucherA'),
                        'type' => LineItem::PROMOTION_LINE_ITEM_TYPE,
                        'code' => $this->ids->get('VoucherA'),
                        'identifier' => $this->ids->get('VoucherA'),
                        'quantity' => 1,
                        'payload' => ['promotionId' => $this->ids->get('voucherA')],
                        'label' => 'label',
                        'price' => new CalculatedPrice(200, 200, new CalculatedTaxCollection(), new TaxRuleCollection()),
                        'priceDefinition' => new QuantityPriceDefinition(200, new TaxRuleCollection(), 2),
                    ],
                ],
                'deliveries' => [],
                'transactions' => [
                    [
                        'paymentMethodId' => $this->getAvailablePaymentMethodId(),
                        'stateId' => $this->getStateMachineState(OrderTransactionStates::STATE_MACHINE, OrderTransactionStates::STATE_OPEN),
                        'amount' => new CalculatedPrice(10.0, 10.0, new CalculatedTaxCollection(), new TaxRuleCollection()),
                    ],
                ],
                'context' => '{}',
                'payload' => '{}',
            ]],
            Context::createDefaultContext()
        );

        return $id;
    }

    private function sendValidRequest(string $orderId, string $paymentMethodId): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/order/payment',
                [
                    'orderId' => $orderId,
                    'paymentMethodId' => $paymentMethodId,
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertSame(Response::HTTP_OK, $this->browser->getResponse()->getStatusCode());
        static::assertTrue($response['success']);
    }

    private function getAvailablePaymentMethodId(int $offset = 0): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('payment_method.repository');

        $criteria = (new Criteria())
            ->setLimit(1)
            ->setOffset($offset)
            ->addFilter(new EqualsFilter('active', true))
            ->addFilter(new EqualsFilter('availabilityRuleId', null));

        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();
        static::assertNotNull($id);

        return $id;
    }

    private function getTransactions(string $orderId): OrderTransactionCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->addSorting(new FieldSorting('createdAt'));

        $criteria->addAssociation('stateMachineState');

        /** @var OrderTransactionCollection $transactions */
        $transactions = static::getContainer()->get('order_transaction.repository')->search($criteria, Context::createDefaultContext())->getEntities();

        return $transactions;
    }

    private function setFirstTransactionState(string $orderId, string $state = OrderTransactionStates::STATE_CANCELLED): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->addSorting(new FieldSorting('createdAt'));

        $transactionId = static::getContainer()->get('order_transaction.repository')->searchIds($criteria, Context::createDefaultContext())->firstId();
        static::getContainer()->get('order_transaction.repository')->update([[
            'id' => $transactionId,
            'stateId' => $this->getStateMachineState(OrderTransactionStates::STATE_MACHINE, $state),
        ]], Context::createDefaultContext());
    }
}
