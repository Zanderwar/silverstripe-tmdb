<?php
namespace TMDB\Search;

use TMDB\Request\StrictPageRequest;

class Movies extends StrictPageRequest
{

    /**
     * Endpoint
     *
     * @var string
     */
    protected static $endpoint = "search/movie";

    /**
     * The parameters accepted by this endpoint
     *
     * @var array
     */
    protected static $params = array(
        'language'             => NULL, // auto-set to SilverStripe locale in TMDBService but can change here
        'query'                => NULL, // the search string
        'include_adult'        => NULL,
        'year'                 => NULL,
        'primary_release_year' => NULL
    );

    /**
     * The types expected by the parameters for validation purposes
     *
     * @var array
     */
    protected static $types = array(
        'language'             => 'string',
        'page'                 => 'integer',
        'include_adult'        => 'bool',
        'query'                => 'string',
        'year'                 => 'integer',
        'primary_release_year' => 'integer'
    );

    /**
     * The param(s) required by the endpoint
     *
     * @var array
     */
    protected static $required = array(
        'query'
    );

    /**
     * Instance Store
     *
     * @var array
     */
    protected static $multiton = array();

    /**
     * Returns default instance if no parameter provided (if default instance does not exist, will create)
     *
     * @param string $key
     *
     * @return $this
     */
    public static function inst($key = "_MASTER")
    {
        if (isset(static::$multiton[ $key ]) && is_object(static::$multiton[ $key ])) {
            return static::$multiton[ $key ];
        }

        $class = get_called_class();

        return static::$multiton[ $key ] = new $class();
    }


}