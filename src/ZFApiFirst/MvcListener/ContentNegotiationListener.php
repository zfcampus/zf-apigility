<?php

namespace ZFApiFirst\MvcListener;

use Zend\Mvc\MvcEvent;
use ZFApiFirst\Controller\ParameterDataContainer;
use Zend\Http\Request;

class ContentNegotiationListener
{

    public function __invoke(MvcEvent $e)
    {
        /* @var $request \Zend\Http\Request */
        $request = $e->getRequest();
        $routeMatch = $e->getRouteMatch();

        $parameterData = new ParameterDataContainer();

        // route parameters:
        $routeParams = $routeMatch->getParams();
        unset($routeParams['_gateway']);
        $parameterData->setRouteParams($routeParams);

        // query parameters:
        $parameterData->setQueryParams($_GET);

        // body parameters:
        $bodyParams = array();

        // json & urlencoded content negotiation
        if ($request->isPost() || $request->isPut() || $request->isPatch()) {
            $contentType = $request->getHeader('Content-type');
            if ($contentType && strtolower($contentType->getFieldValue()) == 'application/json') {
                $bodyParams = json_decode($request->getContent(), true);
            } else {
                if ($request->isPost()) {
                    $bodyParams = $_POST;
                } elseif (strtolower($contentType->getFieldValue()) == 'application/x-www-form-urlencoded') {
                    parse_str($request->getContent(), $bodyParams);
                }
            }
        }

        $parameterData->setBodyParams($bodyParams);

        $e->setParam('ZFApiFirstParameterData', $parameterData);
    }
}