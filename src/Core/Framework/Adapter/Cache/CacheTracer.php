<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Cache;

use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\SystemConfig\SystemConfigService;

/**
 * @extends AbstractCacheTracer<mixed|null>
 */
#[Package('core')]
class CacheTracer extends AbstractCacheTracer
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $config,
        private readonly AbstractTranslator $translator,
        private readonly CacheTagCollection $collection
    ) {
    }

    public function getDecorated(): AbstractCacheTracer
    {
        throw new DecorationPatternException(self::class);
    }

    public function trace(string $key, \Closure $param)
    {
        return $this->collection->trace($key, fn () => $this->translator->trace($key, fn () => $this->config->trace($key, $param)));
    }

    public function get(string $key): array
    {
        return array_merge(
            $this->collection->getTrace($key),
            $this->config->getTrace($key),
            $this->translator->getTrace($key)
        );
    }
}
