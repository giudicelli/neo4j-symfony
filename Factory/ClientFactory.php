<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Factory;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class ClientFactory
{
    /**
     * Build an Client form multiple connection.
     *
     * @param string $connectionUrls
     */
    public function create(array $connectionUrls): ClientInterface
    {
        // Add connections to connection manager
        $firstName = null;
        $clientBuilder = ClientBuilder::create();
        foreach ($connectionUrls as $name => $url) {
            if (null === $firstName || 'default' === $name) {
                $firstName = $name;
            }
            if ($this->isHttp($url)) {
                $clientBuilder->addHttpConnection($name, $url);
            } else {
                $clientBuilder->addBoltConnection($name, $url);
            }
        }
        $clientBuilder->setDefaultConnection($firstName);

        return $clientBuilder->build();
    }

    /**
     * Determnies if the URL is HTTP.
     */
    private function isHttp(string $url): bool
    {
        return 'http' === parse_url($url, PHP_URL_SCHEME);
    }
}
