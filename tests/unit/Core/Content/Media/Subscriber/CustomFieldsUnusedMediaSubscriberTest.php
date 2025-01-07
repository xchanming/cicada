<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\Subscriber;

use Cicada\Core\Content\Media\Event\UnusedMediaSearchEvent;
use Cicada\Core\Content\Media\Subscriber\CustomFieldsUnusedMediaSubscriber;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(CustomFieldsUnusedMediaSubscriber::class)]
class CustomFieldsUnusedMediaSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        static::assertEquals(
            [
                UnusedMediaSearchEvent::class => 'removeUsedMedia',
            ],
            CustomFieldsUnusedMediaSubscriber::getSubscribedEvents()
        );
    }
}
