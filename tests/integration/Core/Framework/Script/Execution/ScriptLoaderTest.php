<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Script\Execution;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Script\Execution\ScriptLoader;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\AppSystemTestBehaviour;

/**
 * @internal
 */
class ScriptLoaderTest extends TestCase
{
    use AppSystemTestBehaviour;
    use IntegrationTestBehaviour;

    public function testGetScripts(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures');

        $loader = static::getContainer()->get(ScriptLoader::class);

        static::assertCount(
            1,
            $loader->get('include-case')
        );
        static::assertCount(
            2,
            $loader->get('multi-script-case')
        );
        static::assertCount(
            0,
            $loader->get('include')
        );
    }

    public function testGetInactiveScripts(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures', false);

        $loader = static::getContainer()->get(ScriptLoader::class);

        static::assertCount(1, $loader->get('include-case'));
        static::assertFalse($loader->get('include-case')[0]->isActive());

        static::assertCount(2, $loader->get('multi-script-case'));
        static::assertFalse($loader->get('multi-script-case')[0]->isActive());
        static::assertFalse($loader->get('multi-script-case')[1]->isActive());

        static::assertCount(0, $loader->get('include'));
    }
}
