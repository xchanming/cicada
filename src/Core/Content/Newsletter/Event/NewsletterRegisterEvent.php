<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\Event;

use Cicada\Core\Content\Flow\Dispatching\Action\FlowMailVariables;
use Cicada\Core\Content\Flow\Dispatching\Aware\NewsletterRecipientAware;
use Cicada\Core\Content\Flow\Dispatching\Aware\ScalarValuesAware;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EntityType;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Event\EventData\ScalarValueType;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Event\MailAware;
use Cicada\Core\Framework\Event\SalesChannelAware;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('buyers-experience')]
class NewsletterRegisterEvent extends Event implements SalesChannelAware, MailAware, NewsletterRecipientAware, ScalarValuesAware, FlowEventAware
{
    final public const EVENT_NAME = 'newsletter.register';

    private ?MailRecipientStruct $mailRecipientStruct = null;

    public function __construct(
        private readonly Context $context,
        private readonly NewsletterRecipientEntity $newsletterRecipient,
        private readonly string $url,
        private readonly string $salesChannelId
    ) {
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('newsletterRecipient', new EntityType(NewsletterRecipientDefinition::class))
            ->add('url', new ScalarValueType(ScalarValueType::TYPE_STRING));
    }

    /**
     * @return array<string, scalar|array<mixed>|null>
     */
    public function getValues(): array
    {
        return [FlowMailVariables::URL => $this->url];
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getNewsletterRecipient(): NewsletterRecipientEntity
    {
        return $this->newsletterRecipient;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        if (!$this->mailRecipientStruct) {
            $recipientName = $this->newsletterRecipient->getEmail();

            if ($this->newsletterRecipient->getName()) {
                $recipientName = $this->newsletterRecipient->getName();
            }

            $this->mailRecipientStruct = new MailRecipientStruct(
                [
                    $this->newsletterRecipient->getEmail() => $recipientName,
                ]
            );
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
