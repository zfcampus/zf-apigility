<?php

namespace ZFApiFirst\Controller;

use Zend\Mvc\Controller\AbstractActionController as BaseAbstractActionController;
use Zend\Mvc\MvcEvent;

abstract class AbstractRpcController extends BaseAbstractActionController
{

    /**
     * Execute the request
     *
     * @param  MvcEvent $e
     * @return mixed
     * @throws Exception\DomainException
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();

        $action = $routeMatch->getParam('action', 'not-found');
        $method = static::getMethodFromAction($action);

        if (!method_exists($this, $method)) {
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
        $dispatchParameters = $parameterMatcher->getMatchedParameters(array($this, $method), $parameters);

        // call action
        $actionResponse = call_user_func_array(array($this, $method), $dispatchParameters);

        // return response as MvcEvent response
        $e->setResult($actionResponse);

        return $actionResponse;
    }

}
