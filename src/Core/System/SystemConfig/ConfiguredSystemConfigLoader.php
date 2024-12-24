<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class ConfiguredSystemConfigLoader extends AbstractSystemConfigLoader
{
    public function __construct(
        private readonly AbstractSystemConfigLoader $decorated,
        private readonly SymfonySystemConfigService $config,
    ) {
    }

    public function getDecorated(): AbstractSystemConfigLoader
    {
        return $this->decorated;
    }

    /**
     * @return array<mixed>
     */
    public function load(?string $salesChannelId): array
    {
        $config = $this->decorated->load($salesChannelId);

        return $this->config->override($config, $salesChannelId);
    }
}
