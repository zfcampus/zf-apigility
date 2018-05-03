<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Zend\EventManager\EventManager;
use Zend\Http\PhpEnvironment;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceManager;
use ZF\Apigility\Application;

class ApplicationTest extends TestCase
{
    protected function setUp()
    {
        $events = new EventManager();

        $request = $this->prophesize(PhpEnvironment\Request::class);
        $response = $this->prophesize(PhpEnvironment\Response::class);

        $this->services = $this->setUpServices(
            $this->prophesize(ServiceManager::class),
            $events,
            $request,
            $response
        );

        $this->app = $this->setUpMvcEvent(
            $this->createApplication(
                $this->services->reveal(),
                $events,
                $request->reveal(),
                $response->reveal()
            ),
            $request,
            $response
        );
    }

    /**
     * Create and return an Application instance.
     *
     * Checks to see which version of zend-mvc is present, and uses that to
     * determine how to construct the instance.
     *
     * @param ServiceManager $services
     * @param EventManager $events
     * @param PhpEnvironment\Request $request
     * @param PhpEnvironment\Response $response
     * @return Application
     */
    public function createApplication($services, $events, $request, $response)
    {
        $r = new ReflectionMethod(Application::class, '__construct');
        if ($r->getNumberOfRequiredParameters() === 2) {
            // v2
            return new Application([], $services, $events, $request, $response);
        }

        // v3
        return new Application($services, $events, $request, $response);
    }

    public function setUpServices($services, $events, $request, $response)
    {
        $services->get('config')->willReturn([]);
        $services->get('EventManager')->willReturn($events);
        $services->get('Request')->willReturn($request->reveal());
        $services->get('Response')->willReturn($response->reveal());
        return $services;
    }

    public function setUpMvcEvent($app, $request, $response)
    {
        $event = new MvcEvent();
        $event->setTarget($app);
        $event->setApplication($app)
            ->setRequest($request->reveal())
            ->setResponse($response->reveal());
        $r = new ReflectionProperty($app, 'event');
        $r->setAccessible(true);
        $r->setValue($app, $event);
        return $app;
    }

    public function testRouteListenerRaisingExceptionTriggersDispatchErrorAndSkipsDispatch()
    {
        $events   = $this->app->getEventManager();
        $response = $this->prophesize(PhpEnvironment\Response::class)->reveal();

        $events->attach('route', function ($e) {
            throw new Exception();
        });

        $events->attach('dispatch.error', function ($e) use ($response) {
            $this->assertNotEmpty($e->getError());
            return $response;
        });

        $events->attach('dispatch', function ($e) {
            $this->fail('dispatch event triggered when it should not be');
        });

        $events->attach('render', function ($e) {
            $this->fail('render event triggered when it should not be');
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
