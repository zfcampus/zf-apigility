<?php

namespace ZFApiFirst\Controller;

use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;

class CallableController extends AbstractController
{
    protected $callable = null;

    public function __construct($callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException('Provided callable is not actually callable');
        }
        $this->callable = $callable;
    }

    /**
     * Execute the request
     *
     * @param MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();

        /** @var $parameterData ParameterDataContainer */
        $parameterData = $this->getEvent()->getParam('ZFApiFirstParameterData');
        if ($parameterData) {
            $parameters = $parameterData->getRouteParams();
        } else {
            $parameters = $routeMatch->getParams();
            unset($parameters['_gateway']);
        }

        // match parameter
        $parameterMatcher = new ParameterMatcher($e);
        $dispatchParameters = $parameterMatcher->getMatchedParameters($this->callable, $parameters);

        // call action
        $actionResponse = call_user_func_array($this->callable, $dispatchParameters);

        // return response as MvcEvent response
        $e->setResult($actionResponse);

        return $actionResponse;
    }
}