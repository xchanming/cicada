<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme;

use Cicada\Core\Framework\App\Source\SourceResolver;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin;
use Cicada\Core\Framework\Util\Filesystem;
use Cicada\Core\Kernel;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @internal
 */
#[Package('storefront')]
class ThemeFilesystemResolver
{
    /**
     * @var list<string>
     */
    private readonly array $bundleNames;

    public function __construct(
        private readonly SourceResolver $sourceResolver,
        private readonly Kernel $kernel,
    ) {
        // get all bundles + plugin names
        // we need to do this because at boot a plugin might not be active (eg during plugin:activate command) and thus not in `getBundles()`
        // but getPluginInstances does not include local bundles (eg Storefront)
        $this->bundleNames = array_values(array_unique(array_merge(
            array_keys($this->kernel->getBundles()),
            array_map(fn (Plugin $plugin): string => $plugin->getName(), $this->kernel->getPluginLoader()->getPluginInstances()->all())
        )));
    }

    public function getFilesystemForStorefrontConfig(StorefrontPluginConfiguration $configuration): Filesystem
    {
        if (!\in_array($configuration->getTechnicalName(), $this->bundleNames, true)) {
            // it's an app
            return $this->sourceResolver->filesystemForAppName($configuration->getTechnicalName());
        }

        try {
            $bundle = $this->kernel->getBundle($configuration->getTechnicalName());
        } catch (\InvalidArgumentException $e) {
            $bundles = $this->kernel->getPluginLoader()
                ->getPluginInstances()
                ->filter(fn (Plugin $plugin) => $plugin->getName() === $configuration->getTechnicalName())
                ->all();

            $bundle = array_values($bundles)[0];
        }

        \assert($bundle instanceof BundleInterface);

        return new Filesystem($bundle->getPath());
    }
}
