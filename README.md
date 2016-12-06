ZF Apigility
============

[![Build Status](https://travis-ci.org/zfcampus/zf-apigility.png)](https://travis-ci.org/zfcampus/zf-apigility)

Introduction
------------

Meta-module for Zend Framework combining features from:

- zf-api-problem
- zf-content-negotiation
- zf-content-validation
- zf-hal
- zf-mvc-auth
- zf-rest
- zf-rpc
- zf-versioning

in order to provide a cohesive solution for exposing web-based APIs.

Also features database-connected REST resources.

Requirements
------------
  
Please see the [composer.json](composer.json) file.
````console
curl -s http://getcomposer.org/installer | php
````

Installation
------------

Run the following `composer` command:

```console
$ composer require zfcampus/zf-apigility
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

```javascript
"require": {
    "zfcampus/zf-apigility": "^1.3"
}
```

And then run `composer update` to ensure the module is installed.

Finally, add the module name to your project's `config/application.config.php` under the `modules`
key:

```php
return [
    /* ... */
    'modules' => [
        /* ... */
        'ZF\Apigility',
    ],
    /* ... */
];
```

> ### zf-component-installer
>
> If you use [zf-component-installer](https://github.com/zendframework/zf-component-installer),
> that plugin will install zf-apigility, and all modules it depends on, as a
> module in your application configuration for you.

Assets
------

If you are using this module along with the [admin](https://github.com/zfcampus/zf-apigility-admin)
and/or the [welcome screen](https://github.com/zfcampus/zf-apigility-welcome),
this module contains assets that you will need to make web accessible. For that,
you have two options:

- [rwoverdijk/assetmanager](https://github.com/rwoverdijk/AssetManager) is a ZF
  module that provides advanced capabilities around web asset management, and is
  the original tool used by this module. At its current release (1.6.0),
  however, it does not support v3 components from Zend Framework. An upcoming
  1.7.0 release will likely support them.
- [zfcampus/zf-asset-manager](https://github.com/zfcampus/zf-asset-manager) is a
  Composer plugin that acts during installation and uninstallation of packages,
  copying and removing asset trees as defined using the configuration from
  rwoverdijk/assetmanager. To use this, however, you will need to install the
  plugin *first*, and then this module. (If you have already installed this
  module, remove it using `composer remove zfcampus/zf-apigility`.)

Configuration
=============

### User Configuration

The top-level configuration key for user configuration of this module is
`zf-apigility`.

#### db-connected

`db-connected` is an array of resources that can be built via the
[TableGatewayAbstractFactory](#zfapigilitytablegatewayabstractfactory) and the
[DbConnectedResourceAbstractFactory](#zfapigilitydbconnectedresourceabstractfactory) when required
to fulfill the use case of database table-driven resource use cases. The following example
enumerates all of the required and optional configuration necessary to enable this.

Example:

```php
'db-connected' => [
    /**
     * This is sample configuration for a DB-connected service.
     * Each such service requires an adapter, a hydrator, an entity, and a
     * collection.
     *
     * The TableGateway will be called "YourDBConnectedResource\Table" should
     * you wish to retrieve it manually later.
     */
    'YourDBConnectedResource' => [
        'table_service'    => 'Optional; if present, this service will be used as the table gateway',
        'resource_class'   => 'Optional; if present, this class will be used as the db-connected resource',
        'table_name'       => 'Name of DB table to use',
        'identifier_name'  => 'Optional; identifier field in table; defaults to table_name_id or id',
        'adapter_name'     => 'Service Name for DB adapter to use',
        'hydrator_name'    => 'Service Name for Hydrator to use',
        'entity_class'     => 'Name of entity class to which to hydrate',
        'collection_class' => 'Name of collection class which iterates entities; should be a Paginator extension',
    ],
],
```

### System Configuration

The following configuration is required to ensure the proper functioning of this module in Zend
Framework applications, and is provided by the module:

```php
namespace ZF\Apigility;

use Zend\Db\Adapter\AdapterAbstractServiceFactory as DbAdapterAbstractServiceFactory;
use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'asset_manager' => [
        'resolver_configs' => [
            'paths' => [
                __DIR__ . '/../asset',
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'zf-apigility' => [
                'type'  => 'literal',
                'options' => [
                    'route' => '/apigility',
                ],
                'may_terminate' => false,
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            MvcAuth\UnauthenticatedListener::class => InvokableFactory::class,
            MvcAuth\UnauthorizedListener::class => InvokableFactory::class,
        ],
        'abstract_factories' => [
            DbAdapterAbstractServiceFactory::class, // so that db-connected works "out-of-the-box"
            DbConnectedResourceAbstractFactory::class,
            TableGatewayAbstractFactory::class,
        ],
    ],
];
```

ZF Events
=========

### Listeners

#### ZF\Apigility\MvcAuth\UnauthenticatedListener

This listener is attached to `MvcAuthEvent::EVENT_AUTHENTICATION_POST` at priority `100`.  The
primary purpose fo this listener is to override the `zf-mvc-auth` _unauthenticated_ listener in
order to be able to respond with an API-Problem response (vs. a standard HTTP response) on
authentication failure.

#### ZF\Apigility\MvcAuth\UnauthorizedListener

This listener is attached to `MvcAuthEvent::EVENT_AUTHORIZATION_POST` at priority `100`.  The
primary purpose of this listener is to override the `zf-mvc-auth` _unauthorized_ listener in order
to be able to respond with an API-Problem response (vs a standard HTTP response) on authorization
failure.

#### ZF\Apigility\Module

This listener is attached to `MvcEvent::EVENT_RENDER` at priority `400`.  Its purpose is to
conditionally attach `ZF\ApiProblem\RenderErrorListener` when an `MvcEvent`'s result is a
`HalJsonModel` or `JsonModel`, ensuring `zf-api-problem` can render a response in situations where
a rendering error occurs.

ZF Services
===========

### Factories

#### ZF\Apigility\DbConnectedResourceAbstractFactory

This factory uses the requested name in addition to the `zf-apigility.db-connected` configuration
in order to produce `ZF\Apigility\DbConnectedResource` based resources.

#### ZF\Apigility\TableGatewayAbstractFactory

This factory uses the requested name in addition to the `zf-apigility.db-connected` configuration
in order to produce correctly configured `Zend\Db\TableGateway\TableGateway` instances.  These
instances of `TableGateway`s are configured to use the proper `HydratingResultSet` and produce
the configured entities with each row returned when iterated.

### Models

#### ZF\Apigility\DbConnectedResource

This instance serves as the base class for database connected REST resource classes.  This
implementation is an extension of `ZF\Rest\AbstractResourceListener` and can be routed to by
Apigility as a RESTful resource.
