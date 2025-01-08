<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Template;

use Cicada\Core\Framework\Adapter\Cache\CacheClearer;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class TemplateStateService
{
    public function __construct(
        private readonly EntityRepository $templateRepo,
        private readonly CacheClearer $cacheClearer,
    ) {
    }

    public function activateAppTemplates(string $appId, Context $context): void
    {
        $this->updateAppTemplates($appId, $context, false, true);
    }

    public function deactivateAppTemplates(string $appId, Context $context): void
    {
        $this->updateAppTemplates($appId, $context, true, false);
    }

    private function updateAppTemplates(string $appId, Context $context, bool $currentActiveState, bool $newActiveState): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('appId', $appId));
        $criteria->addFilter(new EqualsFilter('active', $currentActiveState));

        /** @var array<string> $templates */
        $templates = $this->templateRepo->searchIds($criteria, $context)->getIds();

        $updateSet = array_map(fn (string $id) => ['id' => $id, 'active' => $newActiveState], $templates);

        $this->templateRepo->update($updateSet, $context);

        $this->cacheClearer->clearHttpCache();
    }
}
