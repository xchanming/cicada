<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\MailTemplate\Service\Event;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\ScalarValuesStorer;
use Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeSentEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * @internal
 */
#[CoversClass(MailBeforeSentEvent::class)]
#[Package('after-sales')]
class MailBeforeSentEventTest extends TestCase
{
    public function testScalarValuesCorrectly(): void
    {
        $event = new MailBeforeSentEvent(
            ['foo' => 'bar'],
            new Email(),
            Context::createDefaultContext()
        );

        $storer = new ScalarValuesStorer();

        $stored = $storer->store($event, []);

        $flow = new StorableFlow('foo', Context::createDefaultContext(), $stored);

        $storer->restore($flow);

        static::assertArrayHasKey('data', $flow->data());
        static::assertEquals(['foo' => 'bar'], $flow->data()['data']);
    }

    public function testInstantiate(): void
    {
        $context = Context::createDefaultContext();
        $customerId = Uuid::randomHex();
        $email = (new Email())->subject('test subject')
            ->html('content html')
            ->text('content plain')
            ->to('test@xchanming.com')
            ->from(new Address('test@xchanming.com'));

        $event = new MailBeforeSentEvent(
            [
                'customerId' => $customerId,
            ],
            $email,
            $context,
            CheckoutOrderPlacedEvent::EVENT_NAME
        );

        static::assertSame(Level::Info, $event->getLogLevel());
        static::assertSame('mail.after.create.message', $event->getName());
        static::assertSame($context, $event->getContext());
        static::assertSame([
            'customerId' => $customerId,
        ], $event->getData());
        static::assertSame([
            'data' => [
                'customerId' => $customerId,
            ],
            'eventName' => CheckoutOrderPlacedEvent::EVENT_NAME,
            'message' => $email,
        ], $event->getLogData());
    }
}
