<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App;

use Cicada\Core\Framework\App\ActiveAppsLoader;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\AppSystemTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ActiveAppsLoaderTest extends TestCase
{
    use AppSystemTestBehaviour;
    use IntegrationTestBehaviour;

    private ActiveAppsLoader $activeAppsLoader;

    protected function setUp(): void
    {
        $this->activeAppsLoader = static::getContainer()->get(ActiveAppsLoader::class);
    }

    public function testGetActiveAppsWithActiveApp(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/Manifest/_fixtures/test');

        $activeApps = $this->activeAppsLoader->getActiveApps();
        static::assertCount(1, $activeApps);
        static::assertSame('test', $activeApps[0]['name']);
    }

    public function testGetActiveAppsWithInactiveApp(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/Manifest/_fixtures/test', false);

        $activeApps = $this->activeAppsLoader->getActiveApps();
        static::assertCount(0, $activeApps);
    }
}
