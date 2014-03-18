<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;
use ZF\Hal\View\HalJsonModel;
use ZF\MvcAuth\MvcAuthEvent;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/',
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
        $events->attach(MvcEvent::EVENT_RENDER, array($this, 'onRender'), 400);
    }

    /**
     * Attach the ApiProblem render.error listener if a JSON response is detected
     *
     * @param mixed $e
     */
    public function onRender($e)
    {
        $result = $e->getResult();
        if (! $result instanceof HalJsonModel
            && ! $result instanceof JsonModel
        ) {
            return;
        }

        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();
        $events->attach($services->get('ZF\ApiProblem\RenderErrorListener'));
    }
}
