<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationCollection;

/**
 * @deprecated tag:v6.7.0 - Will be removed
 */
#[Package('framework')]
interface StorefrontPluginRegistryInterface
{
    public function getConfigurations(): StorefrontPluginConfigurationCollection;
}
