<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Provider;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Content\Sitemap\Service\ConfigHandler;
use Cicada\Core\Content\Sitemap\Struct\Url;
use Cicada\Core\Content\Sitemap\Struct\UrlResult;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\RouterInterface;

#[Package('discovery')]
class CategoryUrlProvider extends AbstractUrlProvider
{
    final public const CHANGE_FREQ = 'daily';

    /**
     * @internal
     */
    public function __construct(
        private readonly ConfigHandler $configHandler,
        private readonly Connection $connection,
        private readonly CategoryDefinition $definition,
        private readonly IteratorFactory $iteratorFactory,
        private readonly RouterInterface $router
    ) {
    }

    public function getDecorated(): AbstractUrlProvider
    {
        throw new DecorationPatternException(self::class);
    }

    public function getName(): string
    {
        return 'category';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function getUrls(SalesChannelContext $context, int $limit, ?int $offset = null): UrlResult
    {
        $categories = $this->getCategories($context, $limit, $offset);

        if (empty($categories)) {
            return new UrlResult([], null);
        }
        $keys = FetchModeHelper::keyPair($categories);

        $seoUrls = $this->getSeoUrls(array_values($keys), 'frontend.navigation.page', $context, $this->connection);

        /** @var array<string, array{seo_path_info: string}> $seoUrls */
        $seoUrls = FetchModeHelper::groupUnique($seoUrls);

        $urls = [];
        $url = new Url();

        foreach ($categories as $category) {
            $lastMod = $category['updated_at'] ?: $category['created_at'];

            $lastMod = (new \DateTime($lastMod))->format(Defaults::STORAGE_DATE_TIME_FORMAT);

            $newUrl = clone $url;

            if (isset($seoUrls[$category['id']])) {
                $newUrl->setLoc($seoUrls[$category['id']]['seo_path_info']);
            } else {
                $newUrl->setLoc($this->router->generate('frontend.navigation.page', ['navigationId' => $category['id']]));
            }

            $newUrl->setLastmod(new \DateTime($lastMod));
            $newUrl->setChangefreq(self::CHANGE_FREQ);
            $newUrl->setResource(CategoryEntity::class);
            $newUrl->setIdentifier($category['id']);

            $urls[] = $newUrl;
        }

        $keys = array_keys($keys);
        /** @var int|null $nextOffset */
        $nextOffset = array_pop($keys);

        return new UrlResult($urls, $nextOffset);
    }

    /**
     * @return list<array{id: string, created_at: string, updated_at: string}>
     */
    private function getCategories(SalesChannelContext $context, int $limit, ?int $offset): array
    {
        $lastId = null;
        if ($offset) {
            $lastId = ['offset' => $offset];
        }

        $iterator = $this->iteratorFactory->createIterator($this->definition, $lastId);
        $query = $iterator->getQuery();
        $query->setMaxResults($limit);

        $query->addSelect([
            '`category`.created_at',
            '`category`.updated_at',
        ]);

        $wheres = [];
        $categoryIds = array_filter([
            $context->getSalesChannel()->getNavigationCategoryId(),
            $context->getSalesChannel()->getFooterCategoryId(),
            $context->getSalesChannel()->getServiceCategoryId(),
        ]);

        foreach ($categoryIds as $id) {
            $wheres[] = '`category`.path LIKE ' . $query->createNamedParameter('%|' . $id . '|%');
        }

        $query->andWhere('(' . implode(' OR ', $wheres) . ')');
        $query->andWhere('`category`.version_id = :versionId');
        $query->andWhere('`category`.active = 1');
        $query->andWhere('`category`.type != :linkType');
        $query->andWhere('`category`.type != :folderType');

        $excludedCategoryIds = $this->getExcludedCategoryIds($context);
        if (!empty($excludedCategoryIds)) {
            $query->andWhere('`category`.id NOT IN (:categoryIds)');
            $query->setParameter('categoryIds', Uuid::fromHexToBytesList($excludedCategoryIds), ArrayParameterType::BINARY);
        }

        $query->setParameter('versionId', Uuid::fromHexToBytes(Defaults::LIVE_VERSION));
        $query->setParameter('linkType', CategoryDefinition::TYPE_LINK);
        $query->setParameter('folderType', CategoryDefinition::TYPE_FOLDER);

        /** @var list<array{id: string, created_at: string, updated_at: string}> $result */
        $result = $query->executeQuery()->fetchAllAssociative();

        return $result;
    }

    /**
     * @return array<string>
     */
    private function getExcludedCategoryIds(SalesChannelContext $salesChannelContext): array
    {
        $salesChannelId = $salesChannelContext->getSalesChannelId();

        $excludedUrls = $this->configHandler->get(ConfigHandler::EXCLUDED_URLS_KEY);
        if (empty($excludedUrls)) {
            return [];
        }

        $excludedUrls = array_filter($excludedUrls, static function (array $excludedUrl) use ($salesChannelId) {
            if ($excludedUrl['resource'] !== CategoryEntity::class) {
                return false;
            }

            if ($excludedUrl['salesChannelId'] !== $salesChannelId) {
                return false;
            }

            return true;
        });

        return array_column($excludedUrls, 'identifier');
    }
}
