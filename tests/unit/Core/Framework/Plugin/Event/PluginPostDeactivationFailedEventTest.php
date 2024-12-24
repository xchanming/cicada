<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Plugin\Event;

use Cicada\Core\Framework\Plugin\Context\ActivateContext;
use Cicada\Core\Framework\Plugin\Event\PluginPostDeactivationFailedEvent;
use Cicada\Core\Framework\Plugin\PluginEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PluginPostDeactivationFailedEvent::class)]
class PluginPostDeactivationFailedEventTest extends TestCase
{
    public function testEvent(): void
    {
        $activateContext = $this->createMock(ActivateContext::class);
        $exception = new \Exception('failed');
        $event = new PluginPostDeactivationFailedEvent(
            new PluginEntity(),
            $activateContext,
            $exception
        );
        static::assertEquals($activateContext, $event->getContext());
        static::assertEquals($exception, $event->getException());
    }
}
