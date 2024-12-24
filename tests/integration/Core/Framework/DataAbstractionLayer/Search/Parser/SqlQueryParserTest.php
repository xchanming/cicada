<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Search\Parser;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\SuffixFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Parser\SqlQueryParser;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SqlQueryParser::class)]
class SqlQueryParserTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $repository;

    private EntityRepository $manufacturerRepository;

    private Context $context;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->manufacturerRepository = static::getContainer()->get('product_manufacturer.repository');

        $this->context = Context::createDefaultContext();
        $this->repository = static::getContainer()->get('product.repository');

        $this->ids = new IdsCollection();

        $this->createProduct();

        parent::setUp();
    }

    public function testFindProductsWithoutCategory(): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryIds', null));

        $result = $this->repository->searchIds($criteria, $this->context);

        $productsWithoutCategory = [
            $this->ids->get('product1-without-category'),
            $this->ids->get('product2-without-category'),
        ];

        static::assertEquals($productsWithoutCategory, $result->getIds());
    }

    public function testFindProductsWithCategory(): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryIds', $this->ids->get('category1')));

        $result = $this->repository->searchIds($criteria, $this->context);

        $productsWithoutCategory = [
            $this->ids->get('product1-with-category'),
        ];

        static::assertEquals($productsWithoutCategory, $result->getIds());
    }

    #[DataProvider('whenToUseNullSafeOperatorProvider')]
    public function testWhenToUseNullSafeOperator(Filter $filter, bool $expected): void
    {
        $parser = static::getContainer()->get(SqlQueryParser::class);

        $definition = static::getContainer()->get(ProductDefinition::class);

        $parsed = $parser->parse($filter, $definition, Context::createDefaultContext(), 'product');

        $has = false;
        foreach ($parsed->getWheres() as $where) {
            $has = $has || str_contains((string) $where, '<=>');
        }

        static::assertEquals($expected, $has);
    }

    /**
     * @return iterable<array-key, array{0: Filter, 1: bool}>
     */
    public static function whenToUseNullSafeOperatorProvider()
    {
        yield 'Dont used for simple equals' => [new EqualsFilter('product.id', Uuid::randomHex()), false];
        yield 'Used for negated comparison' => [new NandFilter([new EqualsFilter('product.id', Uuid::randomHex())]), true];
        yield 'Used for negated null comparison' => [new NandFilter([new EqualsFilter('product.id', null)]), true];
        yield 'Used in nested negated comparison' => [new AndFilter([new NandFilter([new EqualsFilter('product.id', Uuid::randomHex())])]), true];
        yield 'Used for null comparison' => [new EqualsFilter('product.id', null), true];
    }

    public function testContainsFilterFindUnderscore(): void
    {
        $targetId = $this->createManufacturer(['link' => 'target_to_find']);
        $errournousId = $this->createManufacturer(['link' => 'target to find']);
        $criteria = (new Criteria())->addFilter(new ContainsFilter('link', 'target_to_find'));
        $foundIds = $this->manufacturerRepository->searchIds($criteria, Context::createDefaultContext());

        static::assertContains($targetId, $foundIds->getIds());
        static::assertNotContains($errournousId, $foundIds->getIds());
    }

    public function testContainsFilterFindPercentageSign(): void
    {
        $targetId = $this->createManufacturer(['link' => 'target%find']);
        $errournousId = $this->createManufacturer(['link' => 'target to find']);
        $criteria = (new Criteria())->addFilter(new ContainsFilter('link', 'target%find'));
        $foundIds = $this->manufacturerRepository->searchIds($criteria, Context::createDefaultContext());

        static::assertContains($targetId, $foundIds->getIds());
        static::assertNotContains($errournousId, $foundIds->getIds());
    }

    public function testContainsFilterFindBackslash(): void
    {
        $targetId = $this->createManufacturer(['link' => 'target \\ find']);
        $errournousId = $this->createManufacturer(['link' => 'target \\find']);
        $criteria = (new Criteria())->addFilter(new ContainsFilter('link', ' \\ '));
        $foundIds = $this->manufacturerRepository->searchIds($criteria, Context::createDefaultContext());

        static::assertContains($targetId, $foundIds->getIds());
        static::assertNotContains($errournousId, $foundIds->getIds());
    }

    public function testPrefixFilterFindUnderscore(): void
    {
        $targetId = $this->createManufacturer(['link' => 'target_to_find']);
        $erroneousId = $this->createManufacturer(['link' => 'target to find']);
        $criteria = (new Criteria())->addFilter(new PrefixFilter('link', 'target_to'));
        $foundIds = $this->manufacturerRepository->searchIds($criteria, Context::createDefaultContext());

        static::assertContains($targetId, $foundIds->getIds());
        static::assertNotContains($erroneousId, $foundIds->getIds());
    }

    public function testPrefixFilterFindPercentageSign(): void
    {
        $targetId = $this->createManufacturer(['link' => 'target%find']);
        $erroneousId = $this->createManufacturer(['link' => 'target to find']);
        $criteria = (new Criteria())->addFilter(new PrefixFilter('link', 'target%fi'));
        $foundIds = $this->manufacturerRepository->searchIds($criteria, Context::createDefaultContext());

        static::assertContains($targetId, $foundIds->getIds());
        static::assertNotContains($erroneousId, $foundIds->getIds());
    }

    public function testPrefixFilterFindBackslash(): void
    {
        $targetId = $this->createManufacturer(['link' => '\\ target find']);
        $erroneousId = $this->createManufacturer(['link' => '\\target find']);
        $criteria = (new Criteria())->addFilter(new PrefixFilter('link', '\\ '));
        $foundIds = $this->manufacturerRepository->searchIds($criteria, Context::createDefaultContext());

        static::assertContains($targetId, $foundIds->getIds());
        static::assertNotContains($erroneousId, $foundIds->getIds());
    }

    public function testSuffixFilterFindUnderscore(): void
    {
        $targetId = $this->createManufacturer(['link' => 'target_to_find']);
        $erroneousId = $this->createManufacturer(['link' => 'target to find']);
        $criteria = (new Criteria())->addFilter(new SuffixFilter('link', 'to_find'));
        $foundIds = $this->manufacturerRepository->searchIds($criteria, Context::createDefaultContext());

        static::assertContains($targetId, $foundIds->getIds());
        static::assertNotContains($erroneousId, $foundIds->getIds());
    }

    public function testSuffixFilterFindPercentageSign(): void
    {
        $targetId = $this->createManufacturer(['link' => 'target%find']);
        $erroneousId = $this->createManufacturer(['link' => 'target to find']);
        $criteria = (new Criteria())->addFilter(new SuffixFilter('link', 'et%find'));
        $foundIds = $this->manufacturerRepository->searchIds($criteria, Context::createDefaultContext());

        static::assertContains($targetId, $foundIds->getIds());
        static::assertNotContains($erroneousId, $foundIds->getIds());
    }

    public function testSuffixFilterFindBackslash(): void
    {
        $targetId = $this->createManufacturer(['link' => 'target find \\']);
        $erroneousId = $this->createManufacturer(['link' => 'target find\\']);
        $criteria = (new Criteria())->addFilter(new SuffixFilter('link', ' \\'));
        $foundIds = $this->manufacturerRepository->searchIds($criteria, Context::createDefaultContext());

        static::assertContains($targetId, $foundIds->getIds());
        static::assertNotContains($erroneousId, $foundIds->getIds());
    }

    /**
     * @param array<mixed> $parameters
     */
    private function createManufacturer(array $parameters = []): string
    {
        $id = Uuid::randomHex();

        $defaults = ['id' => $id, 'name' => 'Test'];

        $parameters = array_merge($defaults, $parameters);

        $this->manufacturerRepository->create([$parameters], Context::createDefaultContext());

        return $id;
    }

    private function createProduct(): void
    {
        $products = [
            (new ProductBuilder($this->ids, 'product1-with-category', 10))
                ->categories(['category1', 'category2'])
                ->visibility()
                ->price(10)
                ->build(),
            (new ProductBuilder($this->ids, 'product2-with-category', 12))
                ->category('category2')
                ->visibility()
                ->price(20)
                ->build(),
            (new ProductBuilder($this->ids, 'product1-without-category', 14))
                ->visibility()
                ->price(30)
                ->build(),
            (new ProductBuilder($this->ids, 'product2-without-category', 16))
                ->visibility()
                ->price(40)
                ->build(),
        ];

        static::getContainer()->get('product.repository')->create($products, Context::createDefaultContext());
    }
}
