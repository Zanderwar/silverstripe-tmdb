<?php
namespace TMDB;

use TMDB\Request\TMDBService;

/**
 * Class Genre
 * @package TMDB
 */
class Genres
{

    /**
     * @var Request\TMDBService
     */
    protected $TMDBService;

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
        $this->APIService = new TMDBService();
    }

    /**
     * Fetches the genre list from TheMovieDB.org
     *
     * @param string $language Default: en-US
     *
     * @return array
     */
    public function getList($language = NULL)
    {
        if (is_null($language)) {
            $language = str_replace("_", "-", \i18n::get_locale());
        }

        $this->APIService->setEndpoint("genre/movie/list");
        $this->APIService->setQueryString(
            array(
                "language" => $language
            )
        );

        return json_decode($this->APIService->request()->getBody(), TRUE);
    }

    /**
     * Gets a particular GenreID => Name mapping
     *
     * @param string|int $search Can be either the external genre ID (eg 28) or string (eg 'Action')
     *
     * @return mixed
     */
    public function getGenre($search)
    {
        $list = $this->getCached("genre_list", "getList");

        if (isset($list[ "genres" ])) {
            foreach ($list[ "genres" ] as $genre) {
                if ($genre[ "id" ] == $search || $genre[ "name" ] == $search) {
                    return $genre;
                }
            }
        }
    }

    /**
     * Returns the total amount of movies currently in the specified genre
     *
     * @param string|int $search GenreId or Genre Name to valid existence of
     *
     * @return int
     */
    public function getTotalMoviesInGenre($search)
    {
        $genre = $this->getGenre($search);

        $this->APIService->setEndpoint("genre/{$genre['id']}/movies");
        $response = json_decode($this->APIService->request()->getBody(), TRUE);

        if (array_key_exists("total_results", $response)) {
            return $response[ "total_results" ];
        }

        throw new \RuntimeException("total_results key does not exist in payload");
    }

    /**
     * @param string $cacheKey       The cache key that we're looking for
     * @param string $callback       The function name that the cache factory will be filled with if not found
     * @param array  $callbackParams Each required param of the callback function in an array
     *
     * @return mixed
     */
    public function getCached($cacheKey, $callback, $callbackParams = array())
    {
        $factory = \SS_Cache::factory("tmdb");
        if (!($result = $factory->load($cacheKey))) {
            $result = call_user_func_array(array( $this, $callback ), $callbackParams);
            $factory->save(serialize($result), $cacheKey);
        }

        return (is_string($result)) ? unserialize($result) : $result;
    }

    /**
     * Validates whether or not the given input is a valid GenreId or Genre Name
     *
     * @param string|int $search GenreId or Genre Name to valid existence of
     *
     * @return bool
     */
    public function isValidGenre($search)
    {
        $result = $this->getGenre($search);



        return (is_array($result) && array_key_exists("name", $result));
    }

}