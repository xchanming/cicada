<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Controller;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderException;
use Cicada\Core\Checkout\Order\SalesChannel\AbstractCancelOrderRoute;
use Cicada\Core\Checkout\Order\SalesChannel\AbstractOrderRoute;
use Cicada\Core\Checkout\Order\SalesChannel\AbstractSetPaymentOrderRoute;
use Cicada\Core\Checkout\Order\SalesChannel\OrderRouteResponse;
use Cicada\Core\Checkout\Order\SalesChannel\OrderService;
use Cicada\Core\Checkout\Payment\SalesChannel\AbstractHandlePaymentMethodRoute;
use Cicada\Core\Checkout\Payment\SalesChannel\HandlePaymentMethodRouteResponse;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Exception\InvalidUuidException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Currency\CurrencyEntity;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Cicada\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Storefront\Controller\AccountOrderController;
use Cicada\Storefront\Page\Account\Order\AccountEditOrderPageLoader;
use Cicada\Storefront\Page\Account\Order\AccountOrderDetailPageLoader;
use Cicada\Storefront\Page\Account\Order\AccountOrderPageLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[CoversClass(AccountOrderController::class)]
class AccountOrderControllerTest extends TestCase
{
    private AccountOrderControllerTestClass $controller;

    private MockObject&AbstractOrderRoute $orderRouteMock;

    private MockObject&AccountEditOrderPageLoader $accountEditOrderPageLoaderMock;

    private MockObject&AbstractHandlePaymentMethodRoute $handlePaymentRouteMock;

    private MockObject&OrderService $orderServiceMock;

    protected function setUp(): void
    {
        $this->orderRouteMock = $this->createMock(AbstractOrderRoute::class);
        $this->accountEditOrderPageLoaderMock = $this->createMock(AccountEditOrderPageLoader::class);
        $this->handlePaymentRouteMock = $this->createMock(AbstractHandlePaymentMethodRoute::class);
        $this->orderServiceMock = $this->createPartialMock(OrderService::class, ['__construct']);

        $this->controller = new AccountOrderControllerTestClass(
            $this->createMock(AccountOrderPageLoader::class),
            $this->accountEditOrderPageLoaderMock,
            $this->createMock(AbstractContextSwitchRoute::class),
            $this->createMock(AbstractCancelOrderRoute::class),
            $this->createMock(AbstractSetPaymentOrderRoute::class),
            $this->handlePaymentRouteMock,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(AccountOrderDetailPageLoader::class),
            $this->orderRouteMock,
            $this->createMock(SalesChannelContextServiceInterface::class),
            $this->createMock(SystemConfigService::class),
            $this->orderServiceMock,
        );
    }

    public function testEditOrderNotFound(): void
    {
        $ids = new IdsCollection();

        $response = $this->controller->editOrder($ids->get('order'), new Request(), Generator::generateSalesChannelContext());

        // Ensure flash massage is shown
        static::assertEquals(['danger' => ['error.CHECKOUT__ORDER_ORDER_NOT_FOUND']], $this->controller->flashBag);
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        static::assertEquals('frontend.account.order.page', $response->getTargetUrl());
    }

    public function testEditOrderInvalidUuid(): void
    {
        // Ensure invalid uuid exception is thrown
        $this->orderRouteMock->method('load')->willThrowException(new InvalidUuidException('invalid-id'));

        $response = $this->controller->editOrder('invalid-id', new Request(), Generator::generateSalesChannelContext());

        // Ensure flash massage is shown
        static::assertEquals(['danger' => ['error.CHECKOUT__ORDER_ORDER_NOT_FOUND']], $this->controller->flashBag);
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        static::assertEquals('frontend.account.order.page', $response->getTargetUrl());
    }

