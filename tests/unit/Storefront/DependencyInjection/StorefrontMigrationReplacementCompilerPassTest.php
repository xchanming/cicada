<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\DependencyInjection;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationSource;
use Cicada\Storefront\DependencyInjection\StorefrontMigrationReplacementCompilerPass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(StorefrontMigrationReplacementCompilerPass::class)]
class StorefrontMigrationReplacementCompilerPassTest extends TestCase
{
    public function testProcessing(): void
    {
        $container = new ContainerBuilder();
        $container->register(MigrationSource::class . '.core.V6_3', MigrationSource::class)->setPublic(true);
        $container->register(MigrationSource::class . '.core.V6_4', MigrationSource::class)->setPublic(true);
        $container->register(MigrationSource::class . '.core.V6_5', MigrationSource::class)->setPublic(true);
        $container->register(MigrationSource::class . '.core.V6_6', MigrationSource::class)->setPublic(true);

        $container->addCompilerPass(new StorefrontMigrationReplacementCompilerPass());
        $container->compile();

        $calls = $container->getDefinition(MigrationSource::class . '.core.V6_3')->getMethodCalls();
        static::assertCount(1, $calls);

        static::assertSame('addDirectory', $calls[0][0]);
        static::assertStringContainsString('Migration/V6_3', $calls[0][1][0]);
        static::assertSame('Cicada\Storefront\Migration\V6_3', $calls[0][1][1]);
    }
}
