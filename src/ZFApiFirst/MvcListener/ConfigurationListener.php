<?php

namespace ZFApiFirst\MvcListener;

use Zend\Mvc\MvcEvent;

class ConfigurationListener
{
    public function __invoke(MvcEvent $e)
    {
        $app = $e->getParam('application');
        $sm = $app->getServiceManager();

        $config = $app->getServiceManager()->get('configuration');

        /* @var $gateway \ZFApiFirst\ZFApiFirst */
        $gateway = $sm->get('ZFApiFirst');

        if (isset($config['zfapifirst']['configs']) && is_array($config['zfapifirst']['configs'])) {
            foreach ($config['zfapifirst']['configs'] as $gwConfig) {
                $gateway->configure(include $gwConfig);
            }
        }

    }
}