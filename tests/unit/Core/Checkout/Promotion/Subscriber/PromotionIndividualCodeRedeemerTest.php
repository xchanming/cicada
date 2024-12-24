<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Promotion\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeCollection;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeEntity;
use Cicada\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Cicada\Core\Checkout\Promotion\Subscriber\PromotionIndividualCodeRedeemer;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(PromotionIndividualCodeRedeemer::class)]
class PromotionIndividualCodeRedeemerTest extends TestCase
{
    /**
     * This test verifies that our subscriber has the
     * correct event that its listening to.
     * This is important, because we have to ensure that
     * we save meta data in the payload of the line item
     * when the order is created.
     * This payload data helps us to reference used individual codes
     * with placed orders.
     */
    #[Group('promotions')]
    public function testSubscribeToOrderLineItemWritten(): void
    {
        $expectedEvent = CheckoutOrderPlacedEvent::class;

        // we need to have a key for the Cicada event
        static::assertArrayHasKey($expectedEvent, PromotionIndividualCodeRedeemer::getSubscribedEvents());
    }

    public function testOnOrderCreateWithOtherLineItem(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(static::never())->method('search');
        $repository->expects(static::never())->method('searchIds');
        $redeemer = new PromotionIndividualCodeRedeemer($repository);

        $lineItem = new OrderLineItemEntity();
        $lineItem->setId(Uuid::randomHex());
        $lineItem->setType('test');
        $order = new OrderEntity();
        $order->setLineItems(new OrderLineItemCollection([$lineItem]));

        $context = Generator::createSalesChannelContext();

        $event = new CheckoutOrderPlacedEvent($context, $order);

        $redeemer->onOrderPlaced($event);
    }

    public function testOnOrderCreateWillProcessMultipleCodes(): void
    {
        $code = new PromotionIndividualCodeEntity();
        $code->setId(Uuid::randomHex());

        /** @var StaticEntityRepository<PromotionIndividualCodeCollection> $repository */
        $repository = new StaticEntityRepository([
            static function (Criteria $criteria) {
                $filter = $criteria->getFilters()[0];
                static::assertInstanceOf(EqualsFilter::class, $filter);
                static::assertSame('notexisting', $filter->getValue());

                return new PromotionIndividualCodeCollection();
            },
            static function (Criteria $criteria) use ($code) {
                $filter = $criteria->getFilters()[0];
                static::assertInstanceOf(EqualsFilter::class, $filter);
                static::assertSame('existing', $filter->getValue());

                return new PromotionIndividualCodeCollection([$code]);
            },
        ]);
        $redeemer = new PromotionIndividualCodeRedeemer($repository);

        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $customer = new OrderCustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setFirstName('foo');
        $customer->setLastName('bar');
        $customer->setCustomerId(Uuid::randomHex());
        $order->setOrderCustomer($customer);

        $lineItem1 = new OrderLineItemEntity();
        $lineItem1->setId(Uuid::randomHex());
        $lineItem1->setType(PromotionProcessor::LINE_ITEM_TYPE);
        $lineItem1->setPayload(['code' => 'notexisting']);
        $lineItem1->setOrderId($order->getId());

        $lineItem2 = new OrderLineItemEntity();
        $lineItem2->setId(Uuid::randomHex());
        $lineItem2->setType(PromotionProcessor::LINE_ITEM_TYPE);
        $lineItem2->setPayload(['code' => 'existing']);
        $lineItem2->setOrderId($order->getId());

        $order->setLineItems(new OrderLineItemCollection([$lineItem1, $lineItem2]));

        $context = Generator::createSalesChannelContext();

        $event = new CheckoutOrderPlacedEvent($context, $order);

        $redeemer->onOrderPlaced($event);

        static::assertSame([[[
            'id' => $code->getId(),
            'payload' => [
                'orderId' => $order->getId(),
                'customerId' => $customer->getCustomerId(),
                'customerName' => 'foo bar',
            ],
        ]]], $repository->updates);
    }
}
