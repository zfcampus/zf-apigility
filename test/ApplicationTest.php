<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility;

use Exception;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;
use Zend\EventManager\EventManager;
use Zend\Mvc\MvcEvent;
use ZF\Apigility\Application;

class ApplicationTest extends TestCase
{
    public function setUp()
    {
        $events = new EventManager();

        $request = $this->getMockBuilder('Zend\Http\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $response = $this->getMockBuilder('Zend\Http\PhpEnvironment\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $services = $this->getMockBuilder('Zend\ServiceManager\ServiceManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->services = $services = $this->setUpServices($services, $events, $request, $response);

        $app = new Application([], $services);
        $this->app = $app = $this->setUpMvcEvent($app, $request, $response);
    }

    public function setUpServices($services, $events, $request, $response)
    {
        $services->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('EventManager'))
            ->willReturn($events);
        $services->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('Request'))
            ->willReturn($request);
        $services->expects($this->at(2))
            ->method('get')
            ->with($this->equalTo('Response'))
            ->willReturn($response);

        return $services;
    }

    public function setUpMvcEvent($app, $request, $response)
    {
        $event = new MvcEvent();
        $event->setTarget($app);
        $event->setApplication($app)
            ->setRequest($request)
            ->setResponse($response);
        $r = new ReflectionProperty($app, 'event');
        $r->setAccessible(true);
        $r->setValue($app, $event);
        return $app;
    }

    public function testRouteListenerRaisingExceptionTriggersDispatchErrorAndSkipsDispatch()
    {
        $phpunit  = $this;
        $events   = $this->app->getEventManager();
        $response = $this->getMockBuilder('Zend\Http\PhpEnvironment\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $events->attach('route', function ($e) {
            throw new Exception();
        });

        $events->attach('dispatch.error', function ($e) use ($phpunit, $response) {
            $phpunit->assertNotEmpty($e->getError());
            return $response;
        });

        $events->attach('dispatch', function ($e) use ($phpunit) {
            $phpunit->fail('dispatch event triggered when it should not be');
        });

        $events->attach('render', function ($e) use ($phpunit) {
            $phpunit->fail('render event triggered when it should not be');
        });

        $finishTriggered = false;
        $events->attach('finish', function ($e) use (&$finishTriggered) {
            $finishTriggered = true;
        });

        $this->app->run();
        $this->assertTrue($finishTriggered);
        $this->assertSame($response, $this->app->getResponse());
    }
}
