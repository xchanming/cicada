<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\DataResolver\Element;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\CriteriaCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
interface CmsElementResolverInterface
{
    public function getType(): string;

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection;

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void;
}
