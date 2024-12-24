<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Write\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\JsonUpdateCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[CoversClass(WriteCommandQueue::class)]
class WriteCommandQueueTest extends TestCase
{
    use KernelTestBehaviour;

    public function testCommandsInOrder(): void
    {
        /** @var DefinitionInstanceRegistry */
        $definitionRegistry = static::getContainer()->get(DefinitionInstanceRegistry::class);

        $queue = new WriteCommandQueue();

        $orderId = Uuid::randomHex();
        $orderDefinition = $definitionRegistry->getByEntityName(OrderDefinition::ENTITY_NAME);
        $orderPayload = [
            'id' => Uuid::fromHexToBytes($orderId),
            'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
        ];

        $orderDeliveryId = Uuid::randomHex();
        $orderDeliveryDefinition = $definitionRegistry->getByEntityName(OrderDeliveryDefinition::ENTITY_NAME);
        $orderDeliveryPayload = [
            'id' => Uuid::fromHexToBytes($orderDeliveryId),
            'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            'order_id' => Uuid::fromHexToBytes($orderId),
            'order_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
        ];

        $queue->add($orderDeliveryDefinition->getEntityName(), $orderDeliveryId, new UpdateCommand(
            $orderDeliveryDefinition,
            $orderDeliveryPayload,
            [
                'id' => Uuid::fromHexToBytes($orderDeliveryId),
                'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ],
            new EntityExistence(
                $orderDeliveryDefinition->getEntityName(),
                [
                    'id' => $orderDeliveryId,
                    'version_id' => Defaults::LIVE_VERSION,
                ],
                true,
                false,
                false,
                $orderDeliveryPayload
            ),
            '/0/deliveries/0'
        ));

        $queue->add($orderDeliveryDefinition->getEntityName(), $orderDeliveryId, new JsonUpdateCommand(
            $orderDeliveryDefinition,
            'custom_fields',
            [
                'test' => 'test',
            ],
            [
                'id' => Uuid::fromHexToBytes($orderDeliveryId),
                'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ],
            new EntityExistence(
                $orderDeliveryDefinition->getEntityName(),
                [
                    'id' => $orderDeliveryId,
                    'version_id' => Defaults::LIVE_VERSION,
                ],
                true,
                false,
                false,
                $orderDeliveryPayload
            ),
            '/0/deliveries/0'
        ));

        $queue->add($orderDeliveryDefinition->getEntityName(), $orderDeliveryId, new InsertCommand(
            $orderDeliveryDefinition,
            $orderDeliveryPayload,
            [
                'id' => Uuid::fromHexToBytes($orderDeliveryId),
                'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ],
            new EntityExistence(
                $orderDeliveryDefinition->getEntityName(),
                [
                    'id' => $orderDeliveryId,
                    'version_id' => Defaults::LIVE_VERSION,
                ],
                false,
                false,
                false,
                $orderDeliveryPayload
            ),
            '/0/deliveries/0'
        ));

        $queue->add($orderDefinition->getEntityName(), $orderId, new InsertCommand(
            $orderDefinition,
            $orderPayload,
            [
                'id' => Uuid::fromHexToBytes($orderId),
                'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ],
            new EntityExistence(
                $orderDefinition->getEntityName(),
                [
                    'id' => $orderId,
                    'version_id' => Defaults::LIVE_VERSION,
                ],
                false,
                false,
                false,
                $orderPayload
            ),
            '/0'
        ));

        $ordered = $queue->getCommandsInOrder($definitionRegistry);

        static::assertCount(4, $ordered);

        static::assertInstanceOf(InsertCommand::class, $ordered[0]);
        static::assertSame($orderDefinition->getEntityName(), $ordered[0]->getEntityName());

        static::assertInstanceOf(InsertCommand::class, $ordered[1]);
        static::assertSame($orderDeliveryDefinition->getEntityName(), $ordered[1]->getEntityName());

        static::assertTrue($ordered[2] instanceof JsonUpdateCommand || $ordered[2] instanceof UpdateCommand);
        static::assertTrue($ordered[3] instanceof JsonUpdateCommand || $ordered[3] instanceof UpdateCommand);
    }
}
