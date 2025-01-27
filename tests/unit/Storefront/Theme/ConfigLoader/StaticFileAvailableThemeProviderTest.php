<?php
declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\ConfigLoader;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Storefront\Theme\ConfigLoader\StaticFileAvailableThemeProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StaticFileAvailableThemeProvider::class)]
class StaticFileAvailableThemeProviderTest extends TestCase
{
    public function testFileNotExisting(): void
    {
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Cannot find theme configuration. Did you run bin/console theme:dump');

        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $s = new StaticFileAvailableThemeProvider($fs);
        $s->load(Context::createDefaultContext(), false);
    }

    public function testFileExists(): void
    {
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $fs->write(StaticFileAvailableThemeProvider::THEME_INDEX, json_encode(['test' => 'test'], \JSON_THROW_ON_ERROR));

        $s = new StaticFileAvailableThemeProvider($fs);
        static::assertSame(['test' => 'test'], $s->load(Context::createDefaultContext(), false));
    }

    public function testCallGetDecoratedThrowsError(): void
    {
        static::expectException(DecorationPatternException::class);

        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $s = new StaticFileAvailableThemeProvider($fs);
        $s->getDecorated();
    }
}
