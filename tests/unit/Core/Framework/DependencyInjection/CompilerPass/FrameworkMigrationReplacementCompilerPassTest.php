<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DependencyInjection\CompilerPass;

use Cicada\Core\Framework\DependencyInjection\CompilerPass\FrameworkMigrationReplacementCompilerPass;
use Cicada\Core\Framework\Migration\MigrationSource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
#[CoversClass(FrameworkMigrationReplacementCompilerPass::class)]
class FrameworkMigrationReplacementCompilerPassTest extends TestCase
{
    public function testProcessing(): void
    {
        $container = new ContainerBuilder();
        $container->register(MigrationSource::class . '.core.V6_3', MigrationSource::class)->setPublic(true);
        $container->register(MigrationSource::class . '.core.V6_4', MigrationSource::class)->setPublic(true);
        $container->register(MigrationSource::class . '.core.V6_5', MigrationSource::class)->setPublic(true);
        $container->register(MigrationSource::class . '.core.V6_6', MigrationSource::class)->setPublic(true);
        $container->register(MigrationSource::class . '.core.V6_7', MigrationSource::class)->setPublic(true);
        $container->register(MigrationSource::class . '.core.V6_8', MigrationSource::class)->setPublic(true);

        $container->addCompilerPass(new FrameworkMigrationReplacementCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->compile(false);

        $calls = $container->getDefinition(MigrationSource::class . '.core.V6_3')->getMethodCalls();
        static::assertCount(1, $calls);

        static::assertSame('addDirectory', $calls[0][0]);
        static::assertStringContainsString('Migration/V6_3', $calls[0][1][0]);
        static::assertSame('Cicada\Core\Migration\V6_3', $calls[0][1][1]);
    }
}
