<?php

namespace ZFApiFirst\Controller;

//use Zend\Mvc\Controller\ControllerManager as MvcControllerManager;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\DispatchableInterface;


class ControllerAbstractFactory implements AbstractFactoryInterface
{
    protected $controllerManager = null;

    protected $controllers = array();

    /**
     * @param $controller
     */
    public function addController($controller)
    {
        $this->controllers[] = $controller;
    }

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return (in_array($requestedName, $this->controllers));
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $controller = new $requestedName;
        if (!$controller instanceof DispatchableInterface) {
            $instanceController = new InstanceMethodController($controller);
            return $instanceController;
        }
        return $controller;
    }
}
