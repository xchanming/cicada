<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\Events;

use Cicada\Core\Content\Product\Events\ProductListingResolvePreviewEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('inventory')]
class ProductListingResolvePreviewEventTest extends TestCase
{
    public function testReplace(): void
    {
        $event = new ProductListingResolvePreviewEvent(
            $this->createMock(SalesChannelContext::class),
            new Criteria(),
            ['p1' => 'p1'],
            true
        );

        $event->replace('p1', 'p2');
        static::assertSame(['p1' => 'p2'], $event->getMapping());
    }

    public function testReplaceException(): void
    {
        $event = new ProductListingResolvePreviewEvent(
            $this->createMock(SalesChannelContext::class),
            new Criteria(),
            ['p1' => 'p1'],
            true
        );

        static::expectException(\RuntimeException::class);
        $event->replace('p3', 'p2');
    }
}
