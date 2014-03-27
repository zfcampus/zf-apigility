<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\MvcAuth;

use Zend\Http\Header\WWWAuthenticate;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use ZF\Apigility\MvcAuth\UnauthorizedListener;
use ZF\MvcAuth\MvcAuthEvent;

class UnauthorizedListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ZF\Apigility\MvcAuth\UnauthorizedListener::__invoke
     */
    public function testInvokePropagates403ResponseWhenAuthenticationHasFailed()
    {
        $unauthorizedListener = new UnauthorizedListener();

        $mvcEvent = new MvcEvent();
        $mvcEvent->setResponse(new Response);

        $mvcAuthEvent = new MvcAuthEvent($mvcEvent, null, null);
        $mvcAuthEvent->setIsAuthorized(false);

        $invokeResponse = $unauthorizedListener->__invoke($mvcAuthEvent);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $invokeResponse);
        $this->assertEquals(403, $invokeResponse->getStatusCode());
        $this->assertEquals('Forbidden', $invokeResponse->getReasonPhrase());
    }

    /**
     * @covers ZF\Apigility\MvcAuth\UnauthorizedListener::__invoke
     */
    public function testInvokeWillPropagateMvcEventResponseHeaders()
    {
        $unauthorizedListener = new UnauthorizedListener();

        $mvcEvent = new MvcEvent();

        $mvcEventResponse = new Response();
        $mvcEventResponse->getHeaders()->addHeader(new WWWAuthenticate());
        $mvcEvent->setResponse($mvcEventResponse);

        $mvcAuthEvent = new MvcAuthEvent($mvcEvent, null, null);
        $mvcAuthEvent->setIsAuthorized(false);

        $invokeResponse = $unauthorizedListener->__invoke($mvcAuthEvent);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $invokeResponse);

        $wwwAuthHeader = $invokeResponse->getHeaders()->get('WWW-Authenticate');
        $this->assertInstanceOf('Zend\Http\Header\WWWAuthenticate', $wwwAuthHeader[0]);
    }
}
