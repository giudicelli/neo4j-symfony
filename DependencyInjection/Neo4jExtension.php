<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\DependencyInjection;

use Laudis\Neo4j\Network\Bolt\BoltDriver;
use Laudis\Neo4j\Network\Http\HttpDriver;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
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

        // add aliases for the default services
        $container->setAlias('neo4j.client', 'neo4j.client.default');
        $container->setAlias(ClientInterface::class, 'neo4j.client.default');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'neo4j';
    }

    /**
     * @return array with service ids
     */
    private function handleClients(array &$config, ContainerBuilder $container, array $connectionUrls): array
    {
        if (empty($config['clients'])) {
            // Add default entity manager if none set.
            $config['clients']['default'] = ['connections' => ['default']];
        }

        $serviceIds = [];
        foreach ($config['clients'] as $name => $data) {
            $connections = [];
            $serviceIds[$name] = $serviceId = sprintf('neo4j.client.%s', $name);
            foreach ($data['connections'] as $connectionName) {
                if (empty($connectionUrls[$connectionName])) {
                    throw new InvalidConfigurationException(sprintf('Client "%s" is configured to use connection named "%s" but there is no such connection', $name, $connectionName));
                }
                $connections[$connectionName] = $connectionUrls[$connectionName];
            }
            if (empty($connections)) {
                $connections['default'] = $config['connections']['default'];
            }

            $definition = class_exists(ChildDefinition::class)
                ? new ChildDefinition('neo4j.client.abstract')
                : new DefinitionDecorator('neo4j.client.abstract');

            $container
                ->setDefinition($serviceId, $definition)
                ->setArguments([$connections]);
        }

        return $serviceIds;
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

        return 'http' == $config['scheme'] ? HttpDriver::DEFAULT_PORT : BoltDriver::DEFAULT_TCP_PORT;
    }
}
