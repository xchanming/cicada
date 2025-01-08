<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Lifecycle\Persister;

use Cicada\Core\Framework\Adapter\Cache\CacheClearer;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Template\AbstractTemplateLoader;
use Cicada\Core\Framework\App\Template\TemplateCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Hasher;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class TemplatePersister
{
    /**
     * @param EntityRepository<TemplateCollection> $templateRepository
     * @param EntityRepository<AppCollection> $appRepository
     */
    public function __construct(
        private readonly AbstractTemplateLoader $templateLoader,
        private readonly EntityRepository $templateRepository,
        private readonly EntityRepository $appRepository,
        private readonly CacheClearer $cacheClearer,
    ) {
    }

    public function updateTemplates(Manifest $manifest, string $appId, Context $context, bool $install): void
    {
        $app = $this->getAppWithExistingTemplates($appId, $context);
        $existingTemplates = $app->getTemplates();

        \assert($existingTemplates !== null);

        $templatePaths = $this->templateLoader->getTemplatePathsForApp($manifest);

        $upserts = [];

        foreach ($templatePaths as $templatePath) {
            $templateContent = $this->templateLoader->getTemplateContent($templatePath, $manifest);

            $existing = $existingTemplates->filterByProperty('path', $templatePath)->first();
            if (!$existing) {
                $upserts[] = [
                    'template' => $templateContent,
                    'path' => $templatePath,
                    'active' => $app->isActive(),
                    'appId' => $appId,
                    'hash' => Hasher::hash($templateContent),
                ];

                continue;
            }

            $existingTemplates->remove($existing->getId());

            if (Hasher::hash($templateContent) === $existing->getHash()) {
                continue;
            }

            $upserts[] = [
                'id' => $existing->getId(),
                'template' => $templateContent,
                'hash' => Hasher::hash($templateContent),
            ];
        }
        $needsCacheClear = false;

        if (!empty($upserts)) {
            $needsCacheClear = true;
            $this->templateRepository->upsert($upserts, $context);
        }

        $ids = $existingTemplates->getIds();
        if (!empty($ids)) {
            $needsCacheClear = true;
            $ids = array_map(static fn (string $id): array => ['id' => $id], array_values($ids));

            $this->templateRepository->delete($ids, $context);
        }

        /**
         * only clear cache when we are in an update context
         * otherwise cache is cleared on template active/deactivate
         *
         * @see \Cicada\Core\Framework\App\Template\TemplateStateService::updateAppTemplates
         **/
        if ($needsCacheClear && !$install) {
            $this->cacheClearer->clearHttpCache();
        }
    }

    private function getAppWithExistingTemplates(string $appId, Context $context): AppEntity
    {
        $criteria = new Criteria([$appId]);
        $criteria->addAssociation('templates');

        $app = $this->appRepository->search($criteria, $context)->getEntities()->first();
        if ($app === null) {
            throw AppException::notFoundByField($appId, 'id');
        }

        return $app;
    }
}
