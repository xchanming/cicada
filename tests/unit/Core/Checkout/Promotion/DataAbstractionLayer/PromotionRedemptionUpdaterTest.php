<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Promotion\DataAbstractionLayer;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Cicada\Core\Checkout\Promotion\DataAbstractionLayer\PromotionRedemptionUpdater;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWriteEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PromotionRedemptionUpdater::class)]
class PromotionRedemptionUpdaterTest extends TestCase
{
    private Connection&MockObject $connectionMock;

    private PromotionRedemptionUpdater $promotionRedemptionUpdater;

    protected function setUp(): void
    {
        $this->connectionMock = $this->createMock(Connection::class);
        $this->promotionRedemptionUpdater = new PromotionRedemptionUpdater($this->connectionMock);
    }

    public function getDefinition(): OrderLineItemDefinition
    {
        new StaticDefinitionInstanceRegistry(
            [$definition = new OrderLineItemDefinition()],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        return $definition;
    }

    public function testUpdateEmptyIds(): void
    {
        $this->connectionMock->expects(static::never())->method('fetchAllAssociative');
        $this->promotionRedemptionUpdater->update([], Context::createDefaultContext());
    }

    public function testUpdateValidCase(): void
    {
        $promotionId = Uuid::randomHex();
        $customerId = Uuid::randomHex();

        $this->connectionMock->method('fetchAllAssociative')->willReturn([
            ['promotion_id' => $promotionId, 'total' => 1, 'customer_id' => $customerId],
        ]);

        $statementMock = $this->createMock(Statement::class);
        $statementMock->expects(static::once())
            ->method('executeStatement')
            ->with(static::equalTo([
                'id' => Uuid::fromHexToBytes($promotionId),
                'customerCount' => json_encode([$customerId => 1], \JSON_THROW_ON_ERROR),
                'count' => 1,
            ]));
        $this->connectionMock->method('prepare')->willReturn($statementMock);

        $this->promotionRedemptionUpdater->update([$promotionId], Context::createDefaultContext());
    }

    public function testOrderPlacedUpdatesPromotionsCorrectly(): void
    {
        $promotionId = Uuid::randomHex();
        $customerId = Uuid::randomHex();

        $event = $this->createOrderPlacedEvent($promotionId, $customerId);

        $statementMock = $this->createMock(Statement::class);
        $statementMock->expects(static::once())
            ->method('executeStatement')
            ->with(static::equalTo([
                'id' => Uuid::fromHexToBytes($promotionId),
                'customerCount' => json_encode([$customerId => 1], \JSON_THROW_ON_ERROR),
            ]));

        $this->connectionMock->method('prepare')->willReturn($statementMock);

        $this->promotionRedemptionUpdater->orderPlaced($event);
    }

    public function testOrderPlacedNoLineItemsOrCustomer(): void
    {
        $event = $this->createOrderPlacedEvent(null, null);

        $this->connectionMock->expects(static::never())->method('fetchAllAssociative');
        $this->promotionRedemptionUpdater->orderPlaced($event);
    }

    public function testUpdateCalledBeforeOrderPlacedDoesNotRepeatUpdate(): void
    {
        $promotionId = Uuid::randomHex();
        $customerId = Uuid::randomHex();

        $this->connectionMock->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            [
                [
                    'promotion_id' => $promotionId,
                    'total' => 1,
                    'customer_id' => $customerId,
                ],
            ],
            [
                [
                    'id' => Uuid::fromHexToBytes($promotionId),
                    'orders_per_customer_count' => json_encode([$customerId => 1]),
                ],
            ]
        );

        $statementMock = $this->createMock(Statement::class);
        $statementMock->expects(static::once())->method('executeStatement')->with([
            'id' => Uuid::fromHexToBytes($promotionId),
            'customerCount' => json_encode([$customerId => 1], \JSON_THROW_ON_ERROR),
            'count' => 1,
        ]);
        $this->connectionMock->method('prepare')->willReturn($statementMock);

        $this->promotionRedemptionUpdater->update([$promotionId], Context::createDefaultContext());

        $event = $this->createOrderPlacedEvent($promotionId, $customerId);

        // Expect no further update calls during orderPlaced
        $statementMock = $this->createMock(Statement::class);
        $statementMock->expects(static::never())->method('executeStatement');
        $this->connectionMock->method('prepare')->willReturn($statementMock);

        $this->promotionRedemptionUpdater->orderPlaced($event);
    }

    public function testBeforeDeletePromotionLineItems(): void
    {
        $customerId = Uuid::randomHex();
        $promotionId = Uuid::randomHex();

        $id = Uuid::randomBytes();
        $command = new DeleteCommand(
            $this->getDefinition(),
            ['id' => $id],
            $this->createMock(EntityExistence::class),
        );

        $event = EntityWriteEvent::create(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [$command],
        );

        $this->connectionMock->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            [
                [
                    'promotion_id' => $promotionId,
                    'payload' => '{"code": "F1D6Y0X2"}',
                    'total' => 1,
                    'customer_id' => $customerId,
                ],
            ],
            [
                [
                    'id' => Uuid::fromHexToBytes($promotionId),
                    'orders_per_customer_count' => json_encode([$customerId => 1]),
                ],
            ]
        );

        $statementMock = $this->createMock(Statement::class);
        $statementMock->expects(static::once())
            ->method('executeStatement')
            ->with(static::equalTo([
                'id' => Uuid::fromHexToBytes($promotionId),
                'customerCount' => json_encode([], \JSON_THROW_ON_ERROR),
                'orderCount' => 1,
            ]));

        $this->connectionMock->method('prepare')->willReturn($statementMock);
        $this->connectionMock->expects(static::once())
            ->method('executeStatement')
            ->with(static::equalTo('UPDATE promotion_individual_code set payload = NULL WHERE code IN (:codes)'))
            ->willReturnCallback(function ($query, $params): void {
                static::assertSame(['codes' => ['F1D6Y0X2']], $params);
            });

        $this->promotionRedemptionUpdater->beforeDeletePromotionLineItems($event);

        $event->success();
    }

    public function testBeforeDeletePromotionLineItemsWithoutPromotion(): void
    {
        $id = Uuid::randomBytes();
        $command = new DeleteCommand(
            $this->getDefinition(),
            ['id' => $id],
            $this->createMock(EntityExistence::class),
        );

        $event = EntityWriteEvent::create(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [$command],
        );

        $this->connectionMock->expects(static::once())->method('fetchAllAssociative')->willReturn([]);

        $this->promotionRedemptionUpdater->beforeDeletePromotionLineItems($event);
    }

    public function testBeforeDeletePromotionLineItemsWithInsertCommand(): void
    {
        $command = new InsertCommand(
            $this->getDefinition(),
            ['order_id' => Uuid::randomBytes()],
            ['id' => Uuid::randomBytes()],
            $this->createMock(EntityExistence::class),
            '/0'
        );

        $event = EntityWriteEvent::create(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [$command],
        );

        $this->connectionMock->expects(static::never())->method('fetchAllAssociative');
        $this->promotionRedemptionUpdater->beforeDeletePromotionLineItems($event);
    }

    public function testBeforeDeletePromotionLineItemsWithUpdateCommand(): void
    {
        $command = new UpdateCommand(
            $this->getDefinition(),
            ['order_id' => Uuid::randomBytes()],
            ['id' => Uuid::randomBytes()],
            $this->createMock(EntityExistence::class),
            '/0'
        );

        $event = EntityWriteEvent::create(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [$command],
        );

        $this->connectionMock->expects(static::never())->method('fetchAllAssociative');
        $this->promotionRedemptionUpdater->beforeDeletePromotionLineItems($event);
    }

    private function createOrderPlacedEvent(?string $promotionId, ?string $customerId): CheckoutOrderPlacedEvent
    {
        $lineItems = new OrderLineItemCollection();
        $order = new OrderEntity();
        if ($promotionId !== null) {
            $lineItem = new OrderLineItemEntity();
            $lineItem->setId(Uuid::randomHex());
            $lineItem->setType(PromotionProcessor::LINE_ITEM_TYPE);
            $lineItem->setPromotionId($promotionId);
            $lineItems->add($lineItem);
            $order->setLineItems($lineItems);
        }

        if ($customerId !== null) {
            $orderCustomer = new OrderCustomerEntity();
            $orderCustomer->setId(Uuid::randomHex());
            $orderCustomer->setCustomerId($customerId);
            $order->setOrderCustomer($orderCustomer);
        }

        $context = Generator::generateSalesChannelContext();

        return new CheckoutOrderPlacedEvent($context, $order);
    }
}
