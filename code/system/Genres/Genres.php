<?php
namespace TMDB;

use TMDB\Request\APIService;

/**
 * Class Genre
 * @package TMDB
 */
class Genres
{

    /**
     * @var Request\APIService
     */
    protected $APIService;

    /**
     * @var array
     */
    protected static $multiton = array();

    /**
     * Returns default instance store if no parameter provided (if default instance does not exist, will create)
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

    /**
     * Genre constructor.
     */
    public function __construct()
    {
        $this->APIService = new APIService();
    }

    /**
     * Fetches the genre list from TheMovieDB.org
     *
     * @param string $language Default: en-US
     * @param bool   $assoc Returns the translated JSON string as an object (false), or an associative array (true)
     *
     * @return array
     */
    public function fetch($language = "en-US", $assoc = false)
    {
        $this->APIService->setEndpoint("genre/movie/list");
        $this->APIService->setQueryString(
            array(
                "language" => $language
            )
        );
        return $result = json_decode($this->APIService->request()->getBody(), $assoc);
    }


}