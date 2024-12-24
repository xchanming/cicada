<?php

declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Foo;

use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Tests\Unit\Administration\AdministrationTest;

class BarTest extends AdministrationTest
{
    public function testFoo(): void
    {
        $this->createMock(OrderEntity::class);
    }
}
