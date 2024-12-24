<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Order\SalesChannel;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Order\SalesChannel\OrderService;
use Cicada\Core\Checkout\Order\Validation\OrderValidationFactory;
use Cicada\Core\Content\Product\State;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\Framework\Validation\Exception\ConstraintViolationException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\StateMachine\StateMachineRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 */
#[CoversClass(OrderService::class)]
class OrderServiceTest extends TestCase
{
    private MockObject&CartService $cartService;

    private MockObject&EntityRepository $paymentMethodRepository;

    private OrderService $orderService;

    protected function setUp(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->cartService = $this->createMock(CartService::class);
        $this->paymentMethodRepository = $this->createMock(EntityRepository::class);
        $stateMachineRegistry = $this->createMock(StateMachineRegistry::class);

        $this->orderService = new OrderService(
            new DataValidator(Validation::createValidatorBuilder()->getValidator()),
            new OrderValidationFactory(),
            $eventDispatcher,
            $this->cartService,
            $this->paymentMethodRepository,
            $stateMachineRegistry
        );
    }

    public function testCreateOrderWithDigitalGoodsNeedsRevocationConfirm(): void
    {
        $dataBag = new DataBag();
        $dataBag->set('tos', true);
        $context = $this->createMock(SalesChannelContext::class);

        $cart = new Cart('test');
        $cart->add((new LineItem('a', 'test'))->setStates([State::IS_PHYSICAL]));

        $this->cartService->method('getCart')->willReturn($cart);
        $this->cartService->expects(static::exactly(2))->method('order');

        $idSearchResult = new IdSearchResult(0, [], new Criteria(), Context::createDefaultContext());
        $this->paymentMethodRepository->method('searchIds')->willReturn($idSearchResult);

        $this->orderService->createOrder($dataBag, $context);

        $cart->add((new LineItem('b', 'test'))->setStates([State::IS_DOWNLOAD]));

        try {
            $this->orderService->createOrder($dataBag, $context);

            static::fail('Did not throw exception');
        } catch (\Throwable $exception) {
            static::assertInstanceOf(ConstraintViolationException::class, $exception);
            $errors = iterator_to_array($exception->getErrors());
            static::assertCount(1, $errors);
            static::assertEquals('VIOLATION::IS_BLANK_ERROR', $errors[0]['code']);
            static::assertEquals('/revocation', $errors[0]['source']['pointer']);
        }

        $dataBag->set('revocation', true);

        $this->orderService->createOrder($dataBag, $context);
    }
}
