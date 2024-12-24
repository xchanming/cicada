<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Plugin\KernelPluginLoader;

use Cicada\Core\Framework\Plugin\KernelPluginLoader\DbalKernelPluginLoader;
use Cicada\Core\Framework\Test\Plugin\PluginIntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class DbalKernelPluginLoaderTest extends TestCase
{
    use PluginIntegrationTestBehaviour;

    public function testLoadNoPlugins(): void
    {
        $loader = new DbalKernelPluginLoader($this->classLoader, null, $this->connection);
        $loader->initializePlugins(TEST_PROJECT_DIR);

        static::assertEmpty($loader->getPluginInfos());
        static::assertEmpty($loader->getPluginInstances()->all());
    }

    public function testLoadNoInit(): void
    {
        $plugin = $this->getActivePlugin();
        $this->insertPlugin($plugin);

        $loader = new DbalKernelPluginLoader($this->classLoader, null, $this->connection);
        static::assertEmpty($loader->getPluginInfos());
    }

    public function testLoadPlugins(): void
    {
        $plugin = $this->getActivePlugin();
        $this->insertPlugin($plugin);

        $loader = new DbalKernelPluginLoader($this->classLoader, null, $this->connection);
        $loader->initializePlugins(TEST_PROJECT_DIR);

        static::assertNotEmpty($loader->getPluginInfos());
    }
}
