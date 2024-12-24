<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout;

use Cicada\Core\Checkout\Checkout;
use Cicada\Core\Checkout\DependencyInjection\CompilerPass\CartRedisCompilerPass;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Checkout::class)]
class CheckoutTest extends TestCase
{
    public function testBuildUsesRedisCompilerPass(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'foo');

        $checkout = new Checkout();
        $checkout->build($container);

        $cartRedisCompilerPass = \array_filter(
            $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses(),
            static fn (CompilerPassInterface $pass) => $pass instanceof CartRedisCompilerPass
        );

        static::assertCount(1, $cartRedisCompilerPass);
    }
}
