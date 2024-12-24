<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme;

use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Theme\SeedingThemePathBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SeedingThemePathBuilder::class)]
class SeedingThemePathBuilderTest extends TestCase
{
    public function testAssemblePathDoesNotChangeWithoutChangedSeed(): void
    {
        $pathBuilder = new SeedingThemePathBuilder(new StaticSystemConfigService());

        $path = $pathBuilder->assemblePath(TestDefaults::SALES_CHANNEL, 'theme');

        static::assertEquals($path, $pathBuilder->assemblePath(TestDefaults::SALES_CHANNEL, 'theme'));
    }

    public function testAssembledPathAfterSavingIsTheSameAsPreviouslyGenerated(): void
    {
        $pathBuilder = new SeedingThemePathBuilder(new StaticSystemConfigService());

        $generatedPath = $pathBuilder->generateNewPath(TestDefaults::SALES_CHANNEL, 'theme', 'foo');

        // assert seeding is taking into account when generating a new path
        static::assertNotEquals($generatedPath, $pathBuilder->assemblePath(TestDefaults::SALES_CHANNEL, 'theme'));

        $pathBuilder->saveSeed(TestDefaults::SALES_CHANNEL, 'theme', 'foo');

        // assert that the path is the same after saving
        static::assertEquals($generatedPath, $pathBuilder->assemblePath(TestDefaults::SALES_CHANNEL, 'theme'));
    }
}
