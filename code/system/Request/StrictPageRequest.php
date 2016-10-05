<?php
namespace TMDB\Request;

/**
 * Class StrictPageRequest
 *
 * Used in cases where the endpoint requires certain variables to be sent (as not all do) with the bonus feature of
 * validating against types, e.g. 'string' is validated against is_string(), 'bool' against is_bool() etc. Lol
 *
 * See \TMDB\Request\PageRequest if you don't require validation
 *
 * @package TMDB
 */
class StrictPageRequest extends PageRequest
{
    /**
     * Array of key => type for strict checking
     *
     * @var array
     */
    protected static $types = array();
    

    /**
     * Override to validate type against $params
     *
     * @param $key
     * @param $value
     */
    public function setParam($key, $value) {
        if (!$this->validateType($key, $value)) {
            throw new \InvalidArgumentException("$value is not of valid type for $key");
        }

        parent::setParam($key,$value);
    }

    /**
     * Validates any $types against $args
     *
     * @param $key
     * @param $value
     *
     * @throws \InvalidArgumentException|\RuntimeException
     * @return bool
     */
    public function validateType($key, $value)
    {
        if (!$this->hasTypes() || !array_key_exists($key, static::$types)) {
            return TRUE; // nothing to validate.. good to go
        }

        if (!isset(static::$params[ $key ])) {
            throw new \InvalidArgumentException("$key was found in " . get_called_class() . "::\$types but missing from " . get_called_class() . "::\$params");
        }

        // this will do just fine for now lol.
        if (!function_exists($test = "is_" . static::$types[ $key ])) {
            throw new \RuntimeException(static::$types[ $key ] . " is not a valid php is_* function to test against in " . get_called_class());
        }

        if (!$test($value)) {
            throw new \InvalidArgumentException("$key is not of valid type. Expecting " . static::$types[ $key ] . ", got " . gettype(static::$params[ $key ]));
        }

        return TRUE;

    }

    /**
     * @return bool
     */
    public function hasTypes()
    {
        return (!is_array(static::$types) || empty(static::$types));
    }

}