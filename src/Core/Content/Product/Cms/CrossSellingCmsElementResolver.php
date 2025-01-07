<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Cms;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Content\Cms\SalesChannel\Struct\CrossSellingStruct;
use Cicada\Core\Content\Product\SalesChannel\CrossSelling\AbstractProductCrossSellingRoute;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Request;

#[Package('discovery')]
class CrossSellingCmsElementResolver extends AbstractProductDetailCmsElementResolver
{
    final public const TYPE = 'cross-selling';

    /**
     * @internal
     */
    public function __construct(private readonly AbstractProductCrossSellingRoute $crossSellingLoader)
    {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();
        $context = $resolverContext->getSalesChannelContext();
        $struct = new CrossSellingStruct();
        $slot->setData($struct);

        $productConfig = $config->get('product');

        if ($productConfig === null || $productConfig->getValue() === null) {
            return;
        }

        $product = null;

        if ($productConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
            $product = $this->resolveEntityValue($resolverContext->getEntity(), $productConfig->getStringValue());
        }

        if ($productConfig->isStatic()) {
            $product = $this->getSlotProduct($slot, $result, $productConfig->getStringValue());
        }

        if (!$product instanceof SalesChannelProductEntity) {
            return;
        }

        $crossSellings = $this->crossSellingLoader->load($product->getId(), new Request(), $context, new Criteria())->getResult();

        if ($crossSellings->count()) {
            $struct->setCrossSellings($crossSellings);
        }
    }
}
