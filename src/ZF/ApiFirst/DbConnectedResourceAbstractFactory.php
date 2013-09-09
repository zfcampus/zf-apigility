<?php

namespace ZF\ApiFirst;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;

class DbConnectedResourceAbstractFactory implements AbstractFactoryInterface
{
    public function canCreateServiceWithName(ServiceLocatorInterface $services, $name, $requestedName)
    {
        if ($services->has('Config')) {
            return false;
        }

        $config = $services->get('Config');
        if (!isset($config['zf-api-first'])
            || !isset($config['zf-api-first']['db-connected'])
        ) {
            return false;
        }

        $config = $config['zf-api-first']['db-connected'];
        if (!isset($config[$requestedName])
            || !is_array($config[$requestedName])
            || !$this->isValidConfig($requestedName, $services)
        ) {
            return false;
        }

        return true;
    }

    public function createServiceWithName(ServiceLocatorInterface $services, $name, $requestedName)
    {
        $table         = $services->get($requestedName . '\Table');
        $config        = $services->get('Config');
        $config        = $config['zf-api-first']['db-connected'][$requestedName];
        $identifier    = $this->getIdentifierFromConfig($config);
        $collection    = $this->getCollectionFromConfig($config, $requestedName);
        $resourceClass = $this->getResourceClassFromConfig($config, $requestedName);

        return new $resourceClass($table, $identifier, $collection);
    }

    protected function isValidConfig($requestedName, ServiceLocatorInterface $services)
    {
        $tableGatewayService = $requestedName . '\Table';
        if (!$services->has($tableGatewayService)) {
            return false;
        }
    }

    protected function getIdentifierFromConfig(array $config)
    {
        $identifier = isset($config['identifier_name']) 
            ? $config['identifier_name'] 
            : $config['table_name'] . '_id';
        return $identifier;
    }

    protected function getCollectionFromConfig(array $config, $requestedName)
    {
        $collection = isset($config['collection_class']) ? $config['collection_class'] : 'Zend\Paginator\Paginator';
        if (!class_exists($collection)) {
            throw new ServiceNotCreatedException(sprintf(
                'Unable to create instance for service "%s"; collection class "%s" cannot be found',
                $requestedName,
                $collection
            ));
        }
        return $collection;
    }

    protected function getResourceClassFromConfig(array $config, $requestedName)
    {
        $defaultClass  = __NAMESPACE__ . '\DbConnectedResource';
        $resourceClass = isset($config['resource_class']) ? $config['resource_class'] : $defaultClass;
        if (!class_exists($resourceClass)
            || !is_subclass_of($resourceClass, $defaultClass)
        ) {
            throw new ServiceNotCreatedException(sprintf(
                'Unable to create instance for service "%s"; resource class "%s" cannot be found or does not extend %s',
                $requestedName,
                $resourceClass,
                $defaultClass
            ));
        }
        return $resourceClass;
    }
}
