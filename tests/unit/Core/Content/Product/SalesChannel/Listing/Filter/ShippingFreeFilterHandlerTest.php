<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel\Listing\Filter;

use Cicada\Core\Content\Product\SalesChannel\Listing\Filter;
use Cicada\Core\Content\Product\SalesChannel\Listing\Filter\ShippingFreeListingFilterHandler;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\MaxAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(ShippingFreeListingFilterHandler::class)]
class ShippingFreeFilterHandlerTest extends TestCase
{
    public function testFilterCanBeSkipped(): void
    {
        $result = (new ShippingFreeListingFilterHandler())->create(
            new Request([], ['shipping-free-filter' => false]),
            $this->createMock(SalesChannelContext::class)
        );

        static::assertNull($result);
    }

    #[DataProvider('filterProvider')]
    public function testFilterCanBeCreated(bool $input): void
    {
        $result = (new ShippingFreeListingFilterHandler())->create(
            new Request(['shipping-free' => $input]),
            $this->createMock(SalesChannelContext::class)
        );

        $expected = new Filter(
            'shipping-free',
            $input,
            [
                new FilterAggregation(
                    'shipping-free-filter',
                    new MaxAggregation('shipping-free', 'product.shippingFree'),
                    [new EqualsFilter('product.shippingFree', true)]
                ),
            ],
            new EqualsFilter('product.shippingFree', true),
            $input
        );

        static::assertEquals($expected, $result);
    }

    public static function filterProvider(): \Generator
    {
        yield 'shipping free' => [true];
        yield 'not shipping free' => [false];
    }
}
