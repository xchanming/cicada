<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Product;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Elasticsearch\Product\ElasticsearchProductException;
use Cicada\Elasticsearch\Product\SearchConfigLoader;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(SearchConfigLoader::class)]
class SearchConfigLoaderTest extends TestCase
{
    /**
     * @param array<non-falsy-string, array<array{and_logic: string, field: string, tokenize: int, ranking: float}>> $configKeyedByLanguageId
     * @param array<array{and_logic: string, field: string, tokenize: int, ranking: float}> $expectedResult
     */
    #[DataProvider('loadDataProvider')]
    public function testLoad(array $configKeyedByLanguageId, array $expectedResult): void
    {
        $connection = $this->createMock(Connection::class);

        $connection->expects(static::once())
            ->method('fetchAllAssociative')
            ->willReturn($configKeyedByLanguageId[array_key_first($configKeyedByLanguageId)]);

        $loader = new SearchConfigLoader($connection);

        $languageIdChain = array_values(array_filter(array_keys($configKeyedByLanguageId)));
        static::assertNotEmpty($languageIdChain);

        $context = new Context(
            new SystemSource(),
            [],
            Defaults::CURRENCY,
            $languageIdChain,
        );

        $result = $loader->load($context);

        static::assertEquals($expectedResult, $result);
    }

    public function testLoadWithNoResult(): void
    {
        static::expectExceptionObject(ElasticsearchProductException::configNotFound());
        static::expectExceptionMessage('Configuration for product elasticsearch definition not found');

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $loader = new SearchConfigLoader($connection);

        $context = new Context(
            new SystemSource(),
            [],
            Defaults::CURRENCY,
            [Defaults::LANGUAGE_SYSTEM],
        );

        $loader->load($context);
    }

    /**
     * @return iterable<string, array{configKeyedByLanguageId: array<string, array<array{and_logic: string, field: string, tokenize: int, ranking: int}>>, expectedResult: array<array{and_logic: string, field: string, tokenize: int, ranking: int}>}>
     */
    public static function loadDataProvider(): iterable
    {
        yield 'one language config' => [
            'configKeyedByLanguageId' => [
                Defaults::LANGUAGE_SYSTEM => [[
                    'and_logic' => 'and',
                    'field' => 'name',
                    'tokenize' => 1,
                    'ranking' => 2,
                ]],
            ],
            'expectedResult' => [
                [
                    'and_logic' => 'and',
                    'field' => 'name',
                    'tokenize' => 1,
                    'ranking' => 2,
                ],
            ],
        ];

        yield 'multi languages config' => [
            'configKeyedByLanguageId' => [
                Defaults::LANGUAGE_SYSTEM => [[
                    'and_logic' => 'and',
                    'field' => 'name',
                    'tokenize' => 1,
                    'ranking' => 100,
                ]],
                Uuid::randomHex() => [[
                    'and_logic' => 'and',
                    'field' => 'name',
                    'tokenize' => 0,
                    'ranking' => 50,
                ]],
            ],
            'expectedResult' => [
                [
                    'and_logic' => 'and',
                    'field' => 'name',
                    'tokenize' => 1,
                    'ranking' => 100,
                ],
            ],
        ];
    }
}
