<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\SalesChannel\Entity;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Category\SalesChannel\SalesChannelCategoryDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductDefinition;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelDefinitionInstanceRegistry;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('buyers-experience')]
class SalesChannelDefinitionTest extends TestCase
{
    use IntegrationTestBehaviour;

    private SalesChannelDefinitionInstanceRegistry $registry;

    /**
     * @var EntityRepository<ProductCollection>
     */
    private EntityRepository $apiRepository;

    private SalesChannelRepository $salesChannelProductRepository;

    private AbstractSalesChannelContextFactory $factory;

    protected function setUp(): void
    {
        $this->registry = static::getContainer()->get(SalesChannelDefinitionInstanceRegistry::class);
        $this->apiRepository = static::getContainer()->get('product.repository');
        $this->salesChannelProductRepository = static::getContainer()->get('sales_channel.product.repository');
        $this->factory = static::getContainer()->get(SalesChannelContextFactory::class);
    }

    public function testAssociationReplacement(): void
    {
        $fields = static::getContainer()->get(SalesChannelProductDefinition::class)->getFields();

        $categories = $fields->get('categories');

        /** @var ManyToManyAssociationField $categories */
        static::assertSame(
            static::getContainer()->get(SalesChannelCategoryDefinition::class)->getClass(),
            $categories->getToManyReferenceDefinition()->getClass()
        );

        static::assertSame(
            static::getContainer()->get(SalesChannelCategoryDefinition::class),
            $categories->getToManyReferenceDefinition()
        );

        $fields = static::getContainer()->get(ProductDefinition::class)->getFields();
        $categories = $fields->get('categories');

        /** @var ManyToManyAssociationField $categories */
        static::assertSame(
            static::getContainer()->get(CategoryDefinition::class),
            $categories->getToManyReferenceDefinition()
        );
    }

    public function testDefinitionRegistry(): void
    {
        static::assertSame(
            static::getContainer()->get(SalesChannelProductDefinition::class),
            $this->registry->getByEntityName('product')
        );
    }

    public function testRepositoryCompilerPass(): void
    {
        static::assertInstanceOf(
            SalesChannelRepository::class,
            static::getContainer()->get('sales_channel.product.repository')
        );
    }

    public function testLoadEntities(): void
    {
        $id = Uuid::randomHex();

        $data = [
            'id' => $id,
            'productNumber' => 'test',
            'stock' => 10,
            'active' => true,
            'name' => 'test',
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false]],
            'manufacturer' => ['name' => 'test'],
            'tax' => ['name' => 'test', 'taxRate' => 15],
            'categories' => [
                ['id' => $id, 'name' => 'asd'],
            ],
            'visibilities' => [
                [
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];

        $this->apiRepository->create([$data], Context::createDefaultContext());

        $dispatcher = static::getContainer()->get('event_dispatcher');
        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');
        $this->addEventListener($dispatcher, 'sales_channel.product.loaded', $listener);

        $context = $this->factory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('categories');

        $products = $this->salesChannelProductRepository->search($criteria, $context);

        static::assertCount(1, $products);

        /** @var SalesChannelProductEntity $product */
        $product = $products->first();
        static::assertInstanceOf(SalesChannelProductEntity::class, $product);

        $categories = $product->getCategories();

        static::assertNotNull($categories);
        static::assertCount(1, $categories);
    }
}
