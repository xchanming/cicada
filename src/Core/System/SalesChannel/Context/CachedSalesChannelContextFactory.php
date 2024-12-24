<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Context;

use Cicada\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Cicada\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Package('core')]
class CachedSalesChannelContextFactory extends AbstractSalesChannelContextFactory
{
    final public const ALL_TAG = 'sales-channel-context';

    /**
     * @internal
     *
     * @param AbstractCacheTracer<SalesChannelContext> $tracer
     */
    public function __construct(
        private readonly AbstractSalesChannelContextFactory $decorated,
        private readonly CacheInterface $cache,
        private readonly AbstractCacheTracer $tracer
    ) {
    }

    public function getDecorated(): AbstractSalesChannelContextFactory
    {
        return $this->decorated;
    }

    public function create(string $token, string $salesChannelId, array $options = []): SalesChannelContext
    {
        $name = self::buildName($salesChannelId);

        if (!$this->isCacheable($options)) {
            return $this->getDecorated()->create($token, $salesChannelId, $options);
        }

        ksort($options);

        $key = implode('-', [$name, Hasher::hash($options)]);

        $value = $this->cache->get($key, function (ItemInterface $item) use ($name, $token, $salesChannelId, $options) {
            if (Feature::isActive('cache_rework')) {
                $item->tag([$name, self::ALL_TAG]);

                return CacheValueCompressor::compress(
                    $this->decorated->create($token, $salesChannelId, $options)
                );
            }

            $context = $this->tracer->trace($name, fn () => $this->getDecorated()->create($token, $salesChannelId, $options));

            $keys = array_unique(array_merge(
                $this->tracer->get($name),
                [$name, self::ALL_TAG]
            ));

            $item->tag($keys);

            return CacheValueCompressor::compress($context);
        });

        $context = CacheValueCompressor::uncompress($value);

        if (!$context instanceof SalesChannelContext) {
            return $this->getDecorated()->create($token, $salesChannelId, $options);
        }

        $context->assign(['token' => $token]);

        return $context;
    }

    public static function buildName(string $salesChannelId): string
    {
        return 'context-factory-' . $salesChannelId;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function isCacheable(array $options): bool
    {
        return !isset($options[SalesChannelContextService::CUSTOMER_ID])
            && !isset($options[SalesChannelContextService::BILLING_ADDRESS_ID])
            && !isset($options[SalesChannelContextService::SHIPPING_ADDRESS_ID]);
    }
}
