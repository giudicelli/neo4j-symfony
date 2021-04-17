<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\DependencyInjection;

use Laudis\Neo4j\Network\Bolt\BoltDriver;
use Laudis\Neo4j\Network\Http\HttpDriver;
use Neo4j\OGM\NodeManager;
use Neo4j\OGM\NodeManagerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $connectionUrls = $this->handleConnections($config);
        $this->handleClients($config, $container, $connectionUrls);

        if ($this->validateNodeManagers($config)) {
            $loader->load('node_manager.xml');
            $this->handleNodeManagers($config, $container);
        }
    }

    private function handleNodeManagers(array &$config, ContainerBuilder $container): void
    {
        if (!empty($config['cache_dir'])) {
            $container->getDefinition('neo4j.ogm.metadata_cache')->replaceArgument(0, $config['cache_dir']);
            $container->getDefinition('neo4j.ogm.proxy_factory')->replaceArgument(0, $config['cache_dir']);
        }

        foreach ($config['node_managers'] as $name => $data) {
            $serviceId = sprintf('neo4j.ogm.node_manager.%s', $name);
            $clientName = sprintf('neo4j.client.%s', $data['client']);
            if (!$container->hasDefinition($clientName)) {
                throw new InvalidConfigurationException(sprintf('NodeManager "%s" is configured to use client named "%s" but there is no such client', $name, $clientName));
            }

            $definition = new ChildDefinition('neo4j.ogm.node_manager.abstract');
            $container
                ->setDefinition($serviceId, $definition)
                ->replaceArgument(0, new Reference($clientName));
        }
        if (!$container->hasDefinition('neo4j.ogm.node_manager.default')) {
            throw new InvalidConfigurationException('You need to create a "default" "node_manager"');
        }

        $container->setAlias('neo4j.ogm.node_manager', 'neo4j.ogm.node_manager.default');
        $container->setAlias(NodeManagerInterface::class, 'neo4j.ogm.node_manager.default');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($container->getParameter('kernel.debug') ? true : false);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'neo4j';
    }

    private function handleClients(array &$config, ContainerBuilder $container, array $connectionUrls): void
    {
        if (empty($config['clients'])) {
            // Add default client if none set.
            $config['clients']['default'] = ['connections' => ['default']];
        }

        foreach ($config['clients'] as $name => $data) {
            $connections = [];
            $serviceId = sprintf('neo4j.client.%s', $name);
            foreach ($data['connections'] as $connectionName) {
                if (empty($connectionUrls[$connectionName])) {
                    throw new InvalidConfigurationException(sprintf('Client "%s" is configured to use connection named "%s" but there is no such connection', $name, $connectionName));
                }
                $connections[$connectionName] = $connectionUrls[$connectionName];
            }
            if (empty($connections)) {
                $connections['default'] = $config['connections']['default'];
            }

            $definition = new ChildDefinition('neo4j.client.abstract');
            $container
                ->setDefinition($serviceId, $definition)
                ->setArguments([$connections]);
        }

        // add aliases for the default services
        $container->setAlias('neo4j.client', 'neo4j.client.default');
        $container->setAlias(ClientInterface::class, 'neo4j.client.default');
    }

    /**
     * @return array with connection urls
     */
    private function handleConnections(array &$config): array
    {
        $connectionUrls = [];
        $firstName = null;
        foreach ($config['connections'] as $name => $data) {
            if (null === $firstName || 'default' === $name) {
                $firstName = $name;
            }
            $connectionUrls[$name] = $this->getUrl($data);
        }

        // Make sure we got a 'default'
        if ('default' !== $firstName) {
            $config['connections']['default'] = $config['connections'][$firstName];
        }

        return $connectionUrls;
    }

    /**
     * Get URL form config.
     */
    private function getUrl(array $config): string
    {
        if (null !== $config['dsn']) {
            return $config['dsn'];
        }

        return sprintf(
            '%s://%s:%s@%s:%d',
            $config['scheme'],
            $config['username'],
            $config['password'],
            $config['host'],
            $this->getPort($config)
        );
    }

    /**
     * Return the correct default port if not manually set.
     *
     * @return int
     */
    private function getPort(array $config)
    {
        if (isset($config['port'])) {
            return $config['port'];
        }

        return 'http' == $config['scheme'] ? intval(HttpDriver::DEFAULT_PORT) : BoltDriver::DEFAULT_TCP_PORT;
    }

    /**
     * Make sure the NodeManager is installed if we have configured it.
     *
     * @param array &$config
     *
     * @return bool true if "giudicelli/neo4j-php-ogm" is installed
     *
     * @thorws \LogicException if NodeManager is not installed but they are configured.
     */
    private function validateNodeManagers(array &$config): bool
    {
        $dependenciesInstalled = class_exists(NodeManager::class);
        $nodeManagersConfigured = !empty($config['node_managers']);

        if ($dependenciesInstalled && !$nodeManagersConfigured) {
            // Add default entity manager if none set.
            $config['node_managers']['default'] = ['client' => 'default'];
        } elseif (!$dependenciesInstalled && $nodeManagersConfigured) {
            throw new \LogicException('You need to install "giudicelli/neo4j-php-ogm" to be able to use the NodeManager');
        }

        return $dependenciesInstalled;
    }
}
