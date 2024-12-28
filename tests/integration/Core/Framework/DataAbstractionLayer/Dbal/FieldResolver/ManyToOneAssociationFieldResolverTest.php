<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver;

use Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Promotion\PromotionDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductMedia\ProductMediaDefinition;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Test\Flow\OrderActionTrait;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver\FieldResolverContext;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver\ManyToOneAssociationFieldResolver;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\TaxAddToSalesChannelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ManyToOneAssociationFieldResolverTest extends TestCase
{
    use KernelTestBehaviour;
    use OrderActionTrait;
    use TaxAddToSalesChannelTestBehaviour;

    protected ManyToOneAssociationFieldResolver $resolver;

    protected QueryBuilder $queryBuilder;

    protected DefinitionInstanceRegistry $definitionInstanceRegistry;

    /**
     * @var EntityRepository<OrderCollection>
     */
    protected EntityRepository $orderRepository;

    /**
     * @var EntityRepository<ProductCollection>
     */
    protected EntityRepository $productRepository;

    protected EntityRepository $orderLineItemRepository;

    protected Connection $connection;

    protected SalesChannelContext $salesChannelContext;

    protected Context $context;

    protected function setUp(): void
    {
        $this->resolver = static::getContainer()->get(ManyToOneAssociationFieldResolver::class);
        $this->queryBuilder = new QueryBuilder(static::getContainer()->get(Connection::class));
        $this->definitionInstanceRegistry = static::getContainer()->get(DefinitionInstanceRegistry::class);
        $this->orderRepository = static::getContainer()->get('order.repository');
        $this->productRepository = static::getContainer()->get('product.repository');
        $this->orderLineItemRepository = static::getContainer()->get('order_line_item.repository');
        $this->connection = static::getContainer()->get(Connection::class);
        $this->context = Context::createDefaultContext();
        $this->salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)->create(
            Uuid::randomHex(),
            TestDefaults::SALES_CHANNEL,
            [SalesChannelContextService::CUSTOMER_ID => $this->createCustomer()]
        );
    }

    public function testVersionConstraintWithVersionedReferenceToVersionedEntity(): void
    {
        // Document itself is not versioned, but has a versioned reference on the versioned order
        $orderLineItemDefinition = $this->definitionInstanceRegistry->get(OrderLineItemDefinition::class);
        $orderDefinition = $this->definitionInstanceRegistry->get(OrderDefinition::class);
        $documentAssociationField = $orderLineItemDefinition->getField('order');

        static::assertNotNull($documentAssociationField);
        $resolverContext = new FieldResolverContext(
            '',
            'order_line_item',
            $documentAssociationField,
            $orderLineItemDefinition,
            $orderDefinition,
            $this->queryBuilder,
            $this->context,
            null,
        );

        $this->resolver->join($resolverContext);

        static::assertSame([
            '`order_line_item`' => [
                [
                    'joinType' => 'left',
                    'joinTable' => '`order`',
                    'joinAlias' => '`order_line_item.order`',
                    'joinCondition' => '`order_line_item`.`order_id` = `order_line_item.order`.`id` AND `order_line_item`.`order_version_id` = `order_line_item.order`.`version_id`',
                ],
            ],
        ], $this->queryBuilder->getQueryPart('join'));
    }

    public function testVersionConstraintWithReferenceToNonVersionedEntity(): void
    {
        // Document and document type are not versioned, thus also document cannot have a versioned reference to its type
        $orderLineItemDefinition = $this->definitionInstanceRegistry->get(OrderLineItemDefinition::class);
        $promotionDefinition = $this->definitionInstanceRegistry->get(PromotionDefinition::class);
        $orderLineItemAssociationField = $orderLineItemDefinition->getField('promotion');

        static::assertNotNull($orderLineItemAssociationField);
        $resolverContext = new FieldResolverContext(
            '',
            'order_line_item',
            $orderLineItemAssociationField,
            $orderLineItemDefinition,
            $promotionDefinition,
            $this->queryBuilder,
            $this->context,
            null,
        );

        $this->resolver->join($resolverContext);

        static::assertSame([
            '`order_line_item`' => [
                [
                    'joinType' => 'left',
                    'joinTable' => '`promotion`',
                    'joinAlias' => '`order_line_item.promotion`',
                    'joinCondition' => '`order_line_item`.`promotion_id` = `order_line_item.promotion`.`id`',
                ],
            ],
        ], $this->queryBuilder->getQueryPart('join'));
    }

    public function testVersionConstraintWithReferenceToSelf(): void
    {
        // Document and document type are not versioned, thus also document cannot have a versioned reference to its type
        $productDefinition = $this->definitionInstanceRegistry->get(ProductDefinition::class);
        $productMediaDefinition = $this->definitionInstanceRegistry->get(ProductMediaDefinition::class);
        $productAssociationField = $productDefinition->getField('canonicalProduct');

        static::assertNotNull($productAssociationField);
        $resolverContext = new FieldResolverContext(
            '',
            'document',
            $productAssociationField,
            $productDefinition,
            $productMediaDefinition,
            $this->queryBuilder,
            $this->context,
            null,
        );

        $this->resolver->join($resolverContext);

        static::assertSame([
            '`document`' => [
                [
                    'joinType' => 'left',
                    'joinTable' => '`product`',
                    'joinAlias' => '`document.canonicalProduct`',
                    'joinCondition' => '`document`.`canonical_product_id` = `document.canonicalProduct`.`id` AND `document`.`canonical_product_version_id` = `document.canonicalProduct`.`version_id`',
                ],
            ],
        ], $this->queryBuilder->getQueryPart('join'));
    }

    public function testVersionConstraintWithOneToOneVersionedReferenceFromVersionedEntity(): void
    {
        // Document itself is not versioned, but has a versioned reference on the versioned order
        $orderDefinition = $this->definitionInstanceRegistry->get(OrderDefinition::class);
        $orderCustomerDefinition = $this->definitionInstanceRegistry->get(OrderCustomerDefinition::class);
        $orderAssociationField = $orderDefinition->getField('orderCustomer');

        static::assertNotNull($orderAssociationField);
        $resolverContext = new FieldResolverContext(
            '',
            'order',
            $orderAssociationField,
            $orderDefinition,
            $orderCustomerDefinition,
            $this->queryBuilder,
            $this->context,
            null,
        );

        $this->resolver->join($resolverContext);

        static::assertSame([
            '`order`' => [
                [
                    'joinType' => 'left',
                    'joinTable' => '`order_customer`',
                    'joinAlias' => '`order.orderCustomer`',
                    'joinCondition' => '`order`.`id` = `order.orderCustomer`.`order_id` AND `order`.`version_id` = `order.orderCustomer`.`order_version_id`',
                ],
            ],
        ], $this->queryBuilder->getQueryPart('join'));
    }

    public function testManyToOneInheritedWorks(): void
    {
        $ids = new IdsCollection();
        $p = (new ProductBuilder($ids, 'p1'))
            ->price(100)
            ->cover('cover')
            ->variant(
                (new ProductBuilder($ids, 'p2'))
                    ->price(200)
                    ->build()
            );

        $connection = static::getContainer()->get(Connection::class);

        $context = Context::createDefaultContext();
        $this->productRepository->create([$p->build()], $context);

        // Old database records don't have a product_media_version_id set
        $connection->executeStatement('UPDATE product SET product_media_version_id = NULL WHERE product_media_id IS NULL');

        $criteria = new Criteria([$ids->get('p1'), $ids->get('p2')]);
        $criteria->addAssociation('cover.media');

        $products = array_values($this->productRepository->search($criteria, $context)->getElements());

        static::assertCount(2, $products);

        [$product1, $product2] = $products;
        static::assertInstanceOf(ProductEntity::class, $product1);
        static::assertInstanceOf(ProductEntity::class, $product2);
        static::assertNotNull($product1->getCover());
        static::assertNull($product2->getCover());

        // Enable inheritance

        $context->setConsiderInheritance(true);

        $products = array_values($this->productRepository->search($criteria, $context)->getElements());

        static::assertCount(2, $products);

        [$product1, $product2] = $products;
        static::assertInstanceOf(ProductEntity::class, $product1);
        static::assertInstanceOf(ProductEntity::class, $product2);
        static::assertNotNull($product1->getCover());
        static::assertNotNull($product2->getCover());
    }
}
