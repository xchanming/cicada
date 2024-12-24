<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Twig\NamespaceHierarchy;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class NamespaceHierarchyBuilder
{
    /**
     * @internal
     *
     * @param TemplateNamespaceHierarchyBuilderInterface[] $namespaceHierarchyBuilders
     */
    public function __construct(private readonly iterable $namespaceHierarchyBuilders)
    {
    }

    public function buildHierarchy(): array
    {
        $hierarchy = [];

        foreach ($this->namespaceHierarchyBuilders as $hierarchyBuilder) {
            $hierarchy = $hierarchyBuilder->buildNamespaceHierarchy($hierarchy);
        }

        return $hierarchy;
    }
}
