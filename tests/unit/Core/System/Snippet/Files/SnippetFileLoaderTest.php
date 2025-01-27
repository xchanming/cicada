<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Snippet\Files;

use Cicada\Core\Framework\App\ActiveAppsLoader;
use Cicada\Core\Framework\App\Lifecycle\AppLoader;
use Cicada\Core\System\Snippet\Files\AppSnippetFileLoader;
use Cicada\Core\System\Snippet\Files\GenericSnippetFile;
use Cicada\Core\System\Snippet\Files\SnippetFileCollection;
use Cicada\Core\System\Snippet\Files\SnippetFileLoader;
use Cicada\Tests\Unit\Core\System\Snippet\Files\_fixtures\BaseSnippetSet\BaseSnippetSet;
use Cicada\Tests\Unit\Core\System\Snippet\Files\_fixtures\CicadaBundleWithSnippets\CicadaBundleWithSnippets;
use Cicada\Tests\Unit\Core\System\Snippet\Files\_fixtures\SnippetSet\SnippetSet;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SnippetFileLoader::class)]
class SnippetFileLoaderTest extends TestCase
{
    public function testLoadSnippetsFromCicadaBundle(): void
    {
        $kernel = new MockedKernel(
            [
                'CicadaBundleWithSnippets' => new CicadaBundleWithSnippets(),
            ]
        );

        $collection = new SnippetFileCollection();

        $snippetFileLoader = new SnippetFileLoader(
            $kernel,
            $this->createMock(Connection::class),
            $this->createMock(AppSnippetFileLoader::class),
            new ActiveAppsLoader(
                $this->createMock(Connection::class),
                $this->createMock(AppLoader::class),
                '/'
            )
        );

        $snippetFileLoader->loadSnippetFilesIntoCollection($collection);

        static::assertCount(2, $collection);

        $snippetFile = $collection->getSnippetFilesByIso('zh-CN')[0];
        static::assertEquals('storefront.zh-CN', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/CicadaBundleWithSnippets/Resources/snippet/storefront.zh-CN.json',
            $snippetFile->getPath()
        );
        static::assertEquals('zh-CN', $snippetFile->getIso());
        static::assertEquals('Cicada', $snippetFile->getAuthor());
        static::assertFalse($snippetFile->isBase());

        $snippetFile = $collection->getSnippetFilesByIso('en-GB')[0];
        static::assertEquals('storefront.en-GB', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/CicadaBundleWithSnippets/Resources/snippet/storefront.en-GB.json',
            $snippetFile->getPath()
        );
        static::assertEquals('en-GB', $snippetFile->getIso());
        static::assertEquals('Cicada', $snippetFile->getAuthor());
        static::assertEquals('CicadaBundleWithSnippets', $snippetFile->getTechnicalName());
        static::assertFalse($snippetFile->isBase());
    }

    public function testLoadSnippetFilesIntoCollectionDoesNotOverwriteFiles(): void
    {
        $kernel = new MockedKernel(
            [
                'CicadaBundleWithSnippets' => new CicadaBundleWithSnippets(),
            ]
        );

        $collection = new SnippetFileCollection([
            new GenericSnippetFile(
                'test',
                __DIR__ . '/_fixtures/CicadaBundleWithSnippets/Resources/snippet/storefront.zh-CN.json',
                'xx-XX',
                'test Author',
                true,
                'CicadaBundleWithSnippets'
            ),
            new GenericSnippetFile(
                'test',
                __DIR__ . '/_fixtures/CicadaBundleWithSnippets/Resources/snippet/storefront.en-GB.json',
                'yy-YY',
                'test Author',
                true,
                'CicadaBundleWithSnippets'
            ),
        ]);

        $snippetFileLoader = new SnippetFileLoader(
            $kernel,
            $this->createMock(Connection::class),
            $this->createMock(AppSnippetFileLoader::class),
            new ActiveAppsLoader(
                $this->createMock(Connection::class),
                $this->createMock(AppLoader::class),
                '/'
            )
        );

        $snippetFileLoader->loadSnippetFilesIntoCollection($collection);

        static::assertCount(2, $collection);

        $snippetFile = $collection->getSnippetFilesByIso('xx-XX')[0];
        static::assertEquals('test', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/CicadaBundleWithSnippets/Resources/snippet/storefront.zh-CN.json',
            $snippetFile->getPath()
        );
        static::assertEquals('xx-XX', $snippetFile->getIso());
        static::assertEquals('test Author', $snippetFile->getAuthor());
        static::assertTrue($snippetFile->isBase());

        $snippetFile = $collection->getSnippetFilesByIso('yy-YY')[0];
        static::assertEquals('test', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/CicadaBundleWithSnippets/Resources/snippet/storefront.en-GB.json',
            $snippetFile->getPath()
        );
        static::assertEquals('yy-YY', $snippetFile->getIso());
        static::assertEquals('test Author', $snippetFile->getAuthor());
        static::assertEquals('CicadaBundleWithSnippets', $snippetFile->getTechnicalName());
        static::assertTrue($snippetFile->isBase());
    }

