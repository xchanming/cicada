<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\TestCaseBase;

use Cicada\Core\Framework\MessageQueue\Subscriber\MessageQueueStatsSubscriber;
use Cicada\Core\Framework\Test\TestCaseHelper\StopWorkerWhenIdleListener;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\TraceableMessageBus;
use Symfony\Component\Messenger\Worker;

trait QueueTestBehaviour
{
    #[Before]
    #[After]
    public function clearQueue(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM messenger_messages');
        $bus = static::getContainer()->get('messenger.bus.test_cicada');
        static::assertInstanceOf(TraceableMessageBus::class, $bus);
        $bus->reset();
    }

    public function runWorker(): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(new StopWorkerWhenIdleListener());
        $eventDispatcher->addSubscriber(static::getContainer()->get(MessageQueueStatsSubscriber::class));

        $locator = static::getContainer()->get('messenger.test_receiver_locator');
        static::assertInstanceOf(ServiceLocator::class, $locator);

        $receiver = $locator->get('async');

        $bus = static::getContainer()->get('messenger.bus.test_cicada');
        static::assertInstanceOf(MessageBusInterface::class, $bus);

        $worker = new Worker([$receiver], $bus, $eventDispatcher);

        $worker->run([
            'sleep' => 1000,
        ]);
    }

    abstract protected static function getContainer(): ContainerInterface;
}
