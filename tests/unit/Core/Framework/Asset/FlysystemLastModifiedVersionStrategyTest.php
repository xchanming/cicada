<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Asset;

use Cicada\Core\Framework\Adapter\Asset\FlysystemLastModifiedVersionStrategy;
use Cicada\Core\Framework\Adapter\Filesystem\MemoryFilesystemAdapter;
use Cicada\Core\Framework\Log\Package;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(FlysystemLastModifiedVersionStrategy::class)]
class FlysystemLastModifiedVersionStrategyTest extends TestCase
{
    private Filesystem $fs;

    private UrlPackage $asset;

    private FlysystemLastModifiedVersionStrategy $strategy;

    protected function setUp(): void
    {
        $this->fs = new Filesystem(new MemoryFilesystemAdapter());
        $this->strategy = new FlysystemLastModifiedVersionStrategy('test', $this->fs, new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter()));
        $this->asset = new UrlPackage(['http://xchanming.com'], $this->strategy);
    }

    public function testNonExistentFile(): void
    {
        $url = $this->asset->getUrl('test');
        static::assertSame('http://xchanming.com/test', $url);
    }

    public function testExistsFile(): void
    {
        $this->fs->write('testFile', 'yea');
        $lastModified = (string) $this->fs->lastModified('testFile');
        $url = $this->asset->getUrl('testFile');
        static::assertSame('http://xchanming.com/testFile?' . $lastModified, $url);
    }

    public function testApplyDoesSameAsGetVersion(): void
    {
        static::assertSame($this->strategy->getVersion('foo'), $this->strategy->getVersion('foo'));
    }

    public function testFolder(): void
    {
        $this->fs->write('folder/file', 'test');

        static::assertSame('http://xchanming.com/folder', $this->asset->getUrl('folder'));
        static::assertSame('http://xchanming.com/not_existing/bla', $this->asset->getUrl('not_existing/bla'));
        static::assertSame('http://xchanming.com/folder', $this->asset->getUrl('folder'));
    }

    public function testWithEmptyString(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->expects(static::never())->method('lastModified');

        $strategy = new FlysystemLastModifiedVersionStrategy('test', $fs, new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter()));

        static::assertSame('', $strategy->getVersion(''));
    }
}
