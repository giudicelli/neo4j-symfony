<?php

namespace Neo4j\Neo4jBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Neo4j\Neo4jBundle\DependencyInjection\Neo4jExtension;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jExtensionTest extends AbstractExtensionTestCase
{
    protected function getMinimalConfiguration(): array
    {
        $this->setParameter('kernel.cache_dir', 'foo');

        return ['connections' => ['default' => ['port' => 7474]]];
    }

    protected function getContainerExtensions(): array
    {
        return [
            new Neo4jExtension(),
        ];
    }

    public function testDefaultDsn()
    {
        $this->setParameter('kernel.debug', false);
        $this->load();
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('neo4j.client.default', 0, ['default' => 'bolt://neo4j:neo4j@localhost:7474']);
    }

    public function testDsn()
    {
        $this->setParameter('kernel.debug', false);
        $config = ['connections' => [
            'default' => [
                'dsn' => 'bolt://foo:bar@localhost:7687',
            ],
        ]];

        $this->load($config);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('neo4j.client.default', 0, ['default' => 'bolt://foo:bar@localhost:7687']);
    }
}
