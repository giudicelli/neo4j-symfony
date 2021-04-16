<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle.
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Whether to use the debug mode.
     *
     * @see https://github.com/doctrine/DoctrineBundle/blob/v1.5.2/DependencyInjection/Configuration.php#L31-L41
     *
     * @var bool
     */
    private $debug;

    /**
     * @param bool $debug
     */
    public function __construct($debug)
    {
        $this->debug = (bool) $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('neo4j');
        // Keep compatibility with symfony/config < 4.2
        if (!method_exists($treeBuilder, 'getRootNode')) {
            $root = $treeBuilder->root('neo4j');
        } else {
            $root = $treeBuilder->getRootNode();
        }

        $root->children()
            ->scalarNode('cache_dir')->defaultNull()->end()
            ->arrayNode('clients')
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('name')
                ->prototype('array')
                ->addDefaultsIfNotSet()
                ->fixXmlConfig('connection')
                ->children()
                    ->arrayNode('connections')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()->end()
            ->arrayNode('node_managers')
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('name')
                ->prototype('array')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('client')->defaultValue('default')->end()
                ->end()
            ->end()->end()
            ->arrayNode('connections')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('name')
                ->prototype('array')
                ->addDefaultsIfNotSet()
                ->children()
                    ->enumNode('scheme')->values(['http', 'bolt'])->defaultValue('bolt')->end()
                    ->scalarNode('host')->defaultValue('localhost')->end()
                    ->scalarNode('port')->end()
                    ->scalarNode('username')->defaultValue('neo4j')->end()
                    ->scalarNode('password')->defaultValue('neo4j')->end()
                    ->scalarNode('dsn')->defaultNull()->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
