<?php

namespace Ma27\ApiKeyAuthenticationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
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
        $rootNode = $treeBuilder->root('ma27_api_key_authentication');

        $rootNode
            ->children()
                ->arrayNode('user')
                    ->children()
                        ->integerNode('api_key_length')
                            ->min(50)
                            ->defaultValue(200)
                        ->end()
                        ->scalarNode('object_manager')->isRequired()->end()
                        ->scalarNode('model_name')->defaultValue('AppBundle\\Entity\\User')->end()
                        ->arrayNode('password')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('strategy')
                                    ->defaultValue('php55')
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('metadata_cache')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('api_key_purge')
                    ->canBeEnabled()
                    ->children()
                        ->arrayNode('last_action_listener')
                            ->canBeDisabled()
                        ->end()
                        ->scalarNode('outdated_rule')->defaultValue('-5 days')->end()
                    ->end()
                ->end()
                ->arrayNode('services')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('auth_handler')->defaultNull()->end()
                        ->scalarNode('key_factory')->defaultNull()->end()
                    ->end()
                ->end()
                ->scalarNode('key_header')->defaultValue('X-API-KEY')->end()
                ->arrayNode('response')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('api_key_property')->defaultValue('apiKey')->end()
                        ->scalarNode('error_property')->defaultValue('message')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
