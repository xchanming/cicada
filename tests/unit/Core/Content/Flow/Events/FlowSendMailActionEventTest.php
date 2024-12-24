<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Events;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Events\FlowSendMailActionEvent;
use Cicada\Core\Content\MailTemplate\MailTemplateEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\DataBag;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(FlowSendMailActionEvent::class)]
class FlowSendMailActionEventTest extends TestCase
{
    public function testEventConstructorParameters(): void
    {
        $context = Context::createDefaultContext();
        $flow = new StorableFlow('foo', $context);

        $expectDataBag = new DataBag(['data' => 'data']);
        $mailTemplate = new MailTemplateEntity();

        $event = new FlowSendMailActionEvent($expectDataBag, $mailTemplate, $flow);

        static::assertSame($context, $event->getContext());
        static::assertSame($expectDataBag, $event->getDataBag());
        static::assertSame($mailTemplate, $event->getMailTemplate());
        static::assertSame($flow, $event->getStorableFlow());
    }
}
