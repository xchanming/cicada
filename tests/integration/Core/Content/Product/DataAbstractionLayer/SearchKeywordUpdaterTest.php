<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\DataAbstractionLayer;

use Cicada\Core\Content\Product\DataAbstractionLayer\SearchKeywordUpdater;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class SearchKeywordUpdaterTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $productRepository;

    private EntityRepository $salesChannelLanguageRepository;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->productRepository = static::getContainer()->get('product.repository');
        $this->salesChannelLanguageRepository = static::getContainer()->get('sales_channel_language.repository');
        $this->connection = static::getContainer()->get(Connection::class);
    }

    /**
     * @param array<mixed> $productData
     * @param string[] $chineseKeywords
     * @param string[] $englishKeywords
     * @param string[] $additionalDictionaries
     */
    #[DataProvider('productKeywordProvider')]
    public function testItUpdatesKeywordsAndDictionary(array $productData, IdsCollection $ids, array $chineseKeywords, array $englishKeywords, array $additionalDictionaries = []): void
    {
        $this->productRepository->create([$productData], Context::createDefaultContext());

        $this->assertKeywords($ids->get('1000'), Defaults::LANGUAGE_SYSTEM, $chineseKeywords);
        $this->assertKeywords($ids->get('1000'), $this->getEnGbLanguageId(), $englishKeywords);

        $expectedDictionary = array_merge($chineseKeywords, $additionalDictionaries);
        sort($expectedDictionary);
        $this->assertDictionary(Defaults::LANGUAGE_SYSTEM, $expectedDictionary);
        $expectedDictionary = array_merge($englishKeywords, $additionalDictionaries);
        sort($expectedDictionary);
        $this->assertDictionary($this->getEnGbLanguageId(), $expectedDictionary);
    }

    /**
     * @param array<mixed> $productData
     * @param string[] $chineseKeywords
     * @param string[] $englishKeywords
     * @param string[] $additionalDictionaries
     */
    #[DataProvider('productKeywordProvider')]
    public function testItUpdatesKeywordsForAvailableLanguagesOnly(array $productData, IdsCollection $ids, array $chineseKeywords, array $englishKeywords, array $additionalDictionaries = []): void
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();

        // Delete sales channel zh-CN language associations to ensure only default language is used to create keywords.
        $criteria->addFilter(new EqualsFilter('languageId', $this->getEnGbLanguageId()));

        /** @var list<array<string, string>> $salesChannalLanguageIds */
        $salesChannalLanguageIds = $this->salesChannelLanguageRepository->searchIds($criteria, $context)->getIds();
        $this->salesChannelLanguageRepository->delete($salesChannalLanguageIds, $context);

        $this->productRepository->create([$productData], Context::createDefaultContext());

        $this->assertKeywords($ids->get('1000'), Defaults::LANGUAGE_SYSTEM, $chineseKeywords);

        $expectedDictionary = array_merge($chineseKeywords, $additionalDictionaries);
        sort($expectedDictionary);
        $this->assertDictionary(Defaults::LANGUAGE_SYSTEM, $expectedDictionary);

        $this->assertLanguageHasNoKeywords($this->getEnGbLanguageId());
        $this->assertLanguageHasNoDictionary($this->getEnGbLanguageId());
    }

    public function testCustomFields(): void
    {
        $ids = new IdsCollection();
        $products = [
            (new ProductBuilder($ids, 'p1'))->price(100)->build(),
            (new ProductBuilder($ids, 'p2'))->price(100)->build(),
        ];

        $context = Context::createDefaultContext();
        $context->addState(EntityIndexerRegistry::DISABLE_INDEXING);

        static::getContainer()->get('product.repository')
            ->create($products, $context);

        $id = static::getContainer()->get(Connection::class)
            ->fetchOne('SELECT LOWER(HEX(id)) FROM product_search_config WHERE language_id = :id', ['id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]);

        $fields = [
            ['searchConfigId' => $id, 'searchable' => true, 'field' => 'customFields.field1', 'tokenize' => true, 'ranking' => 100, 'language_id' => Defaults::LANGUAGE_SYSTEM],
            ['searchConfigId' => $id, 'searchable' => true, 'field' => 'manufacturer.customFields.field1', 'tokenize' => true, 'ranking' => 100, 'language_id' => Defaults::LANGUAGE_SYSTEM],
        ];

        static::getContainer()->get('product_search_config_field.repository')
            ->create($fields, Context::createDefaultContext());

        static::getContainer()->get(SearchKeywordUpdater::class)
            ->update($ids->getList(['p1', 'p2']), Context::createDefaultContext());
    }

    public function testItSkipsKeywordGenerationForNotUsedLanguages(): void
    {
        $ids = new IdsCollection();
        $esLocale = $this->getLocaleIdByIsoCode('en-US');

        $languageRepo = static::getContainer()->get('language.repository');
        $languageRepo->create([
            [
                'id' => $ids->get('language'),
                'name' => 'Español',
                'localeId' => $esLocale,
                'translationCodeId' => $esLocale,
            ],
        ], Context::createDefaultContext());

        $this->productRepository->create(
            [
                (new ProductBuilder($ids, '1000'))
                    ->price(10)
                    ->name('Test product')
                    ->translation($ids->get('language'), 'name', 'Test produkt')
                    ->build(),
            ],
            Context::createDefaultContext()
        );

        $this->assertKeywords(
            $ids->get('1000'),
            Defaults::LANGUAGE_SYSTEM,
            [
                '1000', // productNumber
                'product', // part of name
                'test', // part of name
            ]
        );
        $this->assertKeywords($ids->get('1000'), $ids->get('language'), []);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function productKeywordProvider(): array
    {
        $idsCollection = new IdsCollection();

        return [
            'test it uses parent languages' => [
                (new ProductBuilder($idsCollection, '1000'))
                    ->price(10)
                    ->name('Test product')
                    ->build(),
                $idsCollection,
                [
                    '1000', // productNumber
                    'product', // part of name
                    'test', // part of name
                ],
                [
                    '1000', // productNumber
                    'product', // part of name
                    'test', // part of name
                ],
            ],
        ];
    }

    /**
     * @param string[] $expectedKeywords
     */
    private function assertKeywords(string $productId, string $languageId, array $expectedKeywords): void
    {
        $keywords = $this->connection->fetchFirstColumn(
            'SELECT `keyword`
            FROM `product_search_keyword`
            WHERE `product_id` = :productId AND language_id = :languageId
            ORDER BY `keyword` ASC',
            [
                'productId' => Uuid::fromHexToBytes($productId),
                'languageId' => Uuid::fromHexToBytes($languageId),
            ]
        );

        static::assertEquals($expectedKeywords, $keywords);
    }

    private function assertLanguageHasNoKeywords(string $languageId): void
    {
        $keywords = $this->connection->fetchFirstColumn(
            'SELECT `keyword`
            FROM `product_search_keyword`
            WHERE language_id = :languageId
            ORDER BY `keyword` ASC',
            [
                'languageId' => Uuid::fromHexToBytes($languageId),
            ]
        );

        static::assertCount(0, $keywords);
    }

    /**
     * @param string[] $expectedKeywords
     */
    private function assertDictionary(string $languageId, array $expectedKeywords): void
    {
        $dictionary = $this->connection->fetchFirstColumn(
            'SELECT `keyword`
            FROM `product_keyword_dictionary`
            WHERE language_id = :languageId
            ORDER BY `keyword` ASC',
            [
                'languageId' => Uuid::fromHexToBytes($languageId),
            ]
        );

        static::assertEquals($expectedKeywords, $dictionary);
    }

    private function assertLanguageHasNoDictionary(string $languageId): void
    {
        $dictionary = $this->connection->fetchFirstColumn(
            'SELECT `keyword`
            FROM `product_keyword_dictionary`
            WHERE language_id = :languageId
            ORDER BY `keyword` ASC',
            [
                'languageId' => Uuid::fromHexToBytes($languageId),
            ]
        );

        static::assertCount(0, $dictionary);
    }

    private function getLocaleIdByIsoCode(string $iso): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('code', $iso));

        $firstId = static::getContainer()->get('locale.repository')
            ->searchIds($criteria, Context::createDefaultContext())
            ->firstId();

        static::assertIsString($firstId);

        return $firstId;
    }
}
