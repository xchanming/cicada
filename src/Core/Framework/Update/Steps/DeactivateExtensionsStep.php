<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Update\Steps;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Services\AbstractExtensionLifecycle;
use Cicada\Core\Framework\Update\Services\ExtensionCompatibility;
use Cicada\Core\Framework\Update\Struct\Version;
use Cicada\Core\System\SystemConfig\SystemConfigService;

#[Package('services-settings')]
class DeactivateExtensionsStep
{
    final public const UPDATE_DEACTIVATED_PLUGINS = 'core.update.deactivatedPlugins';

    public function __construct(
        private readonly Version $toVersion,
        private readonly string $deactivationFilter,
        private readonly ExtensionCompatibility $pluginCompatibility,
        private readonly AbstractExtensionLifecycle $extensionLifecycleService,
        private readonly SystemConfigService $systemConfigService,
        private readonly Context $context
    ) {
    }

    /**
     * Remove one plugin per run call, as this action can take some time we make a new request for each plugin
     */
    public function run(int $offset): ValidResult
    {
        $extensions = $this->pluginCompatibility->getExtensionsToDeactivate($this->toVersion, $this->context, $this->deactivationFilter);

        $extensionCount = \count($extensions);
        if ($extensionCount === 0) {
            return new ValidResult($offset, $offset);
        }

        $extension = $extensions[0];
        ++$offset;
        $this->extensionLifecycleService->deactivate($extension->getType(), $extension->getName(), $this->context);

        $deactivatedPlugins = (array) $this->systemConfigService->get(self::UPDATE_DEACTIVATED_PLUGINS) ?: [];
        $deactivatedPlugins[] = $extension->getId();
        $this->systemConfigService->set(self::UPDATE_DEACTIVATED_PLUGINS, $deactivatedPlugins);

        if ($extensionCount === 1) {
            return new ValidResult($offset, $offset);
        }

        return new ValidResult($offset, $extensionCount + $offset);
    }
}
