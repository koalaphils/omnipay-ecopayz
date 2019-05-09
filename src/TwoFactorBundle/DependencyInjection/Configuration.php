<?php

declare(strict_types = 1);

namespace TwoFactorBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('two_factor');

        $rootNode
            ->children()
                ->arrayNode('messenger')
                    ->children()
                        ->scalarNode('template_provider')->end()
                        ->arrayNode('sms')
                            ->children()
                                ->scalarNode('sms_messenger_service')->end()
                                ->arrayNode('templates')->scalarPrototype()->end()->end()
                            ->end()
                        ->end()
                        ->arrayNode('email')
                            ->children()
                                ->arrayNode('templates')->scalarPrototype()->end()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('storage')->end()
            ->end();

        return $treeBuilder;
    }
}