<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Storer;

use Cicada\Core\Content\Flow\Dispatching\Aware\NewsletterRecipientAware;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Events\BeforeLoadStorableFlowDataEvent;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('after-sales')]
class NewsletterRecipientStorer extends FlowStorer
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $newsletterRecipientRepository,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @param array<string, mixed> $stored
     *
     * @return array<string, mixed>
     */
    public function store(FlowEventAware $event, array $stored): array
    {
        if (!$event instanceof NewsletterRecipientAware || isset($stored[NewsletterRecipientAware::NEWSLETTER_RECIPIENT_ID])) {
            return $stored;
        }

        $stored[NewsletterRecipientAware::NEWSLETTER_RECIPIENT_ID] = $event->getNewsletterRecipientId();

        return $stored;
    }

    public function restore(StorableFlow $storable): void
    {
        if (!$storable->hasStore(NewsletterRecipientAware::NEWSLETTER_RECIPIENT_ID)) {
            return;
        }

        $storable->lazy(
            NewsletterRecipientAware::NEWSLETTER_RECIPIENT,
            $this->lazyLoad(...)
        );
    }

    private function lazyLoad(StorableFlow $storableFlow): ?NewsletterRecipientEntity
    {
        $id = $storableFlow->getStore(NewsletterRecipientAware::NEWSLETTER_RECIPIENT_ID);
        if ($id === null) {
            return null;
        }

        $criteria = new Criteria([$id]);

        return $this->loadNewsletterRecipient($criteria, $storableFlow->getContext(), $id);
    }

    private function loadNewsletterRecipient(Criteria $criteria, Context $context, string $id): ?NewsletterRecipientEntity
    {
        $event = new BeforeLoadStorableFlowDataEvent(
            NewsletterRecipientDefinition::ENTITY_NAME,
            $criteria,
            $context,
        );

        $this->dispatcher->dispatch($event, $event->getName());

        $newsletterRecipient = $this->newsletterRecipientRepository->search($criteria, $context)->get($id);

        if ($newsletterRecipient) {
            /** @var NewsletterRecipientEntity $newsletterRecipient */
            return $newsletterRecipient;
        }

        return null;
    }
}
