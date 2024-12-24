<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Event\Subscriber;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\ImportExport\Event\ImportExportBeforeImportRecordEvent;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Api\Sync\SyncBehavior;
use Cicada\Core\Framework\Api\Sync\SyncOperation;
use Cicada\Core\Framework\Api\Sync\SyncServiceInterface;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
#[Package('services-settings')]
class ProductCategoryPathsSubscriber implements EventSubscriberInterface, ResetInterface
{
    /**
     * @var array<string, string>
     */
    private array $categoryIdCache = [];

    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $categoryRepository,
        private readonly SyncServiceInterface $syncService
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ImportExportBeforeImportRecordEvent::class => 'categoryPathsToAssignment',
        ];
    }

    public function categoryPathsToAssignment(ImportExportBeforeImportRecordEvent $event): void
    {
        $row = $event->getRow();
        $entityName = $event->getConfig()->get('sourceEntity');

        if ($entityName !== ProductDefinition::ENTITY_NAME || empty($row['category_paths'])) {
            return;
        }

        $context = $event->getContext();

        $result = [];
        $categoriesPaths = explode('|', (string) $row['category_paths']);
        $newCategoriesPayload = [];

        foreach ($categoriesPaths as $path) {
            $categories = explode('>', $path);

            $categoryId = null;
            foreach ($categories as $currentIndex => $categoryName) {
                if (empty($categoryName)) {
                    continue;
                }

                $partialPath = implode('>', \array_slice($categories, 0, $currentIndex + 1));

                if (isset($this->categoryIdCache[$partialPath])) {
                    $categoryId = $this->categoryIdCache[$partialPath];

                    continue;
                }

                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('name', $categoryName));
                $criteria->addFilter(new EqualsFilter('parentId', $categoryId));

                $childCategoryId = $this->categoryRepository->searchIds($criteria, $context)->firstId();
                if ($childCategoryId === null && $categoryId === null) {
                    break;
                }

                if ($childCategoryId !== null) {
                    $categoryId = $childCategoryId;
                    $this->categoryIdCache[$partialPath] = $categoryId;

                    continue;
                }

                $parentId = $categoryId;
                $categoryId = Uuid::fromStringToHex($partialPath);
                $this->categoryIdCache[$partialPath] = $categoryId;

                $newCategoriesPayload[] = [
                    'id' => $categoryId,
                    'parent' => ['id' => $parentId],
                    'name' => $categoryName,
                ];
            }

            if ($categoryId !== null) {
                $result[] = ['id' => $categoryId];
            }
        }

        if (!empty($newCategoriesPayload)) {
            $this->createNewCategories($newCategoriesPayload, $context);
        }

        $record = $event->getRecord();
        $record['categories'] = !empty($record['categories']) ? array_merge($record['categories'], $result) : $result;

        $event->setRecord($record);
    }

    public function reset(): void
    {
        $this->categoryIdCache = [];
    }

    /**
     * @param list<array<string, mixed>> $payload
     */
    private function createNewCategories(array $payload, Context $context): void
    {
        $this->syncService->sync([
            new SyncOperation(
                'write',
                CategoryDefinition::ENTITY_NAME,
                SyncOperation::ACTION_UPSERT,
                $payload
            ),
        ], $context, new SyncBehavior());
    }
}
