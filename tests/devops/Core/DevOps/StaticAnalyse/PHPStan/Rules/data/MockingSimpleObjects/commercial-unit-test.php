<?php

declare(strict_types=1);

namespace Cicada\Commercial\Tests\Unit\Foo;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

class BarTest extends TestCase
{
    public function testFoo(): void
    {
        // not allowed
        $this->createMock(OrderEntity::class);

        // allowed
        $this->createMock(SalesChannelContext::class);
    }
}
