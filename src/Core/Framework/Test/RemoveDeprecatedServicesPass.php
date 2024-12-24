<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
#[Package('core')]
class RemoveDeprecatedServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isDeprecated()) {
                $container->removeDefinition($id);
            }
        }
    }
}
