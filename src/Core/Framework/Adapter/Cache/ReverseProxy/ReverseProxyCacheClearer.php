<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Cache\ReverseProxy;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

/**
 * @deprecated tag:v6.7.0 reason:remove-subscriber - Will be removed with no replacement
 *
 * @internal
 */
#[Package('core')]
class ReverseProxyCacheClearer implements CacheClearerInterface
{
    /**
     * @internal
     */
    public function __construct(protected AbstractReverseProxyGateway $gateway)
    {
    }

    public function clear(string $cacheDir): void
    {
        Feature::ifNotActive('v6.7.0.0', fn () => $this->gateway->banAll());
    }
}