    public function testLoadSnippetsFromPlugin(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())->method('fetchAllKeyValue')->willReturn([
            SnippetSet::class => 'Plugin Manufacturer',
        ]);

        $kernel = new MockedKernel(
            [
                'SnippetSet' => new SnippetSet(true, __DIR__),
            ]
        );

        $collection = new SnippetFileCollection();

        $snippetFileLoader = new SnippetFileLoader(
            $kernel,
            $connection,
            $this->createMock(AppSnippetFileLoader::class),
            new ActiveAppsLoader(
                $this->createMock(Connection::class),
                $this->createMock(AppLoader::class),
                '/'
            )
        );

        $snippetFileLoader->loadSnippetFilesIntoCollection($collection);

        static::assertCount(2, $collection);

        $snippetFile = $collection->getSnippetFilesByIso('zh-CN')[0];
        static::assertEquals('storefront.zh-CN', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/SnippetSet/Resources/snippet/storefront.zh-CN.json',
            $snippetFile->getPath()
        );
        static::assertEquals('zh-CN', $snippetFile->getIso());
        static::assertEquals('Plugin Manufacturer', $snippetFile->getAuthor());
        static::assertFalse($snippetFile->isBase());

        $snippetFile = $collection->getSnippetFilesByIso('en-GB')[0];
        static::assertEquals('storefront.en-GB', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/SnippetSet/Resources/snippet/storefront.en-GB.json',
            $snippetFile->getPath()
        );
        static::assertEquals('en-GB', $snippetFile->getIso());
        static::assertEquals('Plugin Manufacturer', $snippetFile->getAuthor());
        static::assertEquals('SnippetSet', $snippetFile->getTechnicalName());
        static::assertFalse($snippetFile->isBase());
    }

    public function testLoadBaseSnippetsFromPlugin(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())->method('fetchAllKeyValue')->willReturn([
            BaseSnippetSet::class => 'Plugin Manufacturer',
        ]);

        $kernel = new MockedKernel(
            [
                'BaseSnippetSet' => new BaseSnippetSet(true, __DIR__),
            ]
        );

        $collection = new SnippetFileCollection();

        $snippetFileLoader = new SnippetFileLoader(
            $kernel,
            $connection,
            $this->createMock(AppSnippetFileLoader::class),
            new ActiveAppsLoader(
                $this->createMock(Connection::class),
                $this->createMock(AppLoader::class),
                '/'
            )
        );

        $snippetFileLoader->loadSnippetFilesIntoCollection($collection);

        static::assertCount(2, $collection);

        $snippetFile = $collection->getSnippetFilesByIso('zh-CN')[0];
        static::assertEquals('storefront.zh-CN', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/BaseSnippetSet/Resources/snippet/storefront.zh-CN.base.json',
            $snippetFile->getPath()
        );
        static::assertEquals('zh-CN', $snippetFile->getIso());
        static::assertEquals('Plugin Manufacturer', $snippetFile->getAuthor());
        static::assertEquals('BaseSnippetSet', $snippetFile->getTechnicalName());
        static::assertTrue($snippetFile->isBase());

        $snippetFile = $collection->getSnippetFilesByIso('en-GB')[0];
        static::assertEquals('storefront.en-GB', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/BaseSnippetSet/Resources/snippet/storefront.en-GB.base.json',
            $snippetFile->getPath()
        );
        static::assertEquals('en-GB', $snippetFile->getIso());
        static::assertEquals('Plugin Manufacturer', $snippetFile->getAuthor());
        static::assertTrue($snippetFile->isBase());
    }
}
