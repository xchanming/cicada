<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Event;

use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEventFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\System\Tax\TaxEntity;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class EntityLoadedEventFactoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<ProductCollection>
     */
    private EntityRepository $productRepository;

    private IdsCollection $ids;

    private EntityLoadedEventFactory $entityLoadedEventFactory;

    protected function setUp(): void
    {
        $this->productRepository = static::getContainer()->get('product.repository');
        $this->entityLoadedEventFactory = static::getContainer()->get(EntityLoadedEventFactory::class);
        $this->ids = new IdsCollection();
    }

    public function testCreate(): void
    {
        $builder = (new ProductBuilder($this->ids, 'p1'))
            ->price(10)
            ->category('c1')
            ->manufacturer('m1')
            ->prices('r1', 5);

        $this->productRepository->create([$builder->build()], Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addAssociations([
            'manufacturer',
            'prices',
            'categories',
        ]);

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();
        static::assertNotNull($product);

        $product->addExtension('test', new LanguageCollection([
            (new LanguageEntity())->assign(['id' => $this->ids->create('l1'), '_entityName' => 'language']),
        ]));

        $events = $this->entityLoadedEventFactory->create([$product], Context::createDefaultContext());
        static::assertNotNull($events->getEvents());
        $createdEvents = $events->getEvents()->map(fn (EntityLoadedEvent $event): string => $event->getName());
        sort($createdEvents);

        if (Feature::isActive('v6.7.0.0')) {
            static::assertEquals([
                'category.loaded',
                'language.loaded',
                'product.loaded',
                'product_manufacturer.loaded',
                'product_price.loaded',
            ], $createdEvents);
        } else {
            static::assertEquals([
                'category.loaded',
                'language.loaded',
                'product.loaded',
                'product_manufacturer.loaded',
                'product_price.loaded',
                'tax.loaded',
            ], $createdEvents);
        }
    }

    public function testCollectionWithEntitiesMixed(): void
    {
        $tax = (new TaxEntity())->assign(['_entityName' => 'tax']);

        $events = $this->entityLoadedEventFactory->create([new ProductCollection(), $tax], Context::createDefaultContext());
        static::assertNotNull($events->getEvents());
        $createdEvents = $events->getEvents()->map(fn (EntityLoadedEvent $event): string => $event->getName());
        sort($createdEvents);

        static::assertEquals([
            'tax.loaded',
        ], $createdEvents);
    }
}
