<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\MessageQueue\Subscriber;

use Cicada\Core\Framework\Increment\AbstractIncrementer;
use Cicada\Core\Framework\Increment\IncrementGatewayRegistry;
use Cicada\Core\Framework\Test\MessageQueue\fixtures\BarMessage;
use Cicada\Core\Framework\Test\MessageQueue\fixtures\FooMessage;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\QueueTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\MessageQueue\fixtures\NoHandlerMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
class MessageQueueStatsSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    use QueueTestBehaviour;

    public function testListener(): void
    {
        /** @var AbstractIncrementer $pool */
        $pool = static::getContainer()
            ->get('cicada.increment.gateway.registry')
            ->get(IncrementGatewayRegistry::MESSAGE_QUEUE_POOL);

        $pool->reset('message_queue_stats');

        /** @var MessageBusInterface $bus */
        $bus = static::getContainer()->get('messenger.bus.test_cicada');

        $bus->dispatch(new FooMessage());
        $bus->dispatch(new BarMessage());
        $bus->dispatch(new BarMessage());
        $bus->dispatch(new BarMessage());

        $stats = $pool->list('message_queue_stats');
        static::assertEquals(1, $stats[FooMessage::class]['count']);
        static::assertEquals(3, $stats[BarMessage::class]['count']);

        $this->runWorker();

        $stats = $pool->list('message_queue_stats');
        static::assertEquals(0, $stats[FooMessage::class]['count']);
        static::assertEquals(0, $stats[BarMessage::class]['count']);

        $bus->dispatch(new NoHandlerMessage());

        $stats = $pool->list('message_queue_stats');
        static::assertEquals(1, $stats[NoHandlerMessage::class]['count']);

        $this->runWorker();
        $stats = $pool->list('message_queue_stats');
        static::assertEquals(0, $stats[NoHandlerMessage::class]['count']);
    }
}
