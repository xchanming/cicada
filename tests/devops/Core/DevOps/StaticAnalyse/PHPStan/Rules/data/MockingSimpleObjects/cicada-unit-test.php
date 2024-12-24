<?php

declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Foo;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

class BarTest extends TestCase
{
    public function testFoo(): void
    {
        // not allowed
        $this->createMock(OrderEntity::class);

        // allowed
        $this->createMock(EntitySearchResult::class);
    }
}
