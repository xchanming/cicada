<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching\Storer;

use Cicada\Core\Content\Flow\Dispatching\Aware\MessageAware;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\MessageStorer;
use Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeSentEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(MessageStorer::class)]
class MessageStorerTest extends TestCase
{
    public function testStoreNewData(): void
    {
        $storedData = [[]];
        $data = [
            'subject' => 'Hi',
            'senderName' => 'cicada',
            'contentPlain' => 'test',
        ];

        $mail = new Email();
        $mail->html('text/plain');

        $event = new MailBeforeSentEvent(
            $data,
            $mail,
            Context::createDefaultContext()
        );

        $storer = new MessageStorer();
        $stored = $storer->store($event, $storedData);

        static::assertArrayHasKey(MessageAware::MESSAGE, $stored);
        static::assertIsString($stored[MessageAware::MESSAGE]);
    }

    public function testStoreExistsData(): void
    {
        $storedData = [['message' => '[]']];
        $data = [
            'subject' => 'Hi',
            'senderName' => 'cicada',
            'contentPlain' => 'test',
        ];

        $mail = new Email();
        $mail->html('text/plain');

        $event = new MailBeforeSentEvent(
            $data,
            $mail,
            Context::createDefaultContext()
        );

        $storer = new MessageStorer();
        $stored = $storer->store($event, $storedData);

        static::assertArrayHasKey(MessageAware::MESSAGE, $stored);
        static::assertIsString($stored[MessageAware::MESSAGE]);
    }

    public function testRestoreHasStored(): void
    {
        $mail = new Email();
        $mail->html('text/plain');

        $flow = new StorableFlow('foo', Context::createDefaultContext(), [
            MessageAware::MESSAGE => \serialize($mail),
        ]);

        $storer = new MessageStorer();
        $storer->restore($flow);

        static::assertEquals($mail, $flow->getData(MessageAware::MESSAGE));
    }

    public function testRestoreEmptyStored(): void
    {
        $storer = new MessageStorer();

        $flow = new StorableFlow('foo', Context::createDefaultContext());

        $storer->restore($flow);

        static::assertEmpty($flow->data());
    }
}
