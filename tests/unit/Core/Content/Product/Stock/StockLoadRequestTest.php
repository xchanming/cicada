<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Stock;

use Cicada\Core\Content\Product\Stock\StockLoadRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StockLoadRequest::class)]
class StockLoadRequestTest extends TestCase
{
    public function testStockRequest(): void
    {
        $stockRequest = new StockLoadRequest(['product-1', 'product-2']);

        static::assertEquals(['product-1', 'product-2'], $stockRequest->productIds);
    }
}
