<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\DataResolver\ResolverContext;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('buyers-experience')]
class EntityResolverContext extends ResolverContext
{
    public function __construct(
        SalesChannelContext $context,
        Request $request,
        private readonly EntityDefinition $definition,
        private readonly Entity $entity
    ) {
        parent::__construct($context, $request);
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    public function getDefinition(): EntityDefinition
    {
        return $this->definition;
    }
}
