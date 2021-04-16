<?php

namespace Neo4j\Neo4jBundle\Tests\Functional;

use Laudis\Neo4j\Contracts\ClientInterface;
use Neo4j\OGM\NodeManager;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class BundleInitializationTest extends BaseTestCase
{
    public function testRegisterBundle()
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();

        $this->assertTrue($container->has('neo4j.client'));
        $client = $container->get('neo4j.client');
        $this->assertInstanceOf(ClientInterface::class, $client);

        if (class_exists('Neo4j\OGM\NodeManager')) {
            $this->assertTrue($container->has('neo4j.ogm.node_manager'));
            $client = $container->get('neo4j.ogm.node_manager');
            $this->assertInstanceOf(NodeManager::class, $client);
        }
    }
}
