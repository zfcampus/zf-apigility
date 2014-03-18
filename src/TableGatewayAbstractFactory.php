<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility;

use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;

class TableGatewayAbstractFactory implements AbstractFactoryInterface
{
    public function canCreateServiceWithName(ServiceLocatorInterface $services, $name, $requestedName)
    {
        if (7 > strlen($requestedName)
            || substr($requestedName, -6) !== '\Table'
        ) {
            return false;
        }

        if (!$services->has('Config')) {
            return false;
        }

        $config = $services->get('Config');
        if (!isset($config['zf-apigility'])
            || !isset($config['zf-apigility']['db-connected'])
        ) {
            return false;
        }

        $config      = $config['zf-apigility']['db-connected'];
        $gatewayName = substr($requestedName, 0, strlen($requestedName) - 6);
        if (!isset($config[$gatewayName])
            || !is_array($config[$gatewayName])
            || !$this->isValidConfig($config[$gatewayName], $services)
        ) {
            return false;
        }

        return true;
    }

    public function createServiceWithName(ServiceLocatorInterface $services, $name, $requestedName)
    {
        $gatewayName       = substr($requestedName, 0, strlen($requestedName) - 6);
        $config            = $services->get('Config');
        $dbConnectedConfig = $config['zf-apigility']['db-connected'][$gatewayName];

        $restConfig = $dbConnectedConfig;
        if (isset($config['zf-rest'])
            && isset($dbConnectedConfig['controller_service_name'])
            && isset($config['zf-rest'][$dbConnectedConfig['controller_service_name']])
        ) {
            $restConfig = $config['zf-rest'][$dbConnectedConfig['controller_service_name']];
        }

        $table      = $dbConnectedConfig['table_name'];
        $adapter    = $this->getAdapterFromConfig($dbConnectedConfig, $services);
        $hydrator   = $this->getHydratorFromConfig($dbConnectedConfig, $services);
        $entity     = $this->getEntityFromConfig($restConfig, $requestedName);

        $resultSetPrototype = new HydratingResultSet($hydrator, new $entity());
        return new TableGateway($table, $adapter, null, $resultSetPrototype);
    }

    protected function isValidConfig(array $config, ServiceLocatorInterface $services)
    {
        if (!isset($config['table_name'])) {
            return false;
        }

        if (isset($config['adapter_name'])
            && $services->has($config['adapter_name'])
        ) {
            return true;
        }

        if (!isset($config['adapter_name'])
            && $services->has('Zend\Db\Adapter\Adapter')
        ) {
            return true;
        }

        return false;
    }

    protected function getAdapterFromConfig(array $config, ServiceLocatorInterface $services)
    {
        if (isset($config['adapter_name'])
            && $services->has($config['adapter_name'])
        ) {
            return $services->get($config['adapter_name']);
        }

        return $services->get('Zend\Db\Adapter\Adapter');
    }

    protected function getHydratorFromConfig(array $config, ServiceLocatorInterface $services)
    {
        $hydratorName = isset($config['hydrator_name']) ? $config['hydrator_name'] : 'ArraySerializable';
        $hydrators    = $services->get('HydratorManager');
        return $hydrators->get($hydratorName);
    }

    protected function getEntityFromConfig(array $config, $requestedName)
    {
        $entity = isset($config['entity_class']) ? $config['entity_class'] : 'stdClass';
        if (!class_exists($entity)) {
            throw new ServiceNotCreatedException(sprintf(
                'Unable to create instance for service "%s"; entity class "%s" cannot be found',
                $requestedName,
                $entity
            ));
        }
        return $entity;
    }
}
