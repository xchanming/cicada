<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\Snippet\Files;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\ActiveAppsLoader;
use Cicada\Core\Framework\Test\TestCaseBase\CacheTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\System\Snippet\Files\AppSnippetFileLoader;
use Cicada\Core\System\Snippet\Files\SnippetFileCollection;
use Cicada\Core\System\Snippet\Files\SnippetFileLoader;
use Cicada\Core\Test\AppSystemTestBehaviour;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
class AppSnippetFileLoaderTest extends TestCase
{
    use AppSystemTestBehaviour;
    use CacheTestBehaviour;
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private SnippetFileLoader $snippetFileLoader;

    protected function setUp(): void
    {
        $this->snippetFileLoader = new SnippetFileLoader(
            $this->createMock(KernelInterface::class),
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(AppSnippetFileLoader::class),
            static::getContainer()->get(ActiveAppsLoader::class)
        );
    }

    public function testLoadSnippetFilesIntoCollectionWithoutSnippetFiles(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures/Apps/AppWithoutSnippets');

        $collection = new SnippetFileCollection();

        $this->snippetFileLoader->loadSnippetFilesIntoCollection($collection);

        static::assertCount(0, $collection);
    }

    public function testLoadSnippetFilesIntoCollection(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures/Apps/AppWithSnippets');

        $collection = new SnippetFileCollection();

        $this->snippetFileLoader->loadSnippetFilesIntoCollection($collection);

        static::assertCount(2, $collection);

        $snippetFile = $collection->getSnippetFilesByIso('de-DE')[0];
        static::assertEquals('storefront.de-DE', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/Apps/AppWithSnippets/Resources/snippet/storefront.de-DE.json',
            $snippetFile->getPath()
        );
        static::assertEquals('de-DE', $snippetFile->getIso());
        static::assertEquals('cicada AG', $snippetFile->getAuthor());
        static::assertFalse($snippetFile->isBase());

        $snippetFile = $collection->getSnippetFilesByIso('en-GB')[0];
        static::assertEquals('storefront.en-GB', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/Apps/AppWithSnippets/Resources/snippet/storefront.en-GB.json',
            $snippetFile->getPath()
        );
        static::assertEquals('en-GB', $snippetFile->getIso());
        static::assertEquals('cicada AG', $snippetFile->getAuthor());
        static::assertFalse($snippetFile->isBase());
    }

    public function testLoadSnippetFilesDoesNotLoadSnippetsFromInactiveApps(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures/Apps/AppWithSnippets', false);

        $collection = new SnippetFileCollection();

        $this->snippetFileLoader->loadSnippetFilesIntoCollection($collection);

        static::assertCount(0, $collection);
    }

    public function testLoadBaseSnippetFilesIntoCollection(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures/Apps/AppWithBaseSnippets');

        $collection = new SnippetFileCollection();

        $this->snippetFileLoader->loadSnippetFilesIntoCollection($collection);

        static::assertCount(2, $collection);

        $snippetFile = $collection->getSnippetFilesByIso('de-DE')[0];
        static::assertEquals('storefront.de-DE', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/Apps/AppWithBaseSnippets/Resources/snippet/storefront.de-DE.base.json',
            $snippetFile->getPath()
        );
        static::assertEquals('de-DE', $snippetFile->getIso());
        static::assertEquals('cicada AG', $snippetFile->getAuthor());
        static::assertTrue($snippetFile->isBase());

        $snippetFile = $collection->getSnippetFilesByIso('en-GB')[0];
        static::assertEquals('storefront.en-GB', $snippetFile->getName());
        static::assertEquals(
            __DIR__ . '/_fixtures/Apps/AppWithBaseSnippets/Resources/snippet/storefront.en-GB.base.json',
            $snippetFile->getPath()
        );
        static::assertEquals('en-GB', $snippetFile->getIso());
        static::assertEquals('cicada AG', $snippetFile->getAuthor());
        static::assertTrue($snippetFile->isBase());
    }

    public function testLoadSnippetFilesIntoCollectionIgnoresWrongFilenames(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures/Apps/SnippetsWithWrongName');

        $collection = new SnippetFileCollection();

        $this->snippetFileLoader->loadSnippetFilesIntoCollection($collection);

        static::assertCount(0, $collection);
    }
}
