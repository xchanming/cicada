<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo;

use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteRegistry;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\SalesChannel\SalesChannelCollection;
use Doctrine\DBAL\Connection;

/**
 * This class can be used to regenerate the seo urls for a route and an offset at ids.
 */
#[Package('buyers-experience')]
class SeoUrlUpdater
{
    /**
     * @internal
     *
     * @param EntityRepository<LanguageCollection> $languageRepository
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly EntityRepository $languageRepository,
        private readonly SeoUrlRouteRegistry $seoUrlRouteRegistry,
        private readonly SeoUrlGenerator $seoUrlGenerator,
        private readonly SeoUrlPersister $seoUrlPersister,
        private readonly Connection $connection,
        private readonly EntityRepository $salesChannelRepository
    ) {
    }

    /**
     * @param array<string> $ids
     */
    public function update(string $routeName, array $ids): void
    {
        $templates = $routeName !== '' ? $this->loadUrlTemplate($routeName) : [];
        if (empty($templates)) {
            return;
        }

        $route = $this->seoUrlRouteRegistry->findByRouteName($routeName);
        if ($route === null) {
            throw new \RuntimeException(\sprintf('Route by name %s not found', $routeName));
        }

        $context = Context::createDefaultContext();

        $languageChains = $this->fetchLanguageChains($context);

        $criteria = new Criteria();
        $criteria->addFilter(new NandFilter([new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_API)]));

        $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        foreach ($templates as $config) {
            $template = $config['template'];
            $salesChannel = $salesChannels->get($config['salesChannelId']);
            if ($template === '' || $salesChannel === null) {
                continue;
            }

            $chain = $languageChains[$config['languageId']];
            $languageContext = new Context(new SystemSource(), [], Defaults::CURRENCY, $chain);
            $languageContext->setConsiderInheritance(true);

            // generate new seo urls
            $urls = $this->seoUrlGenerator->generate($ids, $template, $route, $languageContext, $salesChannel);

            // persist seo urls to storage
            $this->seoUrlPersister->updateSeoUrls($languageContext, $routeName, $ids, $urls, $salesChannel);
        }
    }

    /**
     * Loads the SEO url templates for the given $routeName for all combinations of languages and sales channels
     *
     * @param non-empty-string $routeName
     *
     * @return list<array{salesChannelId: string, languageId: string, template: string}>
     */
    private function loadUrlTemplate(string $routeName): array
    {
        $query = 'SELECT DISTINCT
               LOWER(HEX(sales_channel.id)) as salesChannelId,
               LOWER(HEX(domains.language_id)) as languageId
             FROM sales_channel_domain as domains
             INNER JOIN sales_channel
               ON domains.sales_channel_id = sales_channel.id
               AND sales_channel.active = 1';
        $parameters = [];

        $query .= ' AND sales_channel.type_id != :apiTypeId';
        $parameters['apiTypeId'] = Uuid::fromHexToBytes(Defaults::SALES_CHANNEL_TYPE_API);

        $domains = $this->connection->fetchAllAssociative($query, $parameters);

        if ($domains === []) {
            return [];
        }

        $salesChannelTemplates = $this->connection->fetchAllKeyValue(
            'SELECT LOWER(HEX(sales_channel_id)) as sales_channel_id, template
             FROM seo_url_template
             WHERE route_name LIKE :route',
            ['route' => $routeName]
        );

        if (!\array_key_exists('', $salesChannelTemplates)) {
            throw new \RuntimeException('Default templates not configured');
        }

        $default = (string) $salesChannelTemplates[''];

        $result = [];
        foreach ($domains as $domain) {
            $salesChannelId = $domain['salesChannelId'];

            $result[] = [
                'salesChannelId' => $salesChannelId,
                'languageId' => $domain['languageId'],
                'template' => $salesChannelTemplates[$salesChannelId] ?? $default,
            ];
        }

        return $result;
    }

    /**
     * @return array<string, array<string>>
     */
    private function fetchLanguageChains(Context $context): array
    {
        $languages = $this->languageRepository->search(new Criteria(), $context)->getEntities()->getElements();

        $languageChains = [];
        foreach ($languages as $language) {
            $languageId = $language->getId();
            $languageChains[$languageId] = array_filter([
                $languageId,
                $language->getParentId(),
                Defaults::LANGUAGE_SYSTEM,
            ]);
        }

        return $languageChains;
    }
}
