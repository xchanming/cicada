<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Plugin\_fixture\bundles;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @internal
 */
class FooBarBundle extends Bundle
{
    protected string $name = 'FancyBundleName';
}
