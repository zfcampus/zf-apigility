ZF Apigility
============

[![Build Status](https://travis-ci.org/zfcampus/zf-apigility.png)](https://travis-ci.org/zfcampus/zf-apigility)

Introduction
------------

Meta- Zend Framework 2 module combining features from:

- zf-api-problem
- zf-hal
- zf-content-negotiation
- zf-versioning
- zf-rest
- zf-rpc

in order to provide a cohesive solution for exposing web-based APIs.

Also features database and Mongo API generation for the data connected part of
the [Apigility](http://www.apigility.org) project.


Release note
------------

The Mongo API part is not yet complete.


Installation
------------

Run the following `composer` command:

```console
$ composer require "zfcampus/zf-apigility:~1.0-dev"
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

```javascript
"require": {
    "zfcampus/zf-apigility": "~1.0-dev"
}
```

And then run `composer update` to ensure the module is installed.

Finally, add the module name to your project's `config/application.config.php` under the `modules`
key:

```php
return array(
    /* ... */
    'modules' => array(
        /* ... */
        'ZF\Apigility',
    ),
    /* ... */
);
```

Configuration
=============

### User Configuration

The top-level configuration key for user configuration of this module is `zf-api-problem`.

#### `db-connected`

Example:

```php
'db-connected' => array(
/**
 * This is sample configuration for a DB-connected service.
 * Each such service requires an adapter, a hydrator, an entity, and a
 * collection.
 *
 * The TableGateway will be called "YourDBConnectedResource\Table" should
 * you wish to retrieve it manually later.
    'YourDBConnectedResource' => array(
        'table_service'    => 'Optional; if present, this service will be used as the table gateway',
        'resource_class'   => 'Optional; if present, this class will be used as the db-connected resource',
        'table_name'       => 'Name of DB table to use',
        'identifier_name'  => 'Optional; identifier field in table; defaults to table_name_id or id',
        'adapter_name'     => 'Service Name for DB adapter to use',
        'hydrator_name'    => 'Service Name for Hydrator to use',
        'entity_class'     => 'Name of entity class to which to hydrate',
        'collection_class' => 'Name of collection class which iterates entities; should be a Paginator extension',
    ),
 */
),
```

### System Configuration

```php
'asset_manager' => array(
    'resolver_configs' => array(
        'paths' => array(
            __DIR__ . '/../asset',
        ),
    ),
),
'router' => array(
    'routes' => array(
        'zf-apigility' => array(
            'type'  => 'Zend\Mvc\Router\Http\Literal',
            'options' => array(
                'route' => '/apigility',
            ),
            'may_terminate' => false,
        ),
    ),
),
'service_manager' => array(
    'invokables' => array(
        'ZF\Apigility\MvcAuth\UnauthenticatedListener' => 'ZF\Apigility\MvcAuth\UnauthenticatedListener',
        'ZF\Apigility\MvcAuth\UnauthorizedListener' => 'ZF\Apigility\MvcAuth\UnauthorizedListener',
    ),
    'abstract_factories' => array(
        'Zend\Db\Adapter\AdapterAbstractServiceFactory', // so that db-connected works "out-of-the-box"
        'ZF\Apigility\DbConnectedResourceAbstractFactory',
        'ZF\Apigility\TableGatewayAbstractFactory',
    ),
),

```

ZF2 Events
==========

### Events

### Listeners

#### `ZF\Apigility\MvcAuth\UnauthenticatedListener`

#### `ZF\Apigility\MvcAuth\UnauthorizedListener`

ZF2 Services
============

#### `ZF\Apigility\DbConnectedResourceAbstractFactory`

#### `ZF\Apigility\TableGatewayAbstractFactory`


