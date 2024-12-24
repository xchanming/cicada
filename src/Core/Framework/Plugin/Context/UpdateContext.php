<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Context;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationCollection;
use Cicada\Core\Framework\Plugin;

#[Package('core')]
class UpdateContext extends InstallContext
{
    public function __construct(
        Plugin $plugin,
        Context $context,
        string $currentCicadaVersion,
        string $currentPluginVersion,
        MigrationCollection $migrationCollection,
        private readonly string $updatePluginVersion
    ) {
        parent::__construct($plugin, $context, $currentCicadaVersion, $currentPluginVersion, $migrationCollection);
    }

    public function getUpdatePluginVersion(): string
    {
        return $this->updatePluginVersion;
    }
}
