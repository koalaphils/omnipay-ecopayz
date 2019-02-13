<?php

namespace BrokerageBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('brokerage');

        $rootNode
            ->children()
                ->scalarNode('url')->end()
                ->arrayNode('security')
                    ->children()
                        ->scalarNode('token')->end()
                        ->scalarNode('token_type')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
