<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\Event;

use Cicada\Core\Content\Flow\Dispatching\Aware\NewsletterRecipientAware;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EntityType;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Event\MailAware;
use Cicada\Core\Framework\Event\SalesChannelAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\JsonSerializableTrait;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('buyers-experience')]
class NewsletterUnsubscribeEvent extends Event implements SalesChannelAware, MailAware, NewsletterRecipientAware, FlowEventAware
{
    use JsonSerializableTrait;

    final public const EVENT_NAME = 'newsletter.unsubscribe';

    private ?MailRecipientStruct $mailRecipientStruct = null;

    public function __construct(
        private readonly Context $context,
        private readonly NewsletterRecipientEntity $newsletterRecipient,
        private readonly string $salesChannelId
    ) {
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('newsletterRecipient', new EntityType(NewsletterRecipientDefinition::class));
    }

    public function getNewsletterRecipient(): NewsletterRecipientEntity
    {
        return $this->newsletterRecipient;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        if (!$this->mailRecipientStruct instanceof MailRecipientStruct) {
            $this->mailRecipientStruct = new MailRecipientStruct([
                $this->newsletterRecipient->getEmail() => $this->newsletterRecipient->getName(),
            ]);
        }

        return $this->mailRecipientStruct;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getNewsletterRecipientId(): string
    {
        return $this->newsletterRecipient->getId();
    }
}
