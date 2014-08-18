ZF Apigility
============

[![Build Status](https://travis-ci.org/zfcampus/zf-apigility.png)](https://travis-ci.org/zfcampus/zf-apigility)

Introduction
------------

Meta- Zend Framework 2 module combining features from:

- zf-api-problem
- zf-content-negotiation
- zf-hal
- zf-rest
- zf-rpc
- zf-versioning

in order to provide a cohesive solution for exposing web-based APIs.

Also features database-connected REST resources.

Requirements
------------
  
Please see the [composer.json](composer.json) file.

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

#### db-connected

`db-connected` is an array of resources that can be built via the
[TableGatewayAbstractFactory](#zfapigilitytablegatewayabstractfactory) and the
[DbConnectedResourceAbstractFactory](#zfapigilitydbconnectedresourceabstractfactory) when required
to fulfill the use case of database table-driven resource use cases. The following example
enumerates all of the required and optional configuration necessary to enable this.

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
     */
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
),
```

### System Configuration

The following configuration is required to ensure the proper functioning of this module in Zend
Framework 2 applications, and is provided by the module:

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

ZF2 Services
============

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
