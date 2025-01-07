<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Cms;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Content\Product\Cms\AbstractProductDetailCmsElementResolver;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('discovery')]
class TestProductDetailCmsElementResolver extends AbstractProductDetailCmsElementResolver
{
    public function getType(): string
    {
        return 'test';
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        // nth
    }

    public function runGetSlotProduct(CmsSlotEntity $slot, ElementDataCollection $result, string $productId): ?SalesChannelProductEntity
    {
        return $this->getSlotProduct($slot, $result, $productId);
    }
}
