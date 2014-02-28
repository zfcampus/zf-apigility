<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

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
