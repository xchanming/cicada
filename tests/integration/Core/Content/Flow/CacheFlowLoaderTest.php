<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Flow;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Content\Flow\Dispatching\CachedFlowLoader;
use Cicada\Core\Content\Flow\FlowEvents;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[Package('after-sales')]
class CacheFlowLoaderTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testGetSubscribedEvents(): void
    {
        static::assertEquals([
            FlowEvents::FLOW_WRITTEN_EVENT => 'invalidate',
        ], CachedFlowLoader::getSubscribedEvents());
    }

    public function testClearFlowCache(): void
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');
        $dispatcher->addListener(FlowEvents::FLOW_WRITTEN_EVENT, $listener);

        $flowLoader = static::getContainer()->get(CachedFlowLoader::class);
        $class = new \ReflectionClass($flowLoader);
        $property = $class->getProperty('flows');
        $property->setAccessible(true);
        $property->setValue(
            $flowLoader,
            ['abc']
        );

        static::getContainer()->get('flow.repository')->create([[
            'name' => 'Create Order',
            'eventName' => CheckoutOrderPlacedEvent::EVENT_NAME,
            'priority' => 1,
            'active' => true,
        ]], Context::createDefaultContext());

        static::assertEmpty($property->getValue($flowLoader));
    }
}
