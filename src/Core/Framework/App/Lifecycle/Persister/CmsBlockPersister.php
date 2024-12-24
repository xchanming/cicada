<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Lifecycle\Persister;

use Cicada\Core\Framework\App\Aggregate\CmsBlock\AppCmsBlockCollection;
use Cicada\Core\Framework\App\Cms\AbstractBlockTemplateLoader;
use Cicada\Core\Framework\App\Cms\CmsExtensions;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class CmsBlockPersister
{
    /**
     * @param EntityRepository<AppCmsBlockCollection> $cmsBlockRepository
     */
    public function __construct(
        private readonly EntityRepository $cmsBlockRepository,
        private readonly AbstractBlockTemplateLoader $blockTemplateLoader,
    ) {
    }

    public function updateCmsBlocks(
        CmsExtensions $cmsExtensions,
        string $appId,
        string $defaultLocale,
        Context $context
    ): void {
        $existingCmsBlocks = $this->getExistingCmsBlocks($appId, $context);

        $cmsBlocks = $cmsExtensions->getBlocks() !== null ? $cmsExtensions->getBlocks()->getBlocks() : [];
        $upserts = [];
        foreach ($cmsBlocks as $cmsBlock) {
            $payload = $cmsBlock->toEntityArray($appId, $defaultLocale);

            $template = $this->blockTemplateLoader->getTemplateForBlock($cmsExtensions, $cmsBlock->getName());

            $payload['template'] = $template;
            $payload['styles'] = $this->blockTemplateLoader->getStylesForBlock($cmsExtensions, $cmsBlock->getName());

            $existing = $existingCmsBlocks->filterByProperty('name', $cmsBlock->getName())->first();
            if ($existing) {
                $payload['id'] = $existing->getId();
                $existingCmsBlocks->remove($existing->getId());
            }

            $upserts[] = $payload;
        }

        if (!empty($upserts)) {
            $this->cmsBlockRepository->upsert($upserts, $context);
        }

        $this->deleteOldCmsBlocks($existingCmsBlocks, $context);
    }

    private function deleteOldCmsBlocks(AppCmsBlockCollection $toBeRemoved, Context $context): void
    {
        $ids = $toBeRemoved->getIds();

        if (!empty($ids)) {
            $ids = array_map(static fn (string $id): array => ['id' => $id], array_values($ids));

            $this->cmsBlockRepository->delete($ids, $context);
        }
    }

    private function getExistingCmsBlocks(string $appId, Context $context): AppCmsBlockCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('appId', $appId));

        return $this->cmsBlockRepository->search($criteria, $context)->getEntities();
    }
}
