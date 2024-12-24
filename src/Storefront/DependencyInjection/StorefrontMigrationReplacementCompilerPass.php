<?php declare(strict_types=1);

namespace Cicada\Storefront\DependencyInjection;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationSource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[Package('storefront')]
class StorefrontMigrationReplacementCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $migrationPath = \dirname(__DIR__) . '/Migration';

        $migrationSourceV3 = $container->getDefinition(MigrationSource::class . '.core.V6_3');
        $migrationSourceV3->addMethodCall('addDirectory', [$migrationPath . '/V6_3', 'Cicada\Storefront\Migration\V6_3']);

        $migrationSourceV4 = $container->getDefinition(MigrationSource::class . '.core.V6_4');
        $migrationSourceV4->addMethodCall('addDirectory', [$migrationPath . '/V6_4', 'Cicada\Storefront\Migration\V6_4']);

        $majors = ['V6_5', 'V6_6'];
        foreach ($majors as $major) {
            $migrationPathV5 = $migrationPath . '/' . $major;

            if (\is_dir($migrationPathV5)) {
                $migrationSource = $container->getDefinition(MigrationSource::class . '.core.' . $major);
                $migrationSource->addMethodCall('addDirectory', [$migrationPathV5, 'Cicada\Storefront\Migration\\' . $major]);
            }
        }
    }
}
