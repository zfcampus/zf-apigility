<?php

namespace ZFApiFirst\Controller;

class ParameterDataContainer
{

    /** @var array */
    protected $routeParams = array();

    /** @var array */
    protected $queryParams = array();

    /** @var array */
    protected $bodyParams = array();

    /**
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * @param array $routeParams
     */
    public function setRouteParams($routeParams)
    {
        $this->routeParams = $routeParams;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasRouteParam($name)
    {
        return (isset($this->routeParams[$name]));
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed
     */
    public function getRouteParam($name, $default = null)
    {
        if (isset($this->routeParams[$name])) {
            return $this->routeParams[$name];
        }
        return $default;
    }

    /**
     * @param $name
     * @param $value
     * @return ParameterDataContainer
     */
    public function setRouteParam($name, $value)
    {
        $this->routeParams[$name] = $value;
        return $this;
    }

    /**
     * @param array $queryParams
     */
    public function setQueryParams($queryParams)
    {
        $this->queryParams = $queryParams;
    }

    /**
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasQueryParam($name)
    {
        return (isset($this->queryParams[$name]));
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    public function getQueryParam($name, $default = null)
    {
        if (isset($this->queryParams[$name])) {
            return $this->queryParams[$name];
        }
        return $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return ParameterDataContainer
     */
    public function setQueryParam($name, $value)
    {
        $this->queryParams[$name] = $value;
        return $this;
    }

    /**
     * @param array $bodyParams
     */
    public function setBodyParams($bodyParams)
    {
        $this->bodyParams = $bodyParams;
    }

    /**
     * @return array
     */
    public function getBodyParams()
    {
        return $this->bodyParams;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasBodyParam($name)
    {
        return (isset($this->bodyParams[$name]));
    }

    /**
     * @param $name
     * @param null $default
     */
    public function getBodyParam($name, $default = null)
    {
        if (isset($this->bodyParams[$name])) {
            return $this->bodyParams[$name];
        }
        return $default;
    }

    /**
     * @param $name
     * @param $value
     * @return ParameterDataContainer
     */
    public function setBodyParam($name, $value)
    {
        $this->bodyParams[$name] = $value;
        return $this;
    }

}
