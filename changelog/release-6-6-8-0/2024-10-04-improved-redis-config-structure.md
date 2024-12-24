---
title: Improved redis config structure
issue: NEXT-38422
author: Andrii Havryliuk
author_email: a.havryliuk@cicada.com
author_github: h1k3r
---
# Core
* Added support for new configuration parameters:
    * `cicada.redis.connections.dsn` - to define multiple redis connections
    * `cicada.cache.invalidation.delay_options.connection` - to define connection for cache invalidation delay options
    * `cicada.increment.<increment_name>.config.connection` - to define connection for increment storage
    * `cicada.number_range.config.connection` - to define connection for number range storage
    * `cart.storage.config.connection` - to define connection for cart storage
* Deprecated next configuration parameters (should be replaced with `connection` parameter):
    * `cicada.cache.invalidation.delay_options.dsn`
    * `cicada.increment.<increment_name>.config.url`
    * `cicada.number_range.config.dsn`
    * `cart.storage.config.dsn`
* Added `Cicada\Core\Framework\Adapter\Redis\RedisConnectionProvider` to allow retrieving redis connection by name
* Added `Cicada\Core\Framework\Adapter\Redis\RedisConnectionsCompilerPass` to parse configuration and prepare connections for the `RedisConnectionProvider`.
* Added new redis initialization exception types to `Cicada\Core\Framework\Adapter\AdapterException`.
* Changed `Cicada\Core\Framework\DependencyInjection\Configuration` to add support for new configuration parameters.
* Changed container configuration/compiler passes to support both new and old ways of defining redis connections:
  * `src/Core/Checkout/DependencyInjection/cart.xml`
  * `Cicada\Core\Checkout\DependencyInjection\CompilerPass\CartRedisCompilerPass`
  * `src/Core/Framework/DependencyInjection/cache.xml`
  * `Cicada\Core\Framework\Increment\IncrementerGatewayCompilerPass`
  * `src/Core/System/DependencyInjection/number_range.xml`
  * `Cicada\Core\System\DependencyInjection\CompilerPass\RedisNumberRangeIncrementerCompilerPass`
* Changed `Cicada\Core\Framework\Increment\IncrementerGatewayCompilerPass` to use domain exceptions.

___
# Upgrade Information
## Redis configuration

Now you can define multiple redis connections in the `config/packages/cicada.yaml` file under the `cicada` section:
```yaml
cicada:
    # ...
    redis:
        connections:
            connection_1:
                dsn: 'redis://host:port/database_index'
            connection_2:
                dsn: 'redis://host:port/database_index'
```
Connection names should reflect the actual connection purpose/type and be unique, for example `ephemeral`, `persistent`. Also they are used as a part of service names in the container, so they should follow the service naming conventions. After defining connections, you can reference them by name in configuration of different subsystems.

### Cache invalidation

Replace `cicada.cache.invalidation.delay_options.dsn` with `cicada.cache.invalidation.delay_options.connection` in the configuration files:

```yaml
cicada:
    # ...
    cache:
        invalidation:
            delay: 1
            delay_options:
                storage: redis
                # dsn: 'redis://host:port/database_index' # deprecated
                connection: 'connection_1' # new way
```

### Increment storage

Replace `cicada.increment.<increment_name>.config.url` with `cicada.increment.<increment_name>.config.connection` in the configuration files:

```yaml
cicada:
    # ...
    increment:
        increment_name:
            type: 'redis'
            config:
                # url: 'redis://host:port/database_index' # deprecated
                connection: 'connection_2' # new way
```

### Number ranges

Replace `cicada.number_range.config.dsn` with `cicada.number_range.config.connection` in the configuration files:

```yaml
cicada:
    # ...
    number_range:
        increment_storage: "redis"
        config:
            # dsn: 'redis://host:port/dbindex' # deprecated
            connection: 'connection_2' # new way
```

### Cart storage

Replace `cart.storage.config.dsn` with `cart.storage.config.connection` in the configuration files:

```yaml
cicada:
    # ...
    cart:
        storage:
            type: 'redis'
            config:
                #dsn: 'redis://host:port/dbindex' # deprecated
                connection: 'connection_2' # new way
```

### Custom services

If you have custom services that use redis connection, you have next options for the upgrade:

1. Inject `Cicada\Core\Framework\Adapter\Redis\RedisConnectionProvider` and use it to get the connection by name:

    ```xml
    <service id="MyCustomService">
        <argument type="service" id="Cicada\Core\Framework\Adapter\Redis\RedisConnectionProvider" />
        <argument>%myservice.redis_connection_name%</argument>
    </service>
    ```

    ```php
    class MyCustomService
    { 
        public function __construct (
            private RedisConnectionProvider $redisConnectionProvider,
            string $connectionName,
        ) { }

        public function doSomething()
        {
            if ($this->redisConnectionProvider->hasConnection($this->connectionName)) {
                $connection = $this->redisConnectionProvider->getConnection($this->connectionName);
                // use connection
            }
        }
    }
    ```

2. Use `Cicada\Core\Framework\Adapter\Redis\RedisConnectionProvider` as factory to define custom services:

    ```xml
    <service id="my.custom.redis_connection" class="Redis">
        <factory service="Cicada\Core\Framework\Adapter\Redis\RedisConnectionProvider" method="getConnection" />
        <argument>%myservice.redis_connection_name%</argument>
    </service>

    <service id="MyCustomService">
        <argument type="service" id="my.custom.redis_connection" />
    </service>
    ```

    ```php
    class MyCustomService
    { 
        public function __construct (
            private Redis $redisConnection,
        ) { }

        public function doSomething()
        {
            // use connection
        }
    }
    ```
    This approach is especially useful if you need multiple services to share the same connection.

3. Inject connection by name directly:
    ```xml
    <service id="MyCustomService">
        <argument type="service" id="cicada.redis.connection.connection_name" />
    </service>
    ```
   Be cautious with this approach—if you change the Redis connection names in your configuration, it will cause container build errors.

Please beware that redis connections with the **same DSNs** are shared over the system, so closing the connection in one service will affect all other services that use the same connection.  

___
# Next Major Version Changes
## Config keys changes:

Next configuration keys are deprecated and will be removed in the next major version:
* `cicada.cache.invalidation.delay_options.dsn`
* `cicada.increment.<increment_name>.config.url`
* `cicada.number_range.redis_url`
* `cicada.number_range.config.dsn`
* `cicada.cart.redis_url`
* `cart.storage.config.dsn`

To prepare for migration:

1.  For all different redis connections (different DSNs) that are used in the project, add a separate record in the `config/packages/cicada.yaml` file under the `cicada` section, as in upgrade section of this document.
2.  Replace deprecated dsn/url keys with corresponding connection names in the configuration files.
* `cicada.cache.invalidation.delay_options.dsn` -> `cicada.cache.invalidation.delay_options.connection`
* `cicada.increment.<increment_name>.config.url` -> `cicada.increment.<increment_name>.config.connection`
* `cicada.number_range.redis_url` -> `cicada.number_range.config.connection`
* `cicada.number_range.config.dsn` -> `cicada.number_range.config.connection`
* `cicada.cart.redis_url` -> `cart.storage.config.connection`
* `cart.storage.config.dsn` -> `cart.storage.config.connection`
