<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\Event;

use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\ScalarValuesStorer;
use Cicada\Core\Content\Media\Event\MediaUploadedEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\ScalarValueType;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Webhook\AclPrivilegeCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(MediaUploadedEvent::class)]
class MediaUploadedEventTest extends TestCase
{
    public function testInstance(): void
    {
        $mediaId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $mediaUploadEvent = new MediaUploadedEvent(
            $mediaId,
            $context
        );

        static::assertEquals('media.uploaded', $mediaUploadEvent->getName());
        static::assertEquals($mediaId, $mediaUploadEvent->getMediaId());
        static::assertEquals(
            $context,
            $mediaUploadEvent->getContext()
        );
    }

    public function testGetAvailableData(): void
    {
        $eventDataCollection = MediaUploadedEvent::getAvailableData();
        static::assertCount(1, $eventDataCollection->toArray());
        static::assertEquals(
            (new EventDataCollection())->add('mediaId', new ScalarValueType(ScalarValueType::TYPE_STRING)),
            $eventDataCollection
        );
    }

    public function testRestoreScalarValuesCorrectly(): void
    {
        $event = new MediaUploadedEvent('media-id', Context::createDefaultContext());

        $storer = new ScalarValuesStorer();

        $stored = $storer->store($event, []);

        $flow = new StorableFlow('foo', Context::createDefaultContext(), $stored);

        $storer->restore($flow);

        static::assertArrayHasKey('mediaId', $flow->data());
        static::assertEquals('media-id', $flow->data()['mediaId']);
    }

    public function testGetWebhookPayload(): void
    {
        $mediaId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $mediaUploadEvent = new MediaUploadedEvent(
            $mediaId,
            $context
        );

        static::assertEquals(
            [
                'mediaId' => $mediaId,
            ],
            $mediaUploadEvent->getWebhookPayload()
        );
    }

    public function testACL(): void
    {
        $mediaId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $mediaUploadEvent = new MediaUploadedEvent(
            $mediaId,
            $context
        );

        static::assertFalse($mediaUploadEvent->isAllowed('1', new AclPrivilegeCollection(['media:create'])));
        static::assertTrue($mediaUploadEvent->isAllowed('1', new AclPrivilegeCollection(['media:read'])));
    }
}
