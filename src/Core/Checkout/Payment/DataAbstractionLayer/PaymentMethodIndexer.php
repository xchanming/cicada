<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\DataAbstractionLayer;

use Cicada\Core\Checkout\Payment\Event\PaymentMethodIndexerEvent;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('checkout')]
class PaymentMethodIndexer extends EntityIndexer
{
    /**
     * @internal
     */
    public function __construct(
        private readonly IteratorFactory $iteratorFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityRepository $paymentMethodRepository,
        private readonly PaymentDistinguishableNameGenerator $distinguishableNameGenerator
    ) {
    }

    public function getName(): string
    {
        return 'payment_method.indexer';
    }

    /**
     * @param array{offset: int|null}|null $offset
     */
    public function iterate(?array $offset): ?EntityIndexingMessage
    {
        $iterator = $this->iteratorFactory->createIterator($this->paymentMethodRepository->getDefinition(), $offset);

        $ids = $iterator->fetch();

        if (empty($ids)) {
            return null;
        }

        return new PaymentMethodIndexingMessage(array_values($ids), $iterator->getOffset());
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        $updates = $event->getPrimaryKeys(PaymentMethodDefinition::ENTITY_NAME);

        if (empty($updates)) {
            return null;
        }

        return new PaymentMethodIndexingMessage(array_values($updates), null, $event->getContext());
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $ids = $message->getData();
        if (!\is_array($ids)) {
            return;
        }

        $ids = array_unique(array_filter($ids));
        if (empty($ids)) {
            return;
        }

        $context = $message->getContext();

        // Use 'disabled-indexing' state, because DAL is used in the NameGenerator to upsert payment methods
        $context->state(function (Context $context): void {
            $this->distinguishableNameGenerator->generateDistinguishablePaymentNames($context);
        }, EntityIndexerRegistry::DISABLE_INDEXING);

        $this->eventDispatcher->dispatch(new PaymentMethodIndexerEvent($ids, $context, $message->getSkip()));
    }

    public function getTotal(): int
    {
        return $this->iteratorFactory->createIterator($this->paymentMethodRepository->getDefinition())->fetchCount();
    }

    public function getDecorated(): EntityIndexer
    {
        throw new DecorationPatternException(static::class);
    }
}
