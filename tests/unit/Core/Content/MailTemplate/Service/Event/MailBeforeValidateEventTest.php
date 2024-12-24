<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\MailTemplate\Service\Event;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\ScalarValuesStorer;
use Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeValidateEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MailBeforeValidateEvent::class)]
class MailBeforeValidateEventTest extends TestCase
{
    public function testScalarValuesCorrectly(): void
    {
        $event = new MailBeforeValidateEvent(
            ['foo' => 'bar'],
            Context::createDefaultContext(),
            ['template' => 'data'],
        );

        $storer = new ScalarValuesStorer();

        $stored = $storer->store($event, []);

        $flow = new StorableFlow('foo', Context::createDefaultContext(), $stored);

        $storer->restore($flow);

        static::assertArrayHasKey('data', $flow->data());
        static::assertArrayHasKey('templateData', $flow->data());
        static::assertEquals(['foo' => 'bar'], $flow->data()['data']);
        static::assertEquals(['template' => 'data'], $flow->data()['templateData']);
    }

    public function testInstantiate(): void
    {
        $context = Context::createDefaultContext();
        $customerId = Uuid::randomHex();

        $event = new MailBeforeValidateEvent(
            [
                'customerId' => $customerId,
            ],
            $context,
            [
                'user' => 'admin',
                'recoveryUrl' => 'http://some-url.com',
                'resetUrl' => 'http://some-url.com',
                'eventName' => CheckoutOrderPlacedEvent::EVENT_NAME,
            ]
        );

        static::assertSame(Level::Info, $event->getLogLevel());
        static::assertSame('mail.before.send', $event->getName());
        static::assertSame($context, $event->getContext());
        static::assertSame([
            'customerId' => $customerId,
        ], $event->getData());
        static::assertSame([
            'user' => 'admin',
            'recoveryUrl' => 'http://some-url.com',
            'resetUrl' => 'http://some-url.com',
            'eventName' => CheckoutOrderPlacedEvent::EVENT_NAME,
        ], $event->getTemplateData());
        static::assertSame([
            'data' => [
                'customerId' => $customerId,
            ],
            'eventName' => CheckoutOrderPlacedEvent::EVENT_NAME,
            'templateData' => [
                'user' => 'admin',
                'recoveryUrl' => 'http://some-url.com',
                'resetUrl' => 'http://some-url.com',
                'eventName' => CheckoutOrderPlacedEvent::EVENT_NAME,
            ],
        ], $event->getLogData());
    }
}
