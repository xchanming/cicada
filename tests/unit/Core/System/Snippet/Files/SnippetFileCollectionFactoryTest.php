<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Snippet\Files;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\System\Snippet\Files\SnippetFileCollection;
use Cicada\Core\System\Snippet\Files\SnippetFileCollectionFactory;
use Cicada\Core\System\Snippet\Files\SnippetFileLoaderInterface;
use Cicada\Tests\Unit\Core\System\Snippet\Mock\MockSnippetFile;

/**
 * @internal
 */
#[CoversClass(SnippetFileCollectionFactory::class)]
class SnippetFileCollectionFactoryTest extends TestCase
{
    public function testCreateSnippetFileCollection(): void
    {
        $snippetFileLoaderMock = $this->createMock(SnippetFileLoaderInterface::class);
        $snippetFileLoaderMock->expects(static::once())
            ->method('loadSnippetFilesIntoCollection')
            ->willReturnCallback(function (SnippetFileCollection $fileCollection): void {
                $fileCollection->add(new MockSnippetFile('storefront.de-DE', 'de-DE', '{}', true));
            });

        $factory = new SnippetFileCollectionFactory($snippetFileLoaderMock);

        $collection = $factory->createSnippetFileCollection();

        static::assertCount(1, $collection);
    }
}
