<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\DataResolver\Element\Fixtures;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\CriteriaCollection;
use Cicada\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\FieldConfig;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * @internal
 */
class TestCmsElementResolver extends AbstractCmsElementResolver
{
    public function runResolveEntityValue(?Entity $entity, string $path): mixed
    {
        return $this->resolveEntityValue($entity, $path);
    }

    public function runResolveEntityValueToString(?Entity $entity, string $path, EntityResolverContext $resolverContext): string
    {
        return $this->resolveEntityValueToString($entity, $path, $resolverContext);
    }

    public function runResolveDefinitionField(EntityDefinition $definition, string $path): ?Field
    {
        return $this->resolveDefinitionField($definition, $path);
    }

    public function runResolveCriteriaForLazyLoadedRelations(EntityResolverContext $resolverContext, FieldConfig $config): ?Criteria
    {
        return $this->resolveCriteriaForLazyLoadedRelations($resolverContext, $config);
    }

    public function getType(): string
    {
        return 'abstract-test';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
    }
}
