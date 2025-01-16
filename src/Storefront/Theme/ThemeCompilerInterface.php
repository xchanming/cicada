<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationCollection;

#[Package('framework')]
interface ThemeCompilerInterface
{
    public function compileTheme(
        string $salesChannelId,
        string $themeId,
        StorefrontPluginConfiguration $themeConfig,
        StorefrontPluginConfigurationCollection $configurationCollection,
        bool $withAssets,
        Context $context
    ): void;
}
