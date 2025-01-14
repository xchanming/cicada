<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Plugin\Command\Scaffolding;

use Cicada\Core\Framework\Plugin\Command\Scaffolding\PluginScaffoldConfiguration;
use Cicada\Core\Framework\Plugin\Command\Scaffolding\Stub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Stub::class)]
class PluginScaffoldConfigurationTest extends TestCase
{
    public function testAddOption(): void
    {
        $config = new PluginScaffoldConfiguration('TestPlugin', 'MyNamespace', '/path/to/directory');

        $config->addOption('option1', 'value1');
        $config->addOption('option2', 'value2');

        static::assertTrue($config->hasOption('option1'));
        static::assertTrue($config->hasOption('option2'));
        static::assertFalse($config->hasOption('option3'));
        static::assertEquals('value1', $config->getOption('option1'));
        static::assertEquals('value2', $config->getOption('option2'));
        static::assertNull($config->getOption('option3'));
    }

    public function testHasOption(): void
    {
        $config = new PluginScaffoldConfiguration('TestPlugin', 'MyNamespace', '/path/to/directory');

        $config->addOption('option1', 'value1');

        static::assertTrue($config->hasOption('option1'));
        static::assertFalse($config->hasOption('option2'));
    }

    public function testGetOption(): void
    {
        $config = new PluginScaffoldConfiguration('TestPlugin', 'MyNamespace', '/path/to/directory');

        $config->addOption('option1', 'value1');

        static::assertEquals('value1', $config->getOption('option1'));
        static::assertNull($config->getOption('option2'));
    }
}
