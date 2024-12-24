<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\SalesChannel\ProductCloseoutFilter;
use Cicada\Core\Content\Product\SalesChannel\ProductCloseoutFilterFactory;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[CoversClass(ProductCloseoutFilterFactory::class)]
class ProductCloseoutFilterFactoryTest extends TestCase
{
    public function testCreatesProductCloseoutFilter(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $filter = (new ProductCloseoutFilterFactory())->create($context);

        static::assertEquals(new ProductCloseoutFilter(), $filter);
    }

    public function testGetDecoratedThrowsDecorationPatternException(): void
    {
        static::expectException(DecorationPatternException::class);
        (new ProductCloseoutFilterFactory())->getDecorated();
    }
}
