<?php
namespace TMDB\Request;

/**
 * Class StrictPageRequest
 *
 * Used in cases where the endpoint requires certain variables to be sent (as not all do) with the bonus feature of
 * validating against types, e.g. 'string' is validated against is_string(), 'bool' against is_bool() etc. Lol
 *
 * @package TMDB
 */
class StrictPageRequest extends TMDBService
{
    /**
     * Holds query string values until request()
     *
     * @var array
     */
    protected static $args = array();

    /**
     * Array of required args
     *
     * @var array
     */
    protected static $required = array();

    /**
     * Array of key => type for strict checking
     *
     * @var array
     */
    protected static $types = array();

    /**
     * @var \ArrayData
     */
    protected static $response;

    /**
     * StrictPageRequest constructor.
     *
     * @throws \RuntimeException
     * @param int $cacheExpiry
     */
    public function __construct($cacheExpiry = NULL) {
        if (empty(static::$args)) {
            throw new \RuntimeException("You must define the protected variable \$args in " . get_called_class() . " when extending StrictPageRequest");
        }

        parent::__construct($cacheExpiry);
    }

    /**
     * Validates any $types against $args
     *
     * @param      $key
     * @param      $value
     * @param bool $throw
     *
     * @return bool
     */
    public function validateType($key, $value, $throw = TRUE)
    {
        if (!$this->hasTypes() || !array_key_exists($key, static::$types)) {
            return TRUE; // nothing to validate.. good to go
        }

        if (!isset(static::$args[ $key ])) {
            throw new \InvalidArgumentException("$key was found in ". get_called_class() . "::\$types but missing from ". get_called_class() . "::\$args");
        }

        $test = "is_" . static::$types[ $key ];

        // this will do just fine for now lol.
        if (function_exists($test)) {
            if (!$test($value)) {
                if ($throw) {
                    throw new \InvalidArgumentException("$key is not of valid type. Expecting " . static::$types[ $key ] . ", got " . gettype(static::$args[ $key ]));
                }

                return FALSE;
            }
        }
        else {
            throw new \RuntimeException(static::$types[ $key ] . " is not a valid php is_* function to test against in ". get_called_class());
        }

        return TRUE;

    }

    /**
     * Checks that the keys in $required isset in $args
     *
     * @throws \RuntimeException
     * @return void;
     */
    public function checkRequired() {

        if (empty(static::$required)) return;

        foreach (static::$required as $key) {
            if (isset(static::$args[$key])) {
                throw new \RuntimeException("$key must be set for" . get_called_class());
            }
        }
    }

    /**
     * @return bool
     */
    public function hasTypes()
    {
        return (!is_array(static::$types) || empty(static::$types));
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
     * @param $value
     */
    public function setArg($key, $value)
    {
        if (!array_key_exists($key, static::$args)) {
            throw new \InvalidArgumentException("$key not found in args");
        }
        if (!$this->validateType($key, $value)) {
            throw new \InvalidArgumentException("$value is not of valid type for $key");
        }
        if (!\ArrayLib::is_associative(static::$args)) {
            throw new \RuntimeException(get_called_class() . "::\$args must be an associative array.");
        }

        static::$args[ $key ] = $value;
    }

    /**
     * Sets the page
     *
     * @param int $page
     */
    public function setPage($page = 1)
    {
        static::$args['page'] = $page;
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
     * @return \RestfulService_Response
     */
    public function request($subURL = '', $method = "GET", $data = NULL, $headers = NULL, $curlOptions = array())
    {
        $this->checkRequired();

        $this->setQueryString(static::$args);

        return parent::request($subURL, $method, $data, $headers, $curlOptions);
    }

    /**
     * @param string $key
     *
     * @return mixed|void
     */
    public function __get($key)
    {
        if (static::$response instanceof \ArrayData) {
            return static::$response->{$key};
        }

    }
}