<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Order\Subscriber;

use Cicada\Core\Checkout\Order\OrderEvents;
use Cicada\Core\Checkout\Order\Subscriber\OrderSalutationSubscriber;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(OrderSalutationSubscriber::class)]
class OrderSalutationSubscriberTest extends TestCase
{
    private MockObject&Connection $connection;

    private OrderSalutationSubscriber $salutationSubscriber;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $this->salutationSubscriber = new OrderSalutationSubscriber($this->connection);
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertEquals([
            OrderEvents::ORDER_ADDRESS_WRITTEN_EVENT => 'setDefaultSalutation',
            OrderEvents::ORDER_CUSTOMER_WRITTEN_EVENT => 'setDefaultSalutation',
        ], $this->salutationSubscriber->getSubscribedEvents());
    }

    public function testSkip(): void
    {
        $writeResults = [
            new EntityWriteResult(
                'created-id',
                ['id' => Uuid::randomHex(), 'salutationId' => Uuid::randomHex()],
                'order_address',
                EntityWriteResult::OPERATION_INSERT
            ),
        ];

        $event = new EntityWrittenEvent(
            'order_address',
            $writeResults,
            Context::createDefaultContext(),
            [],
        );

        $this->connection->expects(static::never())->method('executeUpdate');

        $this->salutationSubscriber->setDefaultSalutation($event);
    }

    public function testDefaultSalutation(): void
    {
        $orderAddressId = Uuid::randomHex();

        $writeResults = [new EntityWriteResult('created-id', ['id' => $orderAddressId], 'order_address', EntityWriteResult::OPERATION_INSERT)];

        $event = new EntityWrittenEvent(
            'order_address',
            $writeResults,
            Context::createDefaultContext(),
            [],
        );

        $this->connection->expects(static::once())
            ->method('executeStatement')
            ->willReturnCallback(function ($sql, $params) use ($orderAddressId): void {
                static::assertSame($params, [
                    'id' => Uuid::fromHexToBytes($orderAddressId),
                    'notSpecified' => 'not_specified',
                ]);

                static::assertSame('
                UPDATE `order_address`
                SET `salutation_id` = (
                    SELECT `id`
                    FROM `salutation`
                    WHERE `salutation_key` = :notSpecified
                    LIMIT 1
                )
                WHERE `id` = :id AND `salutation_id` is NULL
            ', $sql);
            });

        $this->salutationSubscriber->setDefaultSalutation($event);
    }
}
