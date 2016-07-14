<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\RenderErrorListener;
use ZF\Hal\View\HalJsonModel;
use ZF\MvcAuth\MvcAuthEvent;

class Module
{
    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Listen to application bootstrap event.
     *
     * - Attaches UnauthenticatedListener to authentication.post event.
     * - Attaches UnauthorizedListener to authorization.post event.
     * - Attaches module render listener to render event.
     *
     * @param MvcEvent $e
     * @return void
     */
    public function onBootstrap(MvcEvent $e)
    {
        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();

        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            $services->get(MvcAuth\UnauthenticatedListener::class),
            100
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            $services->get(MvcAuth\UnauthorizedListener::class),
            100
        );
        $events->attach(MvcEvent::EVENT_RENDER, [$this, 'onRender'], 400);
    }

    /**
     * Attach the ApiProblem render.error listener if a JSON response is detected.
     *
     * @param MvcEvent $e
     * @return void
     */
    public function onRender(MvcEvent $e)
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
        $services->get(RenderErrorListener::class)->attach($events);
    }
}
