<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Plugin\Command\Scaffolding\Generator;

use Cicada\Core\Framework\Plugin\Command\Scaffolding\Generator\TestsGenerator;
use Cicada\Core\Framework\Plugin\Command\Scaffolding\PluginScaffoldConfiguration;
use Cicada\Core\Framework\Plugin\Command\Scaffolding\StubCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TestsGenerator::class)]
class TestsGeneratorTest extends TestCase
{
    public function testCommandOptions(): void
    {
        $generator = new TestsGenerator();

        static::assertFalse($generator->hasCommandOption());
        static::assertEmpty($generator->getCommandOptionName());
        static::assertEmpty($generator->getCommandOptionDescription());
    }

    public function testGenerateStubs(): void
    {
        $generator = new TestsGenerator();
        $configuration = new PluginScaffoldConfiguration('TestPlugin', 'MyNamespace', '/path/to/directory');
        $stubCollection = new StubCollection();

        $generator->generateStubs($configuration, $stubCollection);

        static::assertCount(2, $stubCollection);

        static::assertTrue($stubCollection->has('phpunit.xml'));
        static::assertTrue($stubCollection->has('tests/TestBootstrap.php'));
    }
}
