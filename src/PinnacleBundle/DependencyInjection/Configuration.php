<?php

declare(strict_types = 1);

namespace PinnacleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pinnacle');

        $rootNode
            ->children()
                ->scalarNode('agent_code')->cannotBeEmpty()->end()
                ->arrayNode('api')
                    ->children()
                        ->scalarNode('url')->cannotBeEmpty()->end()
                        ->scalarNode('agent_key')->cannotBeEmpty()->end()
                        ->scalarNode('secret_key')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}