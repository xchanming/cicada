<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Lifecycle\Persister;

use Cicada\Core\Framework\App\Aggregate\ActionButton\ActionButtonCollection;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class ActionButtonPersister
{
    /**
     * @param EntityRepository<ActionButtonCollection> $actionButtonRepository
     */
    public function __construct(private readonly EntityRepository $actionButtonRepository)
    {
    }

    public function updateActions(Manifest $manifest, string $appId, string $defaultLocale, Context $context): void
    {
        $existingActionButtons = $this->getExistingActionButtons($appId, $context);

        $actionButtons = $manifest->getAdmin() ? $manifest->getAdmin()->getActionButtons() : [];
        $upserts = [];
        foreach ($actionButtons as $actionButton) {
            $payload = $actionButton->toArray($defaultLocale);
            $payload['appId'] = $appId;

            $existing = $existingActionButtons->filterByProperty('action', $actionButton->getAction())->first();
            if ($existing) {
                $payload['id'] = $existing->getId();
                $existingActionButtons->remove($existing->getId());
            }

            $upserts[] = $payload;
        }

        if (!empty($upserts)) {
            $this->actionButtonRepository->upsert($upserts, $context);
        }

        $this->deleteOldActions($existingActionButtons, $context);
    }

    private function deleteOldActions(ActionButtonCollection $toBeRemoved, Context $context): void
    {
        $ids = $toBeRemoved->getIds();

        if (!empty($ids)) {
            $ids = array_map(static fn (string $id): array => ['id' => $id], array_values($ids));

            $this->actionButtonRepository->delete($ids, $context);
        }
    }

    private function getExistingActionButtons(string $appId, Context $context): ActionButtonCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('appId', $appId));

        return $this->actionButtonRepository->search($criteria, $context)->getEntities();
    }
}
