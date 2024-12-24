<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\SalesChannel\FindVariant;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Exception\VariantNotFoundException;
use Cicada\Core\Content\Product\SalesChannel\FindVariant\FindProductVariantRoute;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(FindProductVariantRoute::class)]
class FindProductVariantRouteTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $repository;

    private SalesChannelContext $context;

    private FindProductVariantRoute $findProductVariantRoute;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get('product.repository');

        $this->context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create('test', TestDefaults::SALES_CHANNEL);

        $this->findProductVariantRoute = static::getContainer()->get(FindProductVariantRoute::class);

        $this->ids = new IdsCollection();

        $this->createProduct();

        parent::setUp();
    }

    public function testFindVariant(): void
    {
        $options = [
            $this->ids->get('Color') => $this->ids->get('Red'),
            $this->ids->get('Size') => $this->ids->get('XL'),
        ];

        $switched = $this->ids->get('Color');

        $result = $this->findProductVariantRoute->load(
            $this->ids->get('base'),
            new Request(
                [
                    'switchedGroup' => $switched,
                    'options' => $options,
                ]
            ),
            $this->context
        );

        static::assertEquals($this->ids->get('redXL'), $result->getFoundCombination()->getVariantId());
    }

    public function testFindToNotCombinable(): void
    {
        // update red-xl to inactive
        $this->repository->update(
            [
                ['id' => $this->ids->get('redXL'), 'active' => false],
            ],
            Context::createDefaultContext()
        );

        $switched = $this->ids->get('Color');

        $options = [
            $this->ids->get('Color') => $this->ids->get('Red'),
            $this->ids->get('Size') => $this->ids->get('XL'),
        ];

        // wished to switch to red-xl but this variant is not available (active = false).
        // should switch to next matching size
        $result = $this->findProductVariantRoute->load(
            $this->ids->get('base'),
            new Request(
                [
                    'switchedGroup' => $switched,
                    'options' => $options,
                ]
            ),
            $this->context
        );

        static::assertEquals($this->ids->get('redL'), $result->getFoundCombination()->getVariantId());
    }

    public function testFindNoCombinable(): void
    {
        $switched = $this->ids->get('new');

        $options = [
            $this->ids->get('new') => $this->ids->get('new'),
        ];

        static::expectException(VariantNotFoundException::class);
        static::expectExceptionMessage(
            'Variant for productId '
            . $this->ids->get('base') . ' with options {"' . $this->ids->get('new') . '":"' . $this->ids->get('new')
            . '"} not found.'
        );

        $this->findProductVariantRoute->load(
            $this->ids->get('base'),
            new Request(
                [
                    'switchedGroup' => $switched,
                    'options' => $options,
                ]
            ),
            $this->context
        );
    }

    private function createProduct(): void
    {
        (new ProductBuilder($this->ids, 'base', 10))->configuratorSetting(
            'Red',
            'Color'
        )->configuratorSetting(
            'Green',
            'Color'
        )->configuratorSetting(
            'XL',
            'Size'
        )->configuratorSetting(
            'L',
            'Size'
        )->visibility()->price(10)->write(static::getContainer());

        (new ProductBuilder($this->ids, 'redXL', 10))->visibility()->parent('base')->price(10)->option(
            'Red',
            'Color'
        )->option('XL', 'Size')->stock(10)->write(static::getContainer());
        (new ProductBuilder($this->ids, 'greenXL', 10))->visibility()->parent('base')->price(10)->option(
            'Green',
            'Color'
        )->option('XL', 'Size')->stock(10)->write(static::getContainer());
        (new ProductBuilder($this->ids, 'redL', 10))->visibility()->parent('base')->price(10)->option(
            'Red',
            'Color'
        )->option('L', 'Size')->stock(10)->write(static::getContainer());
        (new ProductBuilder($this->ids, 'greenL', 10))->visibility()->parent('base')->price(10)->option(
            'Green',
            'Color'
        )->option('L', 'Size')->stock(10)->write(static::getContainer());
    }
}
