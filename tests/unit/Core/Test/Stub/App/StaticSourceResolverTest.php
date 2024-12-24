<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Test\Stub\App;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\Meta\Metadata;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Stub\App\StaticSourceResolver;
use Cicada\Core\Test\Stub\Framework\Util\StaticFilesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(StaticSourceResolver::class)]
class StaticSourceResolverTest extends TestCase
{
    public function testCanResolveManifestToType(): void
    {
        $app = $this->createMock(Manifest::class);

        $resolver = new StaticSourceResolver();

        static::assertSame('static', $resolver->resolveSourceType($app));
    }

    public function testFilesystemMethodsUseConfiguredFilesystem(): void
    {
        $fs = new StaticFilesystem();

        $resolver = new StaticSourceResolver([
            'myApp' => $fs,
        ]);

        $metadata = Metadata::fromArray(['name' => 'myApp', 'label' => [], 'author' => 'myApp', 'copyright' => 'none', 'license' => 'none', 'version' => '99']);
        $manifest = $this->createMock(Manifest::class);
        $manifest->method('getMetadata')->willReturn($metadata);
        $app = (new AppEntity())->assign(['name' => 'myApp']);

        static::assertSame($fs, $resolver->filesystemForManifest($manifest));
        static::assertSame($fs, $resolver->filesystemForApp($app));
        static::assertSame($fs, $resolver->filesystemForAppName('myApp'));
    }

    public function testFilesystemMethodsReturnDefaultFilesystemWhenNonConfigured(): void
    {
        $fs = new StaticFilesystem();

        $resolver = new StaticSourceResolver([
            'someOtherApp' => $fs,
        ]);

        $metadata = Metadata::fromArray(['name' => 'myApp', 'label' => [], 'author' => 'myApp', 'copyright' => 'none', 'license' => 'none', 'version' => '99']);
        $manifest = $this->createMock(Manifest::class);
        $manifest->method('getMetadata')->willReturn($metadata);
        $app = (new AppEntity())->assign(['name' => 'myApp']);

        static::assertNotSame($fs, $resolver->filesystemForManifest($manifest));
        static::assertNotSame($fs, $resolver->filesystemForApp($app));
        static::assertNotSame($fs, $resolver->filesystemForAppName('myApp'));
    }
}
