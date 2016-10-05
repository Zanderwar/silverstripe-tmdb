<?php
namespace TMDB\Request;

/**
 * Class StrictPageRequest
 *
 * Used in cases where the endpoint requires certain variables to be sent, or none at all.
 *
 * See \TMDB\Request\StrictPageRequest if you require validation
 *
 * @package TMDB
 */
class PageRequest extends TMDBService {

    /**
     * @var \TMDB\Request\TMDBService
     */
    protected $TMDBService;

    /**
     * Holds query string values until request()
     *
     * @var array
     */
    protected static $params = array();

    /**
     * Array of required params
     *
     * @var array
     */
    protected static $required = array();

    /**
     * PageRequest constructor.
     *
     * @param null $cacheExpiry
     */
    public function __construct($cacheExpiry = NULL)
    {
        $this->APIService = new TMDBService();

        parent::__construct($cacheExpiry = NULL);

    }

    /**
     * Quick override to set the query string with our args
     *
     * @param string $subURL
     * @param string $method
     * @param null   $data
     * @param null   $headers
     * @param array  $curlOptions
     *
     * @return PageRequestResponse
     */
    public function request($subURL = '', $method = "GET", $data = NULL, $headers = NULL, $curlOptions = array())
    {
        $this->validateRequired();

        $this->setQueryString(static::$params);

        return new PageRequestResponse(parent::request($subURL, $method, $data, $headers, $curlOptions)->getResponse());
    }

    /**
     * Checks that the keys in $required isset in $args
     *
     * @throws \RuntimeException
     * @return bool;
     */
    public function validateRequired()
    {
        if (empty(static::$required) || empty(static::$params)) {
            return TRUE;
        }

        foreach (static::$required as $key) {
            if (!isset(static::$params[ $key ])) {
                throw new \RuntimeException("$key must be set for " . get_called_class());
            }
        }

        return TRUE;
    }

    /**
     * @return bool
     */
    public function hasRequired()
    {
        return (!is_array(static::$required) || empty(static::$required));
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function isRequired($key)
    {
        return (is_array(static::$required) && in_array($key, static::$required));
    }

    /**
     * @param $array
     *
     * @return $this
     */
    public function setParams($array)
    {
        if (!empty(static::$params) && !\ArrayLib::is_associative(static::$params)) {
            throw new \RuntimeException(get_called_class() . "::\$params must be an associative array.");
        }

        if (!\ArrayLib::is_associative($array)) {
            throw new \RuntimeException("setArgs() parameter must be an associative array.");
        }

        foreach ($array as $k => $v) {
            $this->setParam($k, $v);
        }

        return $this;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function setParam($key, $value)
    {
        if (!empty(static::$params) && !\ArrayLib::is_associative(static::$params)) {
            throw new \RuntimeException(get_called_class() . "::\$params must be an associative array.");
        }

        static::$params[ $key ] = $value;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return static::$params;
    }
    
    /**
     * Sets the page
     *
     * @param int $page
     *
     * @return $this
     */
    public function setPage($page = 1)
    {
        static::$params[ 'page' ] = $page;

        return $this;
    }

    

}