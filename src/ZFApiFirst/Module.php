<?php

namespace ZFApiFirst;

use Zend\Console\Adapter\AdapterInterface as ConsoleAdapterInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\ModuleRouteListener;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;


class Module implements AutoloaderProviderInterface, ConfigProviderInterface, ServiceProviderInterface
{
    /**
     * @var ZFApiFirst
     */
    protected $apiFirstService = null;

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    /**
     * Setup the service configuration
     *
     * @see \Zend\ModuleManager\Feature\ServiceProviderInterface::getServiceConfig()
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'ZFApiFirst' => function ($sm) {
                    /* @var $sm \Zend\ServiceManager\ServiceManager */
                    return new ZFApiFirst($sm->get('Application'));
                }
            )
        );
    }

    /**
     * Bootstrap time
     *
     * @param MvcEvent $e
     */
    public function onBootstrap($e)
    {
        $app = $e->getApplication();
        $em = $app->getEventManager();
        $sem = $em->getSharedManager();
        $sm = $e->getApplication()->getServiceManager();

        // setup json strategy
        $strategy = $sm->get('ViewJsonStrategy');
        $view = $sm->get('ViewManager')->getView();
        $strategy->attach($view->getEventManager());

        // get the service
        $this->apiFirstService = $sm->get('ZFApiFirst');

        // setup pre-route configuration
        $em->attach(MvcEvent::EVENT_ROUTE, new MvcListener\ConfigurationListener(), 100);

        // setup route listeners
        $em->attach(MvcEvent::EVENT_ROUTE, new MvcListener\ContentNegotiationListener(), -99);

        // result listener, after dispatch
        $resultListener = new MvcListener\ResultListener($this->apiFirstService);
        $em->attach(MvcEvent::EVENT_DISPATCH, $resultListener, -10);
        $sem->attach('Zend\Stdlib\DispatchableInterface', 'dispatch', $resultListener, -79);

        // setup Module Route Listeners
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

}