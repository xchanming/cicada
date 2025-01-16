<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\StorefrontPluginConfiguration;

use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
abstract class AbstractStorefrontPluginConfigurationFactory
{
    abstract public function getDecorated(): AbstractStorefrontPluginConfigurationFactory;

    abstract public function createFromBundle(Bundle $bundle): StorefrontPluginConfiguration;

    abstract public function createFromApp(string $appName, string $appPath): StorefrontPluginConfiguration;

    /**
     * @param array<string, mixed> $data
     */
    abstract public function createFromThemeJson(string $name, array $data, string $path): StorefrontPluginConfiguration;
}
