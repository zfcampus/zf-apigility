<?php
return array(
    'asset_manager' => array(
        'resolver_configs' => array(
            'paths' => array(
                __DIR__ . '/../asset',
            ),
        ),
    ),
    'zf-api-first' => array(
        'db-connected' => array(
        /**
         * This is sample configuration for a DB-connected service.
         * Each such service requires an adapter, a hydrator, an entity, and a 
         * collection.
         *
         * The TableGateway will be called "YourDBConnectedResource\Table" should
         * you wish to retrieve it manually later.
            'YourDBConnectedResource' => array(
                'table_name'       => 'Name of DB table to use',
                'adapter_name'     => 'Service Name for DB adapter to use',
                'hydrator_name'    => 'Service Name for Hydrator to use',
                'entity_class'     => 'Name of entity class to which to hydrate',
                'collection_class' => 'Name of collection class which iterates entities; should be a Paginator extension',
            ),
         */
        ),
    ),
    /*
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => array(
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view/',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'routeParam' => 'ZFApiFirst\Controller\Plugin\RouteParam',
            'queryParam' => 'ZFApiFirst\Controller\Plugin\QueryParam',
            'bodyParam' => 'ZFApiFirst\Controller\Plugin\BodyParam',
            'routeParams' => 'ZFApiFirst\Controller\Plugin\RouteParams',
            'queryParams' => 'ZFApiFirst\Controller\Plugin\QueryParams',
            'bodyParams' => 'ZFApiFirst\Controller\Plugin\BodyParams',
        )
    )
    */
);
