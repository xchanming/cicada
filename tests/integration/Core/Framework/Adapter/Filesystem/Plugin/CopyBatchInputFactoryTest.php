<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Adapter\Filesystem\Plugin;

use Cicada\Core\Framework\Adapter\Filesystem\Plugin\CopyBatchInputFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[CoversClass(CopyBatchInputFactory::class)]
class CopyBatchInputFactoryTest extends TestCase
{
    public function testCopyBatchInputFactoryUsingDirectory(): void
    {
        $fs = new Filesystem();
        $fs->mkdir(__DIR__ . '/test');
        $fs->touch([
            __DIR__ . '/test/file1',
            __DIR__ . '/test/file2',
            __DIR__ . '/test/file3',
        ]);

        $copyBatch = (new CopyBatchInputFactory())->fromDirectory(__DIR__ . '/test', __DIR__ . '/target');
        sort($copyBatch);

        static::assertCount(3, $copyBatch);

        static::assertSame(__DIR__ . '/test/file1', $copyBatch[0]->getSourceFile());
        static::assertSame([__DIR__ . '/target/test/file1'], $copyBatch[0]->getTargetFiles());

        static::assertSame(__DIR__ . '/test/file2', $copyBatch[1]->getSourceFile());
        static::assertSame([__DIR__ . '/target/test/file2'], $copyBatch[1]->getTargetFiles());

        static::assertSame(__DIR__ . '/test/file3', $copyBatch[2]->getSourceFile());
        static::assertSame([__DIR__ . '/target/test/file3'], $copyBatch[2]->getTargetFiles());

        $fs->remove(__DIR__ . '/test');
    }
}
