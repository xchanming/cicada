<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @internal
 */
#[Package('core')]
class TestBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RemoveDeprecatedServicesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);

        parent::build($container);
    }
}
