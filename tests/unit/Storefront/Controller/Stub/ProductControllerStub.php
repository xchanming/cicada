<?php

declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Controller\Stub;

use Cicada\Storefront\Controller\ProductController;
use Cicada\Tests\Unit\Storefront\Controller\StorefrontControllerMockTrait;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class ProductControllerStub extends ProductController
{
    use StorefrontControllerMockTrait;
}
