<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel\Review\Event;

use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\ScalarValuesStorer;
use Cicada\Core\Content\Product\SalesChannel\Review\Event\ReviewFormEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ReviewFormEvent::class)]
class ReviewFormEventTest extends TestCase
{
    public function testInstance(): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = 'foo';
        $mailRecipientStruct = new MailRecipientStruct(['foo' => 'bar']);
        $data = new DataBag(['baz']);
        $productId = 'bar';
        $customerId = 'bar';

        $event = new ReviewFormEvent($context, $salesChannelId, $mailRecipientStruct, $data, $productId, $customerId);

        static::assertEquals($context, $event->getContext());
        static::assertEquals($salesChannelId, $event->getSalesChannelId());
        static::assertEquals($mailRecipientStruct, $event->getMailStruct());
        static::assertEquals($data->all(), $event->getReviewFormData());
        static::assertEquals($productId, $event->getProductId());
        static::assertEquals($customerId, $event->getCustomerId());
    }

    public function testScalarValuesCorrectly(): void
    {
        $event = new ReviewFormEvent(
            Context::createDefaultContext(),
            'sales-channel-id',
            new MailRecipientStruct(['foo' => 'bar']),
            new DataBag(['foo' => 'bar', 'bar' => 'baz']),
            'product-id',
            'customer-id'
        );

        $storer = new ScalarValuesStorer();

        $stored = $storer->store($event, []);

        $flow = new StorableFlow('foo', Context::createDefaultContext(), $stored);

        $storer->restore($flow);

        static::assertArrayHasKey('reviewFormData', $flow->data());
        static::assertEquals(['foo' => 'bar', 'bar' => 'baz'], $flow->data()['reviewFormData']);
    }
}
