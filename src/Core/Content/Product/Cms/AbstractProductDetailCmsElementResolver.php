<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Cms;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\CriteriaCollection;
use Cicada\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\FieldConfig;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductDefinition;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
abstract class AbstractProductDetailCmsElementResolver extends AbstractCmsElementResolver
{
    abstract public function getType(): string;

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();

        if ($resolverContext instanceof EntityResolverContext && $resolverContext->getDefinition() instanceof SalesChannelProductDefinition) {
            $productConfig = new FieldConfig('product', FieldConfig::SOURCE_MAPPED, $resolverContext->getEntity()->get('id'));
            $config->add($productConfig);
        }

        $productConfig = $config->get('product');
        if ($productConfig === null || $productConfig->isMapped() || $productConfig->getValue() === null) {
            return null;
        }

        $criteria = $this->createBestVariantCriteria($productConfig->getStringValue());

        $criteriaCollection = new CriteriaCollection();
        $criteriaCollection->add('product_' . $slot->getUniqueIdentifier(), SalesChannelProductDefinition::class, $criteria);

        return $criteriaCollection;
    }

    abstract public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void;

    protected function getSlotProduct(CmsSlotEntity $slot, ElementDataCollection $result, string $productId): ?SalesChannelProductEntity
    {
        $searchResult = $result->get('product_' . $slot->getUniqueIdentifier());
        if ($searchResult === null) {
            return null;
        }

        $bestVariant = $searchResult->filterByProperty('parentId', $productId)->first();

        /** @var SalesChannelProductEntity|null $product */
        $product = $bestVariant ?? $searchResult->get($productId);

        return $product;
    }

    private function createBestVariantCriteria(string $productId): Criteria
    {
        $criteria = (new Criteria())
            ->addFilter(new OrFilter([
                new EqualsFilter('product.parentId', $productId),
                new EqualsFilter('id', $productId),
            ]))
            ->addGroupField(new FieldGrouping('displayGroup'));

        $criteria->setTitle('cms::product-detail-static');

        return $criteria;
    }
}
