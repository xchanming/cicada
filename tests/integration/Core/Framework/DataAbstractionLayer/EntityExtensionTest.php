<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCategory\ProductCategoryDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Cicada\Core\Content\Product\Aggregate\ProductPrice\ProductPriceDefinition;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Extension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\AssociationExtension;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ExtendableDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ExtendedDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\FkFieldExtension;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ReferenceVersionExtension;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ScalarExtension;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ScalarRuntimeExtension;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Tax\TaxDefinition;
use Cicada\Core\System\Tax\TaxEntity;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class EntityExtensionTest extends TestCase
{
    use DataAbstractionLayerFieldTestBehaviour {
        tearDown as protected tearDownDefinitions;
    }
    use IntegrationTestBehaviour;

    private Connection $connection;

    /**
     * @var EntityRepository<ProductCollection>
     */
    private EntityRepository $productRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = static::getContainer()->get(Connection::class);
        $this->productRepository = static::getContainer()->get('product.repository');
    }

    protected function tearDown(): void
    {
        $this->tearDownDefinitions();
        parent::tearDown();
        static::getContainer()->get(ProductDefinition::class)->getFields()->remove('myPrices');
        static::getContainer()->get(ProductDefinition::class)->getFields()->remove('myCategories');
    }

    public function testICanWriteAndReadManyToOneAssociationExtension(): void
    {
        $this->connection->rollBack();

        try {
            $this->connection->executeStatement('ALTER TABLE `product` ADD COLUMN my_tax_id binary(16) NULL');
        } catch (Exception) {
        }

        $this->connection->beginTransaction();

        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField(
            (new ManyToOneAssociationField('myTax', 'my_tax_id', TaxDefinition::class, 'id'))->addFlags(new ApiAware(), new Extension())
        );
        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField(
            (new FkField('my_tax_id', 'myTaxId', TaxDefinition::class))->addFlags(new ApiAware(), new Extension())
        );

        $id = Uuid::randomHex();

        $data = [
            'id' => $id,
            'name' => 'test',
            'productNumber' => $id,
            'stock' => 1,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false],
            ],
            'manufacturer' => ['name' => 'test'],
            'tax' => ['name' => 'test', 'taxRate' => 15],
            'myTax' => ['id' => $id, 'name' => 'my-tax', 'taxRate' => 50],
        ];

        $this->productRepository->create([$data], Context::createDefaultContext());

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('myTax');

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();

        static::assertInstanceOf(ProductEntity::class, $product);

        static::assertTrue($product->hasExtension('myTax'));

        $tax = $product->getExtension('myTax');
        static::assertInstanceOf(TaxEntity::class, $tax);

        static::assertSame('my-tax', $tax->getName());

        $this->connection->rollBack();

        $this->connection->executeStatement('ALTER TABLE `product` DROP COLUMN my_tax_id');

        $this->connection->beginTransaction();

        static::getContainer()->get(ProductDefinition::class)->getFields()->remove('myTax');
        static::getContainer()->get(ProductDefinition::class)->getFields()->remove('myTaxId');
    }

    public function testICanWriteOneToManyAssociationsExtensions(): void
    {
        $field = (new OneToManyAssociationField('myPrices', ProductPriceDefinition::class, 'product_id'))->addFlags(new ApiAware(), new Extension());

        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField($field);

        $id = Uuid::randomHex();

        $data = $this->getPricesData($id);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $count = $this->connection->fetchAllAssociative(
            'SELECT * FROM product_price WHERE product_id = :id',
            ['id' => Uuid::fromHexToBytes($id)]
        );

        static::assertCount(2, $count);

        $id = Uuid::randomHex();

        $data = $this->getPricesData($id);

        $data['extensions']['myPrices'] = $data['myPrices'];
        unset($data['myPrices']);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $count = $this->connection->fetchAllAssociative(
            'SELECT * FROM product_price WHERE product_id = :id',
            ['id' => Uuid::fromHexToBytes($id)]
        );

        static::assertCount(2, $count);
    }

    public function testICanReadOneToManyAssociationsExtensionsInBasic(): void
    {
        $field = (new OneToManyAssociationField('myPrices', ProductPriceDefinition::class, 'product_id'))->addFlags(new ApiAware(), new Extension());

        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField($field);

        $id = Uuid::randomHex();

        $data = $this->getPricesData($id);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('myPrices');

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertTrue($product->hasExtension('myPrices'));
        static::assertInstanceOf(ProductPriceCollection::class, $product->getExtension('myPrices'));
        static::assertCount(2, $product->getExtension('myPrices'));
    }

    public function testICanReadOneToManyAssociationsExtensionsNotInBasic(): void
    {
        $field = (new OneToManyAssociationField('myPrices', ProductPriceDefinition::class, 'product_id'))->addFlags(new ApiAware(), new Extension());

        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField($field);

        $id = Uuid::randomHex();

        $data = $this->getPricesData($id);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $product = $this->productRepository->search(new Criteria([$id]), Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertFalse($product->hasExtension('myPrices'));

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('myPrices');

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertTrue($product->hasExtension('myPrices'));
        static::assertInstanceOf(ProductPriceCollection::class, $product->getExtension('myPrices'));
        static::assertCount(2, $product->getExtension('myPrices'));

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('extensions.myPrices');

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertTrue($product->hasExtension('myPrices'));
        static::assertInstanceOf(ProductPriceCollection::class, $product->getExtension('myPrices'));
        static::assertCount(2, $product->getExtension('myPrices'));
    }

    public function testICanSearchOneToManyAssociationsExtensions(): void
    {
        $field = (new OneToManyAssociationField('myPrices', ProductPriceDefinition::class, 'product_id'))->addFlags(new ApiAware(), new Extension());

        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField($field);

        $id = Uuid::randomHex();

        $data = $this->getPricesData($id);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product.myPrices.price.gross', 15));
        $criteria->addFilter(new EqualsFilter('product.ean', 'test'));

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertFalse($product->hasExtension('myPrices'));

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product.extensions.myPrices.price.gross', 15));
        $criteria->addFilter(new EqualsFilter('product.ean', 'test'));

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertFalse($product->hasExtension('myPrices'));
    }

    public function testICanReadPaginatedOneToManyAssociationsExtensions(): void
    {
        $field = (new OneToManyAssociationField('myPrices', ProductPriceDefinition::class, 'product_id'))->addFlags(new ApiAware(), new Extension());

        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField($field);

        $id = Uuid::randomHex();

        $data = $this->getPricesData($id);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $product = $this->productRepository->search(new Criteria([$id]), Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertFalse($product->hasExtension('myPrices'));

        $criteria = new Criteria([$id]);
        $criteria->getAssociation('myPrices')->setLimit(1);

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertTrue($product->hasExtension('myPrices'));
        static::assertInstanceOf(ProductPriceCollection::class, $product->getExtension('myPrices'));
        static::assertCount(1, $product->getExtension('myPrices'));
    }

    public function testICanWriteManyToManyAssociationsExtensions(): void
    {
        $field = (new ManyToManyAssociationField(
            'myCategories',
            CategoryDefinition::class,
            ProductCategoryDefinition::class,
            'product_id',
            'category_id'
        ))->addFlags(new ApiAware(), new Extension());

        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField($field);

        $id = Uuid::randomHex();

        $data = $this->getCategoriesData($id);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $count = $this->connection->fetchAllAssociative(
            'SELECT * FROM product_category WHERE product_id = :id',
            ['id' => Uuid::fromHexToBytes($id)]
        );

        static::assertCount(2, $count);

        $id = Uuid::randomHex();

        $data = $this->getCategoriesData($id);

        $data['extensions']['myCategories'] = $data['myCategories'];
        unset($data['myCategories']);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $count = $this->connection->fetchAllAssociative(
            'SELECT * FROM product_category WHERE product_id = :id',
            ['id' => Uuid::fromHexToBytes($id)]
        );

        static::assertCount(2, $count);
    }

    public function testICanReadManyToManyAssociationsExtensionsInBasic(): void
    {
        $field = (new ManyToManyAssociationField(
            'myCategories',
            CategoryDefinition::class,
            ProductCategoryDefinition::class,
            'product_id',
            'category_id'
        ))->addFlags(new ApiAware(), new Extension());

        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField($field);

        $id = Uuid::randomHex();

        $data = $this->getCategoriesData($id);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('myCategories');

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertTrue($product->hasExtension('myCategories'));
        static::assertInstanceOf(CategoryCollection::class, $product->getExtension('myCategories'));
        static::assertCount(2, $product->getExtension('myCategories'));
    }

    public function testICanReadManyToManyAssociationsExtensionsNotInBasic(): void
    {
        $field = (new ManyToManyAssociationField(
            'myCategories',
            CategoryDefinition::class,
            ProductCategoryDefinition::class,
            'product_id',
            'category_id'
        ))->addFlags(new ApiAware(), new Extension());

        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField($field);

        $id = Uuid::randomHex();

        $data = $this->getCategoriesData($id);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $product = $this->productRepository->search(new Criteria([$id]), Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertFalse($product->hasExtension('myCategories'));

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('myCategories');

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertTrue($product->hasExtension('myCategories'));
        static::assertInstanceOf(CategoryCollection::class, $product->getExtension('myCategories'));
        static::assertCount(2, $product->getExtension('myCategories'));

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('myCategories');

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertTrue($product->hasExtension('myCategories'));
        static::assertInstanceOf(CategoryCollection::class, $product->getExtension('myCategories'));
        static::assertCount(2, $product->getExtension('myCategories'));
    }

    public function testICanSearchManyToManyAssociationsExtensions(): void
    {
        $field = (new ManyToManyAssociationField(
            'myCategories',
            CategoryDefinition::class,
            ProductCategoryDefinition::class,
            'product_id',
            'category_id'
        ))->addFlags(new ApiAware(), new Extension());

        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField($field);

        $id = Uuid::randomHex();

        $data = $this->getCategoriesData($id);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product.myCategories.level', 1));
        $criteria->addFilter(new EqualsFilter('product.myCategories.name', 'test'));

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertFalse($product->hasExtension('myCategories'));

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product.extensions.myCategories.level', 1));
        $criteria->addFilter(new EqualsFilter('product.myCategories.name', 'test'));

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertFalse($product->hasExtension('myCategories'));
    }

    public function testICanReadPaginatedManyToManyAssociationsExtensions(): void
    {
        $field = (new ManyToManyAssociationField(
            'myCategories',
            CategoryDefinition::class,
            ProductCategoryDefinition::class,
            'product_id',
            'category_id'
        ))->addFlags(new ApiAware(), new Extension());

        static::getContainer()->get(ProductDefinition::class)->getFields()->addNewField($field);

        $id = Uuid::randomHex();

        $data = $this->getCategoriesData($id);

        $this->productRepository->create([$data], Context::createDefaultContext());

        $product = $this->productRepository->search(new Criteria([$id]), Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertFalse($product->hasExtension('myCategories'));

        $criteria = new Criteria([$id]);
        $criteria->getAssociation('extensions.myCategories')->setLimit(2);

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertTrue($product->hasExtension('myCategories'));
        static::assertInstanceOf(CategoryCollection::class, $product->getExtension('myCategories'));
        static::assertCount(2, $product->getExtension('myCategories'));

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('myCategories')->setLimit(2);

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertInstanceOf(ProductEntity::class, $product);
        static::assertTrue($product->hasExtension('myCategories'));
        static::assertInstanceOf(CategoryCollection::class, $product->getExtension('myCategories'));
        static::assertCount(2, $product->getExtension('myCategories'));
    }

    public function testICantAddScalarExtensions(): void
    {
        static::expectException(\Exception::class);
        static::expectExceptionMessage('Only AssociationFields, FkFields/ReferenceVersionFields for a ManyToOneAssociationField or fields flagged as Runtime can be added as Extension.');

        $this->registerDefinitionWithExtensions(ExtendableDefinition::class, ScalarExtension::class);
        $extension = static::getContainer()->get(ExtendableDefinition::class);

        static::assertInstanceOf(ExtendableDefinition::class, $extension);
        $extension->getFields()->has('test');
    }

    public function testICanAddRuntimeExtensions(): void
    {
        $this->registerDefinitionWithExtensions(ExtendableDefinition::class, ScalarRuntimeExtension::class);
        $extension = static::getContainer()->get(ExtendableDefinition::class);

        static::assertInstanceOf(ExtendableDefinition::class, $extension);
        static::assertTrue($extension->getFields()->has('test'));
    }

    public function testICanAddFkFieldsAsExtensions(): void
    {
        $this->registerDefinitionWithExtensions(ExtendableDefinition::class, FkFieldExtension::class);
        $extension = static::getContainer()->get(ExtendableDefinition::class);

        static::assertInstanceOf(ExtendableDefinition::class, $extension);
        static::assertTrue($extension->getFields()->has('test'));
    }

    public function testICanAddAssociationExtensions(): void
    {
        $this->registerDefinition(ExtendedDefinition::class);
        $this->registerDefinitionWithExtensions(ExtendableDefinition::class, AssociationExtension::class);
        $extension = static::getContainer()->get(ExtendableDefinition::class);

        static::assertInstanceOf(ExtendableDefinition::class, $extension);
        static::assertTrue($extension->getFields()->has('toOne'));
        static::assertTrue($extension->getFields()->has('toMany'));
    }

    public function testICanAddReferenceVersionAsExtensionWithValidManyToOneAssociation(): void
    {
        $this->registerDefinition(ExtendedDefinition::class);
        $this->registerDefinitionWithExtensions(ExtendableDefinition::class, ReferenceVersionExtension::class);
        $extension = static::getContainer()->get(ExtendableDefinition::class);

        static::assertInstanceOf(ExtendableDefinition::class, $extension);
        static::assertTrue($extension->getFields()->has('toOne'));
        static::assertTrue($extension->getFields()->has('extendedVersionId'));
    }

    /**
     * @return array{id:string, productNumber:string, stock:int, name:string, ean:string, price:array{array{currencyId:string, gross:int, net:int, linked:bool}}, manufacturer:array{name:string}, tax:array{name:string, taxRate:int}, myPrices:array{array{id:string}}}
     */
    private function getPricesData(string $id): array
    {
        $ruleA = Uuid::randomHex();
        $ruleB = Uuid::randomHex();

        static::getContainer()->get('rule.repository')->create(
            [
                ['id' => $ruleA, 'name' => 'test', 'priority' => 1],
                ['id' => $ruleB, 'name' => 'test', 'priority' => 2],
            ],
            Context::createDefaultContext()
        );

        $data = [
            'id' => $id,
            'productNumber' => Uuid::randomHex(),
            'stock' => 1,
            'name' => 'price test',
            'ean' => 'test',
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false]],
            'manufacturer' => ['name' => 'test'],
            'tax' => ['name' => 'test', 'taxRate' => 15],
            'myPrices' => [
                [
                    'id' => $ruleA,
                    'currencyId' => Defaults::CURRENCY,
                    'quantityStart' => 1,
                    'ruleId' => $ruleA,
                    'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false]],
                ],
                [
                    'id' => $ruleB,
                    'currencyId' => Defaults::CURRENCY,
                    'quantityStart' => 1,
                    'ruleId' => $ruleB,
                    'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 8, 'linked' => false]],
                ],
            ],
        ];

        return $data;
    }

    /**
     * @return array{id:string, productNumber:string, stock:int, name:string, ean:string, price:array{array{currencyId:string, gross:int, net:int, linked:bool}}, manufacturer:array{name:string}, tax:array{name:string, taxRate:int}, myCategories:array{array{id:string}}}
     */
    private function getCategoriesData(string $id): array
    {
        $categoryA = Uuid::randomHex();
        $categoryB = Uuid::randomHex();

        static::getContainer()->get('category.repository')->create(
            [
                ['id' => $categoryA, 'name' => 'test', 'position' => 0, 'level' => 1],
                ['id' => $categoryB, 'name' => 'test', 'position' => 1, 'level' => 2],
            ],
            Context::createDefaultContext()
        );

        $data = [
            'id' => $id,
            'productNumber' => Uuid::randomHex(),
            'stock' => 1,
            'name' => 'category test',
            'ean' => 'test',
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false]],
            'manufacturer' => ['name' => 'test'],
            'tax' => ['name' => 'test', 'taxRate' => 15],
            'myCategories' => [
                [
                    'id' => $categoryA,
                ],
                [
                    'id' => $categoryB,
                ],
            ],
        ];

        return $data;
    }
}
