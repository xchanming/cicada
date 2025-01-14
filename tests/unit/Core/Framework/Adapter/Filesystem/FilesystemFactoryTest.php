<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Filesystem;

use Cicada\Core\Framework\Adapter\Filesystem\Adapter\LocalFactory;
use Cicada\Core\Framework\Adapter\Filesystem\Exception\AdapterFactoryNotFoundException;
use Cicada\Core\Framework\Adapter\Filesystem\Exception\DuplicateFilesystemFactoryException;
use Cicada\Core\Framework\Adapter\Filesystem\FilesystemFactory;
use League\Flysystem\Visibility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FilesystemFactory::class)]
class FilesystemFactoryTest extends TestCase
{
    public function testMultipleSame(): void
    {
        static::expectException(DuplicateFilesystemFactoryException::class);
        new FilesystemFactory([new LocalFactory(), new LocalFactory()]);
    }

    public function testCreateLocalAdapter(): void
    {
        $factory = new FilesystemFactory([new LocalFactory()]);
        $adapter = $factory->factory([
            'type' => 'local',
            'config' => [
                'root' => __DIR__,
                'options' => [
                    'visibility' => Visibility::PUBLIC,
                ],
            ],
        ]);

        static::assertSame(Visibility::PUBLIC, $adapter->visibility(''));
    }

    public function testCreateUnknown(): void
    {
        $factory = new FilesystemFactory([new LocalFactory()]);
        static::expectException(AdapterFactoryNotFoundException::class);
        $factory->factory([
            'type' => 'test2',
        ]);
    }
}
