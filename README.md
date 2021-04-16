# Neo4j Symfony Bundle

This a fork of [Neo4j Symfony Bundle](https://github.com/neo4j-php/neo4j-symfony), it uses [Laudis Neo4j PHP Client](https://github.com/neo4j-php/neo4j-php-client) which is the only client [recommended by Neo4j](https://neo4j.com/developer/php/). We got rid of GraphAware, which was a mess.

## Install

Via Composer

``` bash
$ composer require giudicelli/neo4j-bundle
```

If you want to use the an `NodeManager` you need to install an [OGM](https://github.com/giudicelli/neo4j-php-ogm)

```bash
$ composer require giudicelli/neo4j-php-ogm
```

Enable the bundle in your kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Neo4j\Neo4jBundle\Neo4jBundle(),
    );
}
```

## Documentation

The bundle is a convenient way of registering services. We register `Clients`. You will always have alias for the default service:

 * neo4j.client
 * neo4j.node_manager.*

### Minimal configuration

```yaml
neo4j:
  connections:
    default: ~
```

With the minimal configuration we have services named:
 * neo4j.connection.default
 * neo4j.client.default
 * neo4j.node_manager.default*

### Full configuration

```yaml
neo4j:
  cache_dir: "%kernel.cache_dir%"
  connections:
    default:
      scheme: bolt # default (must be either "http" or "bolt")
      host: localhost # default
      port: 7474 # optional, will be set to the proper driver's default port if not provided
      username: neo4j # default
      password: neo4j # default
    second_connection:
      username: foo
      password: bar
    third_connection:
      dsn: 'bolt://foo:bar@localhost:7687'
  clients:
    default:
      connections: [default, second_connection, third_connection]
    other_client:
      connections: [second_connection]
    foobar: ~ # foobar client will have the "default" connection
  entity_managers:
    default: 
      client: other_client # defaults to "default"
```
With the configuration above we would have services named:
 * neo4j.client.default
 * neo4j.client.other_client
 * neo4j.client.other_foobar
 * neo4j.node_manager.default*

\* Note: NodeManager will only be available if `giudicelli/neo4j-php-ogm` is installed. 

## Testing

``` bash
$ composer test
```
## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
