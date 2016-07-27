<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility;

use Interop\Container\ContainerInterface;
use stdClass;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;

class TableGatewayAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Can this factory create the requested table gateway?
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if (7 > strlen($requestedName)
            || substr($requestedName, -6) !== '\Table'
        ) {
            return false;
        }

        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');
        if (! isset($config['zf-apigility']['db-connected'])) {
            return false;
        }

        $config      = $config['zf-apigility']['db-connected'];
        $gatewayName = substr($requestedName, 0, strlen($requestedName) - 6);
        if (! isset($config[$gatewayName])
            || ! is_array($config[$gatewayName])
            || ! $this->isValidConfig($config[$gatewayName], $container)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Can this factory create the requested table gateway? (v2)
     *
     * Provided for backwards compatibility; proxies to canCreate().
     *
     * @param ServiceLocatorInterface $container
     * @param string $name
     * @param string $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this->canCreate($container, $requestedName);
    }

    /**
     * Create and return the requested table gateway instance.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return TableGateway
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $gatewayName       = substr($requestedName, 0, strlen($requestedName) - 6);
        $config            = $container->get('config');
        $dbConnectedConfig = $config['zf-apigility']['db-connected'][$gatewayName];

        $restConfig = $dbConnectedConfig;
        if (isset($config['zf-rest'])
            && isset($dbConnectedConfig['controller_service_name'])
            && isset($config['zf-rest'][$dbConnectedConfig['controller_service_name']])
        ) {
            $restConfig = $config['zf-rest'][$dbConnectedConfig['controller_service_name']];
        }

        $table      = $dbConnectedConfig['table_name'];
        $adapter    = $this->getAdapterFromConfig($dbConnectedConfig, $container);
        $hydrator   = $this->getHydratorFromConfig($dbConnectedConfig, $container);
        $entity     = $this->getEntityFromConfig($restConfig, $requestedName);

        $resultSetPrototype = new HydratingResultSet($hydrator, new $entity());
        return new TableGateway($table, $adapter, null, $resultSetPrototype);
    }

    /**
     * Create and return the requested table gateway instance (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @param string $name
     * @param string $requestedName
     * @return TableGateway
     */
    public function createServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this($container, $requestedName);
    }

    /**
     * Is the configuration valid?
     *
     * @param array $config
     * @param ContainerInterface $container
     * @return bool
     */
    protected function isValidConfig(array $config, ContainerInterface $container)
    {
        if (! isset($config['table_name'])) {
            return false;
        }

        if (isset($config['adapter_name'])
            && $container->has($config['adapter_name'])
        ) {
            return true;
        }

        if (! isset($config['adapter_name'])
            && ($container->has(AdapterInterface::class) || $container->has(Adapter::class))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve a zend-db adapter via provided configuration.
     *
     * If the configuration defines an `adapter_name` and a matching service
     * is discovered, that will be returned.
     *
     * If the Adapter service is present, that will be returned (zend-mvc v2).
     *
     * Otherwise, the AdapterInterface service is returned.
     *
     * @param array $config
     * @param ContainerInterface $container
     * @return Adapter|AdapterInterface
     */
    protected function getAdapterFromConfig(array $config, ContainerInterface $container)
    {
        if (isset($config['adapter_name'])
            && $container->has($config['adapter_name'])
        ) {
            return $container->get($config['adapter_name']);
        }

        if ($container->has(Adapter::class)) {
            // v2 usage
            return $container->get(Adapter::class);
        }

        // v3 usage
        return $container->get(AdapterInterface::class);
    }

    /**
     * Retrieve the configured hydrator.
     *
     * If configuration defines a `hydrator_name`, that service will be
     * retrieved from the HydratorManager; otherwise ArraySerializable
     * will be retrieved.
     *
     * @param array $config
     * @param ContainerInterface $container
     * @return \Zend\Hydrator\HydratorInterface
     */
    protected function getHydratorFromConfig(array $config, ContainerInterface $container)
    {
        $hydratorName = isset($config['hydrator_name']) ? $config['hydrator_name'] : 'ArraySerializable';
        $hydrators    = $container->get('HydratorManager');
        return $hydrators->get($hydratorName);
    }

    /**
     * Retrieve the configured entity.
     *
     * If configuration defines an `entity_class`, and the class exists, that
     * value is returned; if no configuration is provided, stdClass is returned.
     *
     * @param array $config
     * @param string $requestedName
     * @return string Class name of entity
     * @throws ServiceNotCreatedException if the entity class cannot be autoloaded.
     */
    protected function getEntityFromConfig(array $config, $requestedName)
    {
        $entity = isset($config['entity_class']) ? $config['entity_class'] : stdClass::class;
        if (! class_exists($entity)) {
            throw new ServiceNotCreatedException(sprintf(
                'Unable to create instance for service "%s"; entity class "%s" cannot be found',
                $requestedName,
                $entity
            ));
        }
        return $entity;
    }
}
