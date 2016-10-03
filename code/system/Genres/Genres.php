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
     * @param bool   $assoc Returns the translated JSON string as an object (false), or an associative array (true)
     *
     * @return array
     */
    public function getList($language = null, $assoc = true)
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

        return $result = json_decode($this->APIService->request()->getBody(), $assoc);
    }

    public function getGenreById($genre_id) {
        $list = $this->getCached("genre_list", "getList");


        if (isset($list["genres"])) {
            foreach($list["genres"] as $genre) {
                if ($genre["id"] == $genre_id) {
                    return $genre['name'];
                }
            }
        }

        return false;
    }

    /**
     * @param $genre_name
     *
     * @return bool|int|string
     */
    public function getGenreIdByName($genre_name) {
        $list = $this->getCached("genre_list", "getList");

        if (isset($list["genres"])) {
            foreach($list["genres"] as $genre) {
                if ($genre["name"] == $genre_name) {
                    return $genre['id'];
                }
            }
        }

        return false;
    }

    public function getTotalMoviesInGenre($genre_id) {
        $this->APIService->setEndpoint("genre/$genre_id/movies");
        $response = json_decode($this->APIService->request()->getBody(), true);
        return $response["total_pages"];
    }

    /**
     * @return array
     */
    public function getCached($cachekey, $cachewith_function) {
        $factory = \SS_Cache::factory("tmdb");
        if (!($result = $factory->load($cachekey))) {
            $result = $this->{$cachewith_function}();
            $factory->save(serialize($result), $cachekey);
        }

        return (is_string($result)) ? unserialize($result) : $result;
    }

}