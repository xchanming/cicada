<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Cache\ReverseProxy;

use Cicada\Core\Framework\Adapter\Cache\ReverseProxy\AbstractReverseProxyGateway;
use Cicada\Core\Framework\Adapter\Cache\ReverseProxy\ReverseProxyCacheClearer;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Test\Annotation\DisabledFeatures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ReverseProxyCacheClearer::class)]
class ReverseProxyCacheClearerTest extends TestCase
{
    #[DisabledFeatures(['v6.7.0.0'])]
    public function testClear(): void
    {
        $gateway = $this->createMock(AbstractReverseProxyGateway::class);
        $gateway->expects(static::once())
            ->method('banAll');

        $clearer = new ReverseProxyCacheClearer($gateway);
        $clearer->clear('noop');
    }

    public function testClear67(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);

        $gateway = $this->createMock(AbstractReverseProxyGateway::class);
        $gateway->expects(static::never())
            ->method('banAll');

        $clearer = new ReverseProxyCacheClearer($gateway);
        $clearer->clear('noop');
    }
}
