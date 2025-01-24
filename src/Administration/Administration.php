<?php declare(strict_types=1);

namespace Cicada\Administration;

use Cicada\Administration\DependencyInjection\AdministrationMigrationCompilerPass;
use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Parameter\AdditionalBundleParameters;
use Pentatrion\ViteBundle\PentatrionViteBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
#[Package('framework')]
class Administration extends Bundle
{
    public function getTemplatePriority(): int
    {
        return -1;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->buildDefaultConfig($container);

        $container->addCompilerPass(new AdministrationMigrationCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }

    public function getAdditionalBundles(AdditionalBundleParameters $parameters): array
    {
        return [
            new PentatrionViteBundle(),
        ];
    }
}
