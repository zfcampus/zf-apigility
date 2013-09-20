<?php

namespace ZFTest\Apigility\TestAsset;

use RuntimeException;
use Zend\ServiceManager\ServiceLocatorInterface;

class ServiceManager implements ServiceLocatorInterface
{
    protected $services = array();

    public function get($name)
    {
        if (!$this->has($name)) {
            throw new RuntimeException(sprintf(
                'No service by name of "%s" found',
                $name
            ));
        }

        return $this->services[$name];
    }

    public function has($name)
    {
        return isset($this->services[$name]);
    }

    public function set($name, $service)
    {
        $this->services[$name] = $service;
    }
}