    public function testOrderAlreadyPaid(): void
    {
        $ids = new IdsCollection();

        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->assign([
            'currency' => (new CurrencyEntity())->assign([
                'id' => $ids->get('currency'),
            ]),
        ]);

        $order = (new OrderEntity())->assign([
            '_uniqueIdentifier' => Uuid::randomHex(),
            'currencyId' => $ids->get('currency'),
            'deliveries' => new OrderDeliveryCollection(),
        ]);
        $orders = new OrderCollection([$order]);

        $accountRouteResponse = new OrderRouteResponse(
            new EntitySearchResult(
                OrderDefinition::ENTITY_NAME,
                1,
                $orders,
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $dispatcher = static::createMock(EventDispatcherInterface::class);

        $container = new ContainerBuilder();
        $container->set('event_dispatcher', $dispatcher);

        $this->controller->setContainer($container);

        $this->orderRouteMock->method('load')->willReturn($accountRouteResponse);
        $this->accountEditOrderPageLoaderMock->method('load')->willThrowException(OrderException::orderAlreadyPaid($ids->get('order')));

        $response = $this->controller->editOrder($ids->get('order'), new Request(), $salesChannelContext);

        // Ensure flash massage is shown
        static::assertEquals(['danger' => ['error.CHECKOUT__ORDER_ORDER_ALREADY_PAID']], $this->controller->flashBag);
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        static::assertEquals('frontend.account.order.page', $response->getTargetUrl());
    }

    public function testCancelOrderRedirectsToCorrectRouteForLoggedInCustomer(): void
    {
        $salesChannelContextMock = $this->createMock(SalesChannelContext::class);

        $customer = new CustomerEntity();
        $customer->setGuest(false);
        $salesChannelContextMock->method('getCustomer')->willReturn($customer);

        $request = new Request();
        $request->attributes->set('orderId', Uuid::randomHex());

        $response = $this->controller->cancelOrder($request, $salesChannelContextMock);

        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertEquals('frontend.account.order.page', $response->getTargetUrl());
    }

    public function testCancelOrderRedirectsToCorrectRouteForGuestCustomer(): void
    {
        $salesChannelContextMock = $this->createMock(SalesChannelContext::class);

        $customer = new CustomerEntity();
        $customer->setGuest(true);
        $salesChannelContextMock->method('getCustomer')->willReturn($customer);

        $request = new Request();
        $request->attributes->set('orderId', Uuid::randomHex());
        $request->attributes->set('deepLinkCode', 'deep-link-code');

        $response = $this->controller->cancelOrder($request, $salesChannelContextMock);

        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertEquals('frontend.account.order.single.page', $response->getTargetUrl());
    }

    public function testTransactionsStateMachineAssociationIsLoadedOnOrderUpdate(): void
    {
        $ids = new IdsCollection();

        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->assign([
            'currency' => (new CurrencyEntity())->assign([
                'id' => $ids->get('currency'),
            ]),
        ]);

        $criteria = new Criteria([$ids->get('order')]);
        $criteria->addAssociation('transactions.stateMachineState');

        $stateMachineState = new StateMachineStateEntity();
        $stateMachineState->setTechnicalName(OrderTransactionStates::STATE_CANCELLED);

        $transaction = new OrderTransactionEntity();
        $transaction->setId($ids->get('transaction'));
        $transaction->setStateMachineState($stateMachineState);

        // Mock the OrderEntity with transactions
        $order = new OrderEntity();
        $order->setId($ids->get('order'));
        $order->setCurrencyId($ids->get('currency'));
        $order->setDeliveries(new OrderDeliveryCollection());
        $order->setTransactions(new OrderTransactionCollection([$transaction]));

        $orders = new OrderCollection([$order]);

        $accountRouteResponse = new OrderRouteResponse(
            new EntitySearchResult(
                OrderDefinition::ENTITY_NAME,
                1,
                $orders,
                null,
                $criteria,
                $salesChannelContext->getContext()
            )
        );

        $this->orderRouteMock
            ->expects(static::once())
            ->method('load')
            ->with($request = new Request(), $salesChannelContext, $criteria)
            ->willReturn($accountRouteResponse);

        $this->handlePaymentRouteMock
            ->expects(static::once())
            ->method('load')
            ->with(static::isInstanceOf(Request::class), $salesChannelContext)
            ->willReturn(new HandlePaymentMethodRouteResponse(new RedirectResponse('http://doesnotexist.com')));

        $this->controller->updateOrder($ids->get('order'), $request, $salesChannelContext);
    }
}

/**
 * @internal
 */
class AccountOrderControllerTestClass extends AccountOrderController
{
    use StorefrontControllerMockTrait;
}
