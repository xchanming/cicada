<?php

declare(strict_types=1);

namespace Cicada\Commercial\Tests\Unit\Foo;

use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\TestCase;

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
