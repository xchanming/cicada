<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Snippet\Filter;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Snippet\Filter\AddedFilter;
use Cicada\Core\System\Snippet\Filter\AuthorFilter;
use Cicada\Core\System\Snippet\Filter\EditedFilter;
use Cicada\Core\System\Snippet\Filter\EmptySnippetFilter;
use Cicada\Core\System\Snippet\Filter\NamespaceFilter;
use Cicada\Core\System\Snippet\Filter\SnippetFilterFactory;
use Cicada\Core\System\Snippet\Filter\SnippetFilterInterface;
use Cicada\Core\System\Snippet\Filter\TermFilter;
use Cicada\Core\System\Snippet\Filter\TranslationKeyFilter;
use Cicada\Core\System\Snippet\SnippetException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(SnippetFilterFactory::class)]
class SnippetFilterFactoryTest extends TestCase
{
    /**
     * @param class-string<SnippetFilterInterface>|null $expectedResult
     */
    #[DataProvider('dataProviderForTestGetFilter')]
    public function testGetFilter(string $filterName, ?string $expectedResult): void
    {
        $factory = new SnippetFilterFactory([
            new AuthorFilter(),
            new EditedFilter(),
            new EmptySnippetFilter(),
            new NamespaceFilter(),
            new TermFilter(),
            new TranslationKeyFilter(),
            new AddedFilter(),
        ]);

        if ($expectedResult === null) {
            $this->expectException(SnippetException::class);
            $this->expectExceptionMessage(\sprintf('The filter "%s" was not found in "Cicada\Core\System\Snippet\Filter\SnippetFilterFactory".', $filterName));
        }

        $result = $factory->getFilter($filterName);

        static::assertNotNull($expectedResult);
        static::assertInstanceOf($expectedResult, $result);
    }

    /**
     * @return list<array{0: string, 1: class-string<SnippetFilterInterface>|null}>
     */
    public static function dataProviderForTestGetFilter(): array
    {
        return [
            ['', null],
            ['foo', null],
            ['bar', null],
            ['author', AuthorFilter::class],
            ['edited', EditedFilter::class],
            ['empty', EmptySnippetFilter::class],
            ['namespace', NamespaceFilter::class],
            ['term', TermFilter::class],
            ['translationKey', TranslationKeyFilter::class],
            ['added', AddedFilter::class],
        ];
    }
}
