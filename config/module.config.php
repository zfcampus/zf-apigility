<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

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
    'zf-apigility' => [
        'db-connected' => [
        // @codingStandardsIgnoreStart
        /*
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
        // @codingStandardsIgnoreEnd
        ],
    ],
];
