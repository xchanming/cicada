<?php declare(strict_types=1);

namespace Cicada\Storefront\DependencyInjection;

use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Theme\ConfigLoader\DatabaseAvailableThemeProvider;
use Cicada\Storefront\Theme\ConfigLoader\DatabaseConfigLoader;
use Cicada\Storefront\Theme\SeedingThemePathBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

#[Package('framework')]
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('storefront');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('theme')
                    ->children()
                        ->scalarNode('config_loader_id')->defaultValue(DatabaseConfigLoader::class)->end()
                        ->scalarNode('theme_path_builder_id')->defaultValue(SeedingThemePathBuilder::class)->end()
                        ->scalarNode('available_theme_provider')->defaultValue(DatabaseAvailableThemeProvider::class)->end()
                        ->integerNode('file_delete_delay')->defaultValue(900)->end()
                        ->booleanNode('auto_prefix_css')->defaultFalse()->end()
                        ->arrayNode('allowed_scss_values')->performNoDeepMerging()
                            ->scalarPrototype()->end()
                        ->end()
                        ->booleanNode('validate_on_compile')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
