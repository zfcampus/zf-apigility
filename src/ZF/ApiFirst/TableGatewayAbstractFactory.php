<?php

namespace ZF\ApiFirst;

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
        if (!isset($config['zf-api-first'])
            || !isset($config['zf-api-first']['db-connected'])
        ) {
            return false;
        }

        $config      = $config['zf-api-first']['db-connected'];
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
        $gatewayName = substr($requestedName, 0, strlen($requestedName) - 6);
        $config      = $services->get('Config');
        $config      = $config['zf-api-first']['db-connected'][$gatewayName];

        $table      = $config['table_name'];
        $adapter    = $this->getAdapterFromConfig($config, $services);
        $hydrator   = $this->getHydratorFromConfig($config, $services);
        $entity     = $this->getEntityFromConfig($config, $requestedName);

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
