<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DependencyInjection\CompilerPass;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\RateLimiterCompilerPass;
use Cicada\Core\Framework\RateLimiter\RateLimiter;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * @internal
 */
#[CoversClass(RateLimiterCompilerPass::class)]
class RateLimiterCompilerPassTest extends TestCase
{
    private Definition $rateLimiterDef;

    protected function setUp(): void
    {
        $config = [
            'cicada.api.rate_limiter' => [
                'cart_add_line_item' => [
                    'enabled' => true,
                    'id' => 'test_limit',
                    'policy' => 'system_config',
                    'reset' => '5 minutes',
                    'limits' => [
                        [
                            'domain' => 'test.limit',
                            'interval' => '10 seconds',
                        ],
                    ],
                ],
            ],
        ];
        $container = new ContainerBuilder(new ParameterBag($config));
        $container->register(RateLimiter::class);

        $rateLimiterCompilerPass = new RateLimiterCompilerPass();
        $rateLimiterCompilerPass->process($container);

        $this->rateLimiterDef = $container->getDefinition('cicada.rate_limiter');
    }

    public function testSystemServiceConfigReference(): void
    {
        static::assertEquals('registerLimiterFactory', $this->rateLimiterDef->getMethodCalls()[0][0]);

        $registerLimiterFactoryCall = $this->rateLimiterDef->getMethodCalls()[0][1];
        static::assertEquals('cart_add_line_item', $registerLimiterFactoryCall[0]);

        /** @var Definition $rateLimiterDef */
        $rateLimiterDef = $registerLimiterFactoryCall[1];

        static::assertEquals(SystemConfigService::class, $rateLimiterDef->getArgument(2));
    }
}
