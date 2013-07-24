<?php

namespace ZFApiFirst\Controller;

use Zend\Mvc\MvcEvent;

class InstanceMethodController extends AbstractRpcController
{
    protected $instance;

    public function __construct($instance)
    {
        $this->instance = $instance;
    }

    /**
     * Execute the request
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();

        $action = $routeMatch->getParam('action', 'not-found');
        $method = static::getMethodFromAction($action);

        if (!method_exists($this->instance, $method)) {
            $method = 'notFoundAction';
        }

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
        $dispatchParameters = $parameterMatcher->getMatchedParameters(array($this->instance, $method), $parameters);

        // call action
        $actionResponse = call_user_func_array(array($this->instance, $method), $dispatchParameters);

        // return response as MvcEvent response
        $e->setResult($actionResponse);

        return $actionResponse;
    }

    /**
     * Transform an "action" token into a method name
     *
     * @param  string $action
     * @return string
     */
    public static function getMethodFromAction($action)
    {
        $method  = str_replace(array('.', '-', '_'), ' ', $action);
        $method  = ucwords($method);
        $method  = str_replace(' ', '', $method);
        $method  = lcfirst($method);

        return $method;
    }

}