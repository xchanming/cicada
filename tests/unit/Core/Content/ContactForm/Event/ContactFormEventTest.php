<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\ContactForm\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\ContactForm\Event\ContactFormEvent;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\ScalarValuesStorer;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\DataBag;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(ContactFormEvent::class)]
class ContactFormEventTest extends TestCase
{
    public function testScalarValuesCorrectly(): void
    {
        $event = new ContactFormEvent(
            Context::createDefaultContext(),
            'sales-channel-id',
            new MailRecipientStruct(['foo' => 'bar']),
            new DataBag(['foo' => 'bar', 'bar' => 'baz'])
        );

        $storer = new ScalarValuesStorer();

        $stored = $storer->store($event, []);

        $flow = new StorableFlow('foo', Context::createDefaultContext(), $stored);

        $storer->restore($flow);

        static::assertArrayHasKey('contactFormData', $flow->data());
        static::assertEquals(['foo' => 'bar', 'bar' => 'baz'], $flow->data()['contactFormData']);
    }
}
