<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Cms\Subscriber\UnusedMediaSubscriber;
use Cicada\Core\Content\Media\Event\UnusedMediaSearchEvent;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(UnusedMediaSubscriber::class)]
class UnusedMediaSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        static::assertEquals(
            [
                UnusedMediaSearchEvent::class => 'removeUsedMedia',
            ],
            UnusedMediaSubscriber::getSubscribedEvents()
        );
    }
}
