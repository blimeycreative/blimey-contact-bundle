<?php

namespace Savvy\ContactBundle\DependencyInjection;

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
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('savvy_contact');


        $rootNode->children()
            ->arrayNode("notification_addresses")
                ->requiresAtLeastOneElement()
                ->prototype('scalar')->end()
            ->end()
            ->scalarNode("confirmation_from_address")->isRequired()->end()
            ->arrayNode("contact_thanks_message")
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode("confirmation_subject")
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode("notification_subject")
                ->prototype('scalar')->end()
            ->end()
        ->end();



        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
