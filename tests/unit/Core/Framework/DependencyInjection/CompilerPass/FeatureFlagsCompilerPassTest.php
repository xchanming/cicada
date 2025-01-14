<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DependencyInjection\CompilerPass;

use Cicada\Core\Framework\DependencyInjection\CompilerPass\FeatureFlagCompilerPass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 */
#[CoversClass(FeatureFlagCompilerPass::class)]
class FeatureFlagsCompilerPassTest extends TestCase
{
    private FeatureFlagCompilerPass $compilerPass;

    protected function setUp(): void
    {
        $this->compilerPass = new FeatureFlagCompilerPass();
    }

    public function testItRemovesServiceIfInactive(): void
    {
        $definition = new Definition();
        $definition->addTag('cicada.feature', [
            'flag' => 'FEATURE_NEXT_123',
        ]);

        $container = new ContainerBuilder();
        $container->setDefinitions([
            'feature_service' => $definition,
        ]);

        $container->setParameter('cicada.feature.flags', [
            'FEATURE_NEXT_123' => [
                'name' => 'FEATURE_NEXT_123',
                'active' => false,
                'default' => true,
                'major' => true,
                'description' => 'This is a test feature',
            ],
        ]);
        $this->compilerPass->process($container);

        static::assertFalse($container->hasDefinition('feature_service'));
    }

    public function testItKeepServiceIfActive(): void
    {
        $definition = new Definition();
        $definition->addTag('cicada.feature', [
            'flag' => 'FEATURE_NEXT_123',
        ]);

        $container = new ContainerBuilder();
        $container->setDefinitions([
            'feature_service' => $definition,
        ]);

        $container->setParameter('cicada.feature.flags', [
            'FEATURE_NEXT_123' => [
                'name' => 'FEATURE_NEXT_123',
                'active' => true,
                'default' => true,
                'major' => true,
                'description' => 'This is a test feature',
            ],
        ]);
        $this->compilerPass->process($container);

        static::assertTrue($container->hasDefinition('feature_service'));
    }
}
