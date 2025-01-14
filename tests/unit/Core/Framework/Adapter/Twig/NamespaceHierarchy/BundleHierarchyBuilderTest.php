<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Twig\NamespaceHierarchy;

use Cicada\Core\Framework\Adapter\Twig\NamespaceHierarchy\BundleHierarchyBuilder;
use Cicada\Core\Framework\Bundle;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
#[CoversClass(BundleHierarchyBuilder::class)]
class BundleHierarchyBuilderTest extends TestCase
{
    /**
     * @param array<string, int> $plugins
     * @param array<string, int> $apps
     * @param array<int, string> $expectedSorting
     */
    #[DataProvider('sortingProvider')]
    public function testSortingOfTemplates(array $plugins, array $apps, array $expectedSorting): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $bundles = [];

        $path = __DIR__ . '/../../../../../../integration/Core/Framework/Adapter/Twig/fixtures/Plugins/TestPlugin1/';

        foreach ($plugins as $plugin => $prio) {
            $bundles[] = new MockBundle($plugin, $prio, $path);
        }

        $kernel->method('getBundles')->willReturn($bundles);

        $connection = $this->createMock(Connection::class);

        $dbApps = [];

        foreach ($apps as $app => $prio) {
            $dbApps[$app] = [
                'version' => '1.0.0',
                'template_load_priority' => $prio,
            ];
        }

        $connection->method('fetchAllAssociativeIndexed')->willReturn($dbApps);

        $builder = new BundleHierarchyBuilder(
            $kernel,
            $connection
        );

        static::assertSame($expectedSorting, array_keys($builder->buildNamespaceHierarchy([])));
    }

    /**
     * @return iterable<string, array<array<int|string, int|string>>>
     */
    public static function sortingProvider(): iterable
    {
        yield 'all with default prio' => [
            ['TestPluginB' => 0, 'TestPluginA' => 0],
            ['TestPluginAppB' => 0, 'TestPluginAppA' => 0],
            ['TestPluginAppB', 'TestPluginAppA', 'TestPluginA', 'TestPluginB'],
        ];

        yield 'one plugin with high prio' => [
            ['TestPluginB' => -500, 'TestPluginA' => 0],
            ['TestPluginAppB' => 0, 'TestPluginAppA' => 0],
            ['TestPluginB', 'TestPluginAppB', 'TestPluginAppA', 'TestPluginA'],
        ];

        yield 'both plugin with high prio to get higher than apps' => [
            ['TestPluginB' => -500, 'TestPluginA' => -400],
            ['TestPluginAppB' => 0, 'TestPluginAppA' => 0],
            ['TestPluginB', 'TestPluginA', 'TestPluginAppB', 'TestPluginAppA'],
        ];

        yield 'mixed prio by apps and extensions' => [
            ['TestPluginB' => -500, 'TestPluginA' => -400],
            ['TestPluginAppB' => -600, 'TestPluginAppA' => 0],
            ['TestPluginAppB', 'TestPluginB', 'TestPluginA', 'TestPluginAppA'],
        ];

        yield 'anyone has priority' => [
            ['TestPluginB' => -500, 'TestPluginA' => -400],
            ['TestPluginAppB' => -600, 'TestPluginAppA' => -700],
            ['TestPluginAppA', 'TestPluginAppB', 'TestPluginB', 'TestPluginA'],
        ];

        yield 'same priority the database order matters' => [
            ['TestPluginB' => -500, 'TestPluginA' => -400],
            ['TestPluginAppB' => -600, 'TestPluginAppA' => -600],
            ['TestPluginAppB', 'TestPluginAppA', 'TestPluginB', 'TestPluginA'],
        ];
    }
}

/**
 * @internal
 */
class MockBundle extends Bundle
{
    public function __construct(
        string $name,
        private readonly int $templatePriority,
        string $path
    ) {
        $this->name = $name;
        $this->path = $path;
    }

    public function getTemplatePriority(): int
    {
        return $this->templatePriority;
    }
}
