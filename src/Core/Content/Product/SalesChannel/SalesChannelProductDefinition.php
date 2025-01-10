<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceField;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiCriteriaAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ListField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ObjectField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelDefinitionInterface;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('inventory')]
class SalesChannelProductDefinition extends ProductDefinition implements SalesChannelDefinitionInterface
{
    private const PRICE_BASELINE = ['taxId', 'unitId', 'referenceUnit', 'purchaseUnit'];

    public function getEntityClass(): string
    {
        return SalesChannelProductEntity::class;
    }

    public function getCollectionClass(): string
    {
        return SalesChannelProductCollection::class;
    }

    public function processCriteria(Criteria $criteria, SalesChannelContext $context): void
    {
        if (!$this->hasAvailableFilter($criteria)) {
            $criteria->addFilter(
                new ProductAvailableFilter($context->getSalesChannelId(), ProductVisibilityDefinition::VISIBILITY_LINK)
            );
        }

        if ($criteria->getNestingLevel() !== Criteria::ROOT_NESTING_LEVEL) {
            return;
        }

        if (empty($criteria->getFields())) {
            $criteria
                ->addAssociation('prices')
                ->addAssociation('unit')
                ->addAssociation('deliveryTime')
                ->addAssociation('cover.media')
                ->addAssociation('tax')
            ;
        }

        if ($criteria->hasAssociation('productReviews')) {
            $association = $criteria->getAssociation('productReviews');
            $activeReviewsFilter = new MultiFilter(MultiFilter::CONNECTION_OR, [new EqualsFilter('status', true)]);
            if ($customer = $context->getCustomer()) {
                $activeReviewsFilter->addQuery(new EqualsFilter('customerId', $customer->getId()));
            }

            $association->addFilter($activeReviewsFilter);
        }
    }

    protected function defineFields(): FieldCollection
    {
        $fields = parent::defineFields();

        $fields->add(
            (new JsonField('calculated_price', 'calculatedPrice'))->addFlags(new ApiAware(), new Runtime(\array_merge(self::PRICE_BASELINE, ['price', 'prices'])))
        );
        $fields->add(
            (new ListField('calculated_prices', 'calculatedPrices'))->addFlags(new ApiAware(), new Runtime(\array_merge(self::PRICE_BASELINE, ['prices'])))
        );
        $fields->add(
            (new IntField('calculated_max_purchase', 'calculatedMaxPurchase'))->addFlags(new ApiAware(), new Runtime(['maxPurchase']))
        );
        $fields->add(
            (new JsonField('calculated_cheapest_price', 'calculatedCheapestPrice'))->addFlags(new ApiAware(), new Runtime(\array_merge(self::PRICE_BASELINE, ['cheapestPrice'])))
        );
        $fields->add(
            (new BoolField('is_new', 'isNew'))->addFlags(new ApiAware(), new Runtime(['releaseDate']))
        );
        $fields->add(
            (new OneToOneAssociationField('seoCategory', 'seoCategory', 'id', CategoryDefinition::class))->addFlags(new ApiAware(), new Runtime())
        );
        $fields->add(
            (new CheapestPriceField('cheapest_price', 'cheapestPrice'))->addFlags(new WriteProtected(), new Inherited(), new ApiCriteriaAware())
        );
        $fields->add(
            (new ObjectField('cheapest_price_container', 'cheapestPriceContainer'))->addFlags(new Runtime())
        );
        $fields->add(
            (new ObjectField('sortedProperties', 'sortedProperties'))->addFlags(new Runtime(), new ApiAware())
        );

        return $fields;
    }

    private function hasAvailableFilter(Criteria $criteria): bool
    {
        foreach ($criteria->getFilters() as $filter) {
            if ($filter instanceof ProductAvailableFilter) {
                return true;
            }
        }

        return false;
    }
}
