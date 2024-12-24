<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeDefinition;
use Cicada\Core\Checkout\Document\DocumentDefinition;
use Cicada\Core\Checkout\Document\DocumentEntity;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver\FieldResolverContext;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver\ManyToOneAssociationFieldResolver;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Cicada\Tests\Integration\Core\Checkout\Document\DocumentTrait;

/**
 * @internal
 */
class ManyToOneAssociationFieldResolverTest extends TestCase
{
    use DocumentTrait;
    use KernelTestBehaviour;

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

    protected EntityRepository $documentRepository;

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
        $this->documentRepository = static::getContainer()->get('document.repository');
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
        $documentDefinition = $this->definitionInstanceRegistry->get(DocumentDefinition::class);
        $orderDefinition = $this->definitionInstanceRegistry->get(OrderDefinition::class);
        $documentAssociationField = $documentDefinition->getField('order');

        static::assertNotNull($documentAssociationField);
        $resolverContext = new FieldResolverContext(
            '',
            'document',
            $documentAssociationField,
            $documentDefinition,
            $orderDefinition,
            $this->queryBuilder,
            $this->context,
            null,
        );

        $this->resolver->join($resolverContext);

        static::assertSame([
            '`document`' => [
                [
                    'joinType' => 'left',
                    'joinTable' => '`order`',
                    'joinAlias' => '`document.order`',
                    'joinCondition' => '`document`.`order_id` = `document.order`.`id` AND `document`.`order_version_id` = `document.order`.`version_id`',
                ],
            ],
        ], $this->queryBuilder->getQueryPart('join'));
    }

    public function testVersionConstraintWithReferenceToNonVersionedEntity(): void
    {
        // Document and document type are not versioned, thus also document cannot have a versioned reference to its type
        $documentDefinition = $this->definitionInstanceRegistry->get(DocumentDefinition::class);
        $documentTypeDefinition = $this->definitionInstanceRegistry->get(DocumentTypeDefinition::class);
        $documentAssociationField = $documentDefinition->getField('documentType');

        static::assertNotNull($documentAssociationField);
        $resolverContext = new FieldResolverContext(
            '',
            'document',
            $documentAssociationField,
            $documentDefinition,
            $documentTypeDefinition,
            $this->queryBuilder,
            $this->context,
            null,
        );

        $this->resolver->join($resolverContext);

        static::assertSame([
            '`document`' => [
                [
                    'joinType' => 'left',
                    'joinTable' => '`document_type`',
                    'joinAlias' => '`document.documentType`',
                    'joinCondition' => '`document`.`document_type_id` = `document.documentType`.`id`',
                ],
            ],
        ], $this->queryBuilder->getQueryPart('join'));
    }

    public function testVersionConstraintWithReferenceToSelf(): void
    {
        // Document and document type are not versioned, thus also document cannot have a versioned reference to its type
        $documentDefinition = $this->definitionInstanceRegistry->get(DocumentDefinition::class);
        $documentTypeDefinition = $this->definitionInstanceRegistry->get(DocumentDefinition::class);
        $documentAssociationField = $documentDefinition->getField('referencedDocument');

        static::assertNotNull($documentAssociationField);
        $resolverContext = new FieldResolverContext(
            '',
            'document',
            $documentAssociationField,
            $documentDefinition,
            $documentTypeDefinition,
            $this->queryBuilder,
            $this->context,
            null,
        );

        $this->resolver->join($resolverContext);

        static::assertSame([
            '`document`' => [
                [
                    'joinType' => 'left',
                    'joinTable' => '`document`',
                    'joinAlias' => '`document.referencedDocument`',
                    'joinCondition' => '`document`.`referenced_document_id` = `document.referencedDocument`.`id`',
                ],
            ],
        ], $this->queryBuilder->getQueryPart('join'));
    }

    public function testVersionConstraintWithOneToOneVersionedReferenceFromVersionedEntity(): void
    {
        // Document itself is not versioned, but has a versioned reference on the versioned order
        $orderDefinition = $this->definitionInstanceRegistry->get(OrderDefinition::class);
        $orderCustomerDefinition = $this->definitionInstanceRegistry->get(DocumentDefinition::class);
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

    public function testCorrectOrderVersionOverAssociationOverRepositorySearch(): void
    {
        // 1. Create a new order and extract order number
        $cart = $this->generateDemoCart(2);
        $orderId = $this->persistCart($cart);

        $order = $this->orderRepository->search(new Criteria([$orderId]), $this->context)->first();
        static::assertInstanceOf(OrderEntity::class, $order);

        // 2. Generate a document attached to the order
        $this->createDocument('invoice', $orderId, [], $this->context);

        // 3. Set created order version to be lexicographically smaller than the live version
        $this->connection->executeStatement(
            'UPDATE `order`
            SET `version_id` = :newVersionId
            WHERE `version_id` != :liveVersionId AND `id` = :orderId',
            [
                'newVersionId' => hex2bin('00000000000000000000000000000000'),
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
                'orderId' => hex2bin($orderId),
            ],
        );

        // 4. Search for the document over the order number of its attached order
        $documents = $this->documentRepository->search(
            (new Criteria())
                ->addFilter(new EqualsFilter('order.orderNumber', $order->getOrderNumber()))
                ->addAssociation('order')
                ->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT),
            $this->context,
        );

        static::assertCount(1, $documents);
        static::assertEquals(1, $documents->getTotal());

        $document = $documents->getEntities()->first();
        static::assertInstanceOf(DocumentEntity::class, $document);
        static::assertNotNull($document->getOrder());
        static::assertEquals('00000000000000000000000000000000', $document->getOrder()->getVersionId());
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
