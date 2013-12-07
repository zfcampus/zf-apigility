<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility;

use ZF\MvcAuth\MvcAuthEvent;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => 'src/ZF/Apigility/',
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap($e)
    {
        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();

        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION_POST, $services->get('ZF\Apigility\MvcAuth\UnauthenticatedListener'), 100);
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION_POST, $services->get('ZF\Apigility\MvcAuth\UnauthorizedListener'), 100);
    }
}
