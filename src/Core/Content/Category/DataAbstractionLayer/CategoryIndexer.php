<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\DataAbstractionLayer;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Category\Event\CategoryIndexerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\IterableQuery;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\RetryableTransaction;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\ChildCountUpdater;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\TreeUpdater;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('inventory')]
class CategoryIndexer extends EntityIndexer
{
    final public const CHILD_COUNT_UPDATER = 'category.child-count';
    final public const TREE_UPDATER = 'category.tree';
    final public const BREADCRUMB_UPDATER = 'category.breadcrumb';
    private const UPDATE_IDS_CHUNK_SIZE = 50;

    /**
     * @internal
     *
     * @param EntityRepository<CategoryCollection> $repository
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly IteratorFactory $iteratorFactory,
        private readonly EntityRepository $repository,
        private readonly ChildCountUpdater $childCountUpdater,
        private readonly TreeUpdater $treeUpdater,
        private readonly CategoryBreadcrumbUpdater $breadcrumbUpdater,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function getName(): string
    {
        return 'category.indexer';
    }

    public function getTotal(): int
    {
        return $this->getIterator(null)->fetchCount();
    }

    public function iterate(?array $offset): ?EntityIndexingMessage
    {
        $iterator = $this->getIterator($offset);

        $ids = $iterator->fetch();

        if (empty($ids)) {
            return null;
        }

        return new CategoryIndexingMessage(
            data: array_values($ids),
            offset: $iterator->getOffset()
        );
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        $categoryEvent = $event->getEventByEntityName(CategoryDefinition::ENTITY_NAME);

        if (!$categoryEvent) {
            return null;
        }

        $ids = $categoryEvent->getIds();
        $idsWithChangedParentIds = [];
        foreach ($categoryEvent->getWriteResults() as $result) {
            if (!$result->getExistence()) {
                continue;
            }
            $state = $result->getExistence()->getState();

            if (isset($state['parent_id'])) {
                $ids[] = Uuid::fromBytesToHex($state['parent_id']);
            }

            $payload = $result->getPayload();
            if (\array_key_exists('parentId', $payload)) {
                if ($payload['parentId'] !== null) {
                    $ids[] = $payload['parentId'];
                }
                $idsWithChangedParentIds[] = $payload['id'];
            }
        }

        if (empty($ids)) {
            return null;
        }

        if ($idsWithChangedParentIds !== []) {
            $this->treeUpdater->batchUpdate(
                $idsWithChangedParentIds,
                CategoryDefinition::ENTITY_NAME,
                $event->getContext(),
                true
            );
        }

        $children = $this->fetchChildren($ids, $event->getContext()->getVersionId());
        $ids = array_unique(array_merge($ids, $children));

        $chunks = \array_chunk($ids, self::UPDATE_IDS_CHUNK_SIZE);
        $idsForReturnedMessage = array_shift($chunks);

        foreach ($chunks as $chunk) {
            $childrenIndexingMessage = new CategoryIndexingMessage($chunk, null, $event->getContext());
            $childrenIndexingMessage->setIndexer($this->getName());
            EntityIndexerRegistry::addSkips($childrenIndexingMessage, $event->getContext());

            $this->messageBus->dispatch($childrenIndexingMessage);
        }

        return new CategoryIndexingMessage($idsForReturnedMessage, null, $event->getContext());
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $ids = $message->getData();
        if (!\is_array($ids)) {
            return;
        }

        $ids = array_values(array_unique(array_filter($ids)));
        if (empty($ids)) {
            return;
        }

        $context = $message->getContext();

        RetryableTransaction::retryable($this->connection, function () use ($message, $ids, $context): void {
            if ($message->allow(self::CHILD_COUNT_UPDATER)) {
                // listen to parent id changes
                $this->childCountUpdater->update(CategoryDefinition::ENTITY_NAME, $ids, $context);
            }

            if ($message->allow(self::TREE_UPDATER)) {
                $this->treeUpdater->batchUpdate(
                    $ids,
                    CategoryDefinition::ENTITY_NAME,
                    $context,
                    !$message->isFullIndexing
                );
            }

            if ($message->allow(self::BREADCRUMB_UPDATER)) {
                // listen to name changes
                $this->breadcrumbUpdater->update($ids, $context);
            }
        });

        $this->eventDispatcher->dispatch(new CategoryIndexerEvent($ids, $context, $message->getSkip(), $message->isFullIndexing));
    }

    public function getOptions(): array
    {
        return [
            self::CHILD_COUNT_UPDATER,
            self::TREE_UPDATER,
            self::BREADCRUMB_UPDATER,
        ];
    }

    public function getDecorated(): EntityIndexer
    {
        throw new DecorationPatternException(static::class);
    }

    /**
     * @param array<string> $categoryIds
     *
     * @return array<string>
     */
    private function fetchChildren(array $categoryIds, string $versionId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('DISTINCT LOWER(HEX(category.id))');
        $query->from('category');

        $wheres = [];
        foreach ($categoryIds as $id) {
            $key = 'path' . $id;
            $wheres[] = 'category.path LIKE :' . $key;
            $query->setParameter($key, '%|' . $id . '|%');
        }

        $query->andWhere('(' . implode(' OR ', $wheres) . ')');
        $query->andWhere('category.version_id = :version');
        $query->setParameter('version', Uuid::fromHexToBytes($versionId));

        return $query->executeQuery()->fetchFirstColumn();
    }

    /**
     * @param array{offset: int|null}|null $offset
     */
    private function getIterator(?array $offset): IterableQuery
    {
        return $this->iteratorFactory->createIterator($this->repository->getDefinition(), $offset);
    }
}
