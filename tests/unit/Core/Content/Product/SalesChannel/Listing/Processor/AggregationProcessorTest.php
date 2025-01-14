<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel\Listing\Processor;

use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\SalesChannel\Listing\Filter;
use Cicada\Core\Content\Product\SalesChannel\Listing\Filter\AbstractListingFilterHandler;
use Cicada\Core\Content\Product\SalesChannel\Listing\Processor\AggregationListingProcessor;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[CoversClass(AggregationListingProcessor::class)]
class AggregationProcessorTest extends TestCase
{
    public function testByPassPrepare(): void
    {
        $processor = new AggregationListingProcessor(
            [$foo = new FooListingFilterHandler()],
            $this->createMock(EventDispatcherInterface::class)
        );

        $context = $this->createMock(SalesChannelContext::class);

        $criteria = new Criteria();
        $processor->prepare(new Request(), $criteria, $context);

        static::assertCount(1, $criteria->getAggregations());
        static::assertCount(1, $criteria->getPostFilters());
        static::assertInstanceOf(TermsAggregation::class, $criteria->getAggregation('foo'));
        static::assertTrue($foo->called);
    }

    public function testByPassProcess(): void
    {
        $processor = new AggregationListingProcessor(
            [$foo = new FooListingFilterHandler()],
            $this->createMock(EventDispatcherInterface::class)
        );

        $context = $this->createMock(SalesChannelContext::class);

        $result = new ProductListingResult('test', 0, new ProductCollection(), null, new Criteria(), Context::createDefaultContext());

        $processor->process(new Request(), $result, $context);

        static::assertTrue($foo->called);
    }

    public function testCurrentFiltersAttached(): void
    {
        $processor = new AggregationListingProcessor(
            [new FooListingFilterHandler()],
            $this->createMock(EventDispatcherInterface::class)
        );

        $context = $this->createMock(SalesChannelContext::class);
        $criteria = new Criteria();

        $processor->prepare(new Request(), $criteria, $context);

        $result = new ProductListingResult('test', 0, new ProductCollection(), null, $criteria, Context::createDefaultContext());

        $processor->process(new Request(), $result, $context);

        static::assertEquals(['foo'], $result->getCurrentFilter('foo'));
    }

    public function testReduceAggregationBehavior(): void
    {
        $processor = new AggregationListingProcessor(
            [new FooListingFilterHandler(), new BarListingFilterHandler()],
            $this->createMock(EventDispatcherInterface::class)
        );

        $processor->prepare(
            new Request(['reduce-aggregations' => true]),
            $criteria = new Criteria(),
            $this->createMock(SalesChannelContext::class)
        );

        static::assertCount(2, $criteria->getAggregations());
        static::assertCount(2, $criteria->getPostFilters());

        $agg = $criteria->getAggregation('foo');
        static::assertInstanceOf(FilterAggregation::class, $agg);
        static::assertCount(1, $agg->getFilter());
        $filter = $agg->getFilter();
        $filter = \array_shift($filter);
        static::assertInstanceOf(EqualsFilter::class, $filter);
        static::assertEquals('bar', $filter->getField());

        $agg = $criteria->getAggregation('bar');
        static::assertInstanceOf(FilterAggregation::class, $agg);

        // filter is set to excluded and should not be removed by own list (property filter scenario where you also have to add the property filter to calculate the property filter)
        static::assertCount(2, $agg->getFilter());
    }
}

/**
 * @internal
 */
class FooListingFilterHandler extends AbstractListingFilterHandler
{
    public bool $called = false;

    public function getDecorated(): AbstractListingFilterHandler
    {
        throw new DecorationPatternException(self::class);
    }

    public function create(Request $request, SalesChannelContext $context): ?Filter
    {
        $this->called = true;

        return new Filter('foo', true, [new TermsAggregation('foo', 'foo')], new EqualsFilter('foo', true), ['foo']);
    }

    public function process(Request $request, ProductListingResult $result, SalesChannelContext $context): void
    {
        $this->called = true;
    }
}

/**
 * @internal
 */
class BarListingFilterHandler extends AbstractListingFilterHandler
{
    public function getDecorated(): AbstractListingFilterHandler
    {
        throw new DecorationPatternException(self::class);
    }

    public function create(Request $request, SalesChannelContext $context): ?Filter
    {
        return new Filter('bar', true, [new TermsAggregation('bar', 'bar')], new EqualsFilter('bar', true), [], false);
    }
}
