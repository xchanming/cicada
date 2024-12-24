<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\ExtensionRegistry;
use Cicada\Core\Framework\Feature\FeatureFlagRegistry;
use Cicada\Core\Framework\Framework;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelDefinitionInstanceRegistry;
use Symfony\Component\DependencyInjection\Container;

/**
 * @internal
 */
#[CoversClass(Framework::class)]
class FrameworkTest extends TestCase
{
    public function testTemplatePriority(): void
    {
        $framework = new Framework();

        static::assertEquals(-1, $framework->getTemplatePriority());
    }

    public function testFeatureFlagRegisteredOnBoot(): void
    {
        $container = new Container();
        $registry = $this->createMock(FeatureFlagRegistry::class);
        $registry->expects(static::once())->method('register');

        $container->set(FeatureFlagRegistry::class, $registry);
        $container->set(DefinitionInstanceRegistry::class, $this->createMock(DefinitionInstanceRegistry::class));
        $container->set(SalesChannelDefinitionInstanceRegistry::class, $this->createMock(SalesChannelDefinitionInstanceRegistry::class));
        $container->set(ExtensionRegistry::class, $this->createMock(ExtensionRegistry::class));
        $container->setParameter('kernel.cache_dir', '/tmp');
        $container->setParameter('cicada.cache.cache_compression', true);
        $container->setParameter('cicada.cache.cache_compression_method', 'gzip');
        $framework = new Framework();
        $framework->setContainer($container);

        $framework->boot();
    }
}
