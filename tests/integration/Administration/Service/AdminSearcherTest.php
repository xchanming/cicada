<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Administration\Service;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Administration\Framework\Search\CriteriaCollection;
use Cicada\Administration\Service\AdminSearcher;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Query\ScoreQuery;
use Cicada\Core\Framework\Struct\ArrayEntity;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
class AdminSearcherTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $productRepository;

    private AdminSearcher $searcher;

    private Context $context;

    private string $userId;

    protected function setUp(): void
    {
        $this->productRepository = static::getContainer()->get('product.repository');

        $this->searcher = static::getContainer()->get(AdminSearcher::class);

        $connection = static::getContainer()->get(Connection::class);
        $userId = (string) $connection->fetchOne('SELECT id FROM `user` WHERE username = "admin"');
        $this->userId = Uuid::fromBytesToHex($userId);
        $this->context = Context::createDefaultContext();
    }

    public function testSearch(): void
    {
        $productId1 = Uuid::randomHex();
        $productId2 = Uuid::randomHex();

        $this->productRepository->upsert([
            ['id' => $productId1, 'productNumber' => Uuid::randomHex(), 'stock' => 1, 'name' => 'test_product 1', 'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]], 'tax' => ['name' => 'test', 'taxRate' => 5], 'manufacturer' => ['name' => 'test']],
            ['id' => $productId2, 'productNumber' => Uuid::randomHex(), 'stock' => 2, 'name' => 'test_product 2', 'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]], 'tax' => ['name' => 'test', 'taxRate' => 5], 'manufacturer' => ['name' => 'test']],
            ['id' => Uuid::randomHex(), 'productNumber' => Uuid::randomHex(), 'stock' => 1, 'name' => 'notmatch', 'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]], 'tax' => ['name' => 'notmatch', 'taxRate' => 5], 'manufacturer' => ['name' => 'notmatch']],
            ['id' => Uuid::randomHex(), 'productNumber' => Uuid::randomHex(), 'stock' => 1, 'name' => 'notmatch', 'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]], 'tax' => ['name' => 'notmatch', 'taxRate' => 5], 'manufacturer' => ['name' => 'notmatch']],
        ], $this->context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('stock', 1));
        $criteria->addQuery(new ScoreQuery(new ContainsFilter('manufacturer.name', 'test'), 500));
        $criteria->addQuery(new ScoreQuery(new ContainsFilter('name', 'test'), 2500));

        $queries = new CriteriaCollection([
            'product' => $criteria,
        ]);

        $result = $this->searcher->search($queries, $this->context);

        static::assertCount(1, $result);

        static::assertNotEmpty($result['product']);

        /** @var ProductCollection $products */
        $products = $result['product']['data'];
        $first = $products->first();
        $last = $products->last();

        static::assertInstanceOf(ProductEntity::class, $first);
        static::assertInstanceOf(ProductEntity::class, $last);

        /** @var ArrayEntity $firstSearchExtension */
        $firstSearchExtension = $first->getExtension('search');
        $firstScore = $firstSearchExtension->get('_score');

        /** @var ArrayEntity $secondSearchExtension */
        $secondSearchExtension = $first->getExtension('search');
        $secondScore = $secondSearchExtension->get('_score');

        static::assertSame($secondScore, $firstScore);
    }

    public function testSearchWithoutReadPermission(): void
    {
        $productId1 = Uuid::randomHex();
        $productId2 = Uuid::randomHex();

        $this->productRepository->upsert([
            ['id' => $productId1, 'productNumber' => Uuid::randomHex(), 'categories' => [['name' => 'test']], 'stock' => 1, 'name' => 'test_product 1', 'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]], 'tax' => ['name' => 'test', 'taxRate' => 5], 'manufacturer' => ['name' => 'test']],
            ['id' => $productId2, 'productNumber' => Uuid::randomHex(), 'categories' => [['name' => 'test']], 'stock' => 2, 'name' => 'test_product 2', 'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]], 'tax' => ['name' => 'test', 'taxRate' => 5], 'manufacturer' => ['name' => 'test']],
            ['id' => Uuid::randomHex(), 'productNumber' => Uuid::randomHex(), 'stock' => 1, 'name' => 'notmatch', 'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]], 'tax' => ['name' => 'notmatch', 'taxRate' => 5], 'manufacturer' => ['name' => 'notmatch']],
            ['id' => Uuid::randomHex(), 'productNumber' => Uuid::randomHex(), 'stock' => 1, 'name' => 'notmatch', 'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]], 'tax' => ['name' => 'notmatch', 'taxRate' => 5], 'manufacturer' => ['name' => 'notmatch']],
        ], $this->context);

        $criteria = new Criteria();
        $criteria->addQuery(new ScoreQuery(new ContainsFilter('name', 'test'), 2500));

        $queries = new CriteriaCollection([
            'product' => $criteria,
            'category' => $criteria,
        ]);

        $resultWithPermissions = $this->searcher->search($queries, $this->context);

        static::assertCount(2, $resultWithPermissions);

        static::assertNotEmpty($resultWithPermissions['category']);
        static::assertNotEmpty($resultWithPermissions['product']);

        $adminSource = new AdminApiSource($this->userId);
        $adminSource->setIsAdmin(false);
        $adminSource->setPermissions(['category:read']);

        $this->context->assign([
            'source' => $adminSource,
        ]);

        $resultWithoutPermissions = $this->searcher->search($queries, $this->context);

        static::assertCount(1, $resultWithoutPermissions);
        static::assertNotEmpty($resultWithoutPermissions['category']);
        static::assertArrayNotHasKey('product', $resultWithoutPermissions);
    }
}
