<?php declare(strict_types=1);

namespace Cicada\Storefront;

use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\DependencyInjection\DisableTemplateCachePass;
use Cicada\Storefront\DependencyInjection\StorefrontMigrationReplacementCompilerPass;
use Cicada\Storefront\Framework\ThemeInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @internal
 */
#[Package('framework')]
class Storefront extends Bundle implements ThemeInterface
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->buildDefaultConfig($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection'));
        $loader->load('services.xml');
        $loader->load('seo.xml');
        $loader->load('controller.xml');
        $loader->load('theme.xml');
        if (!Feature::isActive('v6.7.0.0')) {
            $loader->load('theme_6_6_0.xml');
        }

        $container->setParameter('storefrontRoot', $this->getPath());

        $container->addCompilerPass(new DisableTemplateCachePass());
        $container->addCompilerPass(new StorefrontMigrationReplacementCompilerPass());
    }
}
