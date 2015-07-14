<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility;

use Exception;
use Zend\Mvc\Application as MvcApplication;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\ResponseInterface;

class Application extends MvcApplication
{
    /**
     * Run the application.
     *
     * {@inheritDoc}
     *
     * This method overrides the behavior of Zend\Mvc\Application to wrap the
     * trigger of the route event in a try/catch block, allowing us to catch
     * route listener exceptions and trigger the dispatch.error event.
     *
     * @triggers route(MvcEvent)
     *           Routes the request, and sets the RouteMatch object in the event.
     * @triggers dispatch(MvcEvent)
     *           Dispatches a request, using the discovered RouteMatch and
     *           provided request.
     * @triggers dispatch.error(MvcEvent)
     *           On errors (controller not found, action not supported, etc.),
     *           populates the event with information about the error type,
     *           discovered controller, and controller class (if known).
     *           Typically, a handler should return a populated Response object
     *           that can be returned immediately.
     * @return self
     */
    public function run()
    {
        $events = $this->events;
        $event  = $this->event;

        // Define callback used to determine whether or not to short-circuit
        $shortCircuit = function ($r) use ($event) {
            if ($r instanceof ResponseInterface) {
                return true;
            }
            if ($event->getError()) {
                return true;
            }
            return false;
        };

        // Trigger route event
        try {
            $result = $events->trigger(MvcEvent::EVENT_ROUTE, $event, $shortCircuit);
        } catch (Exception $e) {
            $event->setError(self::ERROR_EXCEPTION)
                ->setParam('exception', $e);
            $result = $events->trigger(MvcEvent::EVENT_DISPATCH_ERROR, $event);

            $response = $result->last();
            if ($response instanceof ResponseInterface) {
                $event->setTarget($this);
                $event->setResponse($response);
                $this->response = $response;
                $events->trigger(MvcEvent::EVENT_FINISH, $event);
                return $this;
            }

            return $this->completeRequest($event);
        }

        if ($result->stopped()) {
            $response = $result->last();
            if ($response instanceof ResponseInterface) {
                $event->setTarget($this);
                $event->setResponse($response);
                $events->trigger(MvcEvent::EVENT_FINISH, $event);
                $this->response = $response;
                return $this;
            }
        }

        if ($event->getError()) {
            return $this->completeRequest($event);
        }

        // Trigger dispatch event
        $result = $events->trigger(MvcEvent::EVENT_DISPATCH, $event, $shortCircuit);

        // Complete response
        $response = $result->last();
        if ($response instanceof ResponseInterface) {
            $event->setTarget($this);
            $event->setResponse($response);
            $events->trigger(MvcEvent::EVENT_FINISH, $event);
            $this->response = $response;
            return $this;
        }

        $response = $this->response;
        $event->setResponse($response);
        $this->completeRequest($event);

        return $this;
    }
}
