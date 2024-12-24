<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Newsletter\Event;

use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\ScalarValuesStorer;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Cicada\Core\Content\Newsletter\Event\NewsletterRegisterEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(NewsletterRegisterEvent::class)]
class NewsletterRegisterEventTest extends TestCase
{
    public function testScalarValuesCorrectly(): void
    {
        $event = new NewsletterRegisterEvent(
            Context::createDefaultContext(),
            new NewsletterRecipientEntity(),
            'my-url',
            'my-sales-channel-id'
        );

        $storer = new ScalarValuesStorer();

        $stored = $storer->store($event, []);

        $flow = new StorableFlow('foo', Context::createDefaultContext(), $stored);

        $storer->restore($flow);

        static::assertArrayHasKey('url', $flow->data());
        static::assertEquals('my-url', $flow->data()['url']);
    }
}
