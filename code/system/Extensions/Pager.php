<?php
namespace TMDB\Extensions;

use TMDB\Request\APIService;

class Pager
{
    /**
     * Sets the default query string for the pager
     * @var string|null
     */
    protected static $query_string;

    /**
     * @var string
     */
    protected static $endpoint;

    /**
     * If the endpoint requires some dynamic value then you can define it here. If set, $endpoint is ignored.
     *
     * @see     TMDB\Extensions\Pager::$format_search
     * @see     TMDB\Extensions\Pager::$format_replace
     * @see     TMDB\Extensions\Pager::init()
     *
     * @example "genres/{genre_id}/movies"
     *
     * @var
     */
    protected static $endpoint_format;

    /**
     * Total discovered pages
     *
     * @var int|null
     */
    protected static $total_pages = NULL;

    /**
     * The page to start on (if sync is interrupted, we can grab last page from here)
     *
     * @var int
     */
    protected static $starting_page;

    /**
     * The key used for SyncMemory to recover from sync interruption
     *
     * @var string
     */
    protected static $memory_key;

    /**
     * Current Page
     *
     * @var int|null
     */
    protected static $current_page = NULL;

    /**
     * Stores the TheMovieDB.org response for the current page
     *
     * @var array
     */
    protected static $current_page_data;

    /**
     * Total results
     *
     * @var int|null
     */
    protected static $count = NULL;

    /**
     * @var \TMDB\Request\APIService
     */
    protected $APIService;

    /**
     * Pager constructor.
     */
    public function __construct()
    {
        if (!$this->APIService instanceof APIService) {
            $this->APIService = new APIService();
        }
    }

    /**
     * Endpoint Getter
     *
     * @return string
     */
    public static function getEndpoint()
    {
        return self::$endpoint;
    }

    /**
     * Current Page Getter
     *
     * @return int|null
     */
    public static function getCurrentPage()
    {
        return self::$current_page;
    }

    /**
     * Returns how many records will be synchronized
     *
     * @return int|null
     */
    public static function getCount()
    {
        return self::$count;
    }

    /**
     * Get Next Page
     *
     * @todo could probably clean this up with a DRY helper as it's prev() but in reverse
     *
     * @return array
     */
    public function next()
    {
        $current_page = self::$current_page;
        $total_pages  = self::$total_pages;

        if ($current_page >= $total_pages) {
            return FALSE;
        }

        // we record the current page here in SyncMemory before incrementing, in case sync is interrupted half
        // way through next page. We don't care for the first page for obvious reasons
        if (isset(static::$memory_key) && $current_page > 1) {
            $mem_key = static::$memory_key;
            $memory = \SyncMemory::get()->first();

            if (!$memory) {
                $memory = \SyncMemory::create();
            }

            /** @todo work out how to check if $mem_key is actually a column */
            $memory->{$mem_key} = $current_page;
            $memory->write();
        }

        // increment and continue building page request
        $current_page++;
        static::$query_string = array_merge(
            static::$query_string,
            array(
                "page" => $current_page
            )
        );

        $this->APIService->setQueryString(
            static::$query_string
        );

        self::$current_page = $current_page;

        $response = $this->APIService->request();
        $json     = $response->getBody();
        $array    = json_decode($json, TRUE);

        return self::$current_page_data = $array;
    }

    /**
     * Returns the current page
     *
     * @return mixed
     */
    public function current()
    {
        return self::$current_page_data;
    }

    /**
     * Get Previous Page
     *
     * @todo could probably clean this up with a DRY helper as its next() but in reverse
     *
     * @return \ArrayList
     */
    public function prev()
    {
        $current_page = self::$current_page;

        if ($current_page == 0) {
            return FALSE;
        }

        $current_page--;
        static::$query_string = array_merge(
            static::$query_string,
            array(
                "page" => $current_page
            )
        );

        $this->APIService->setQueryString(
            static::$query_string
        );

        self::$current_page = $current_page;

        $response = $this->APIService->request();
        $json     = $response->getBody();
        $array    = json_decode($json, TRUE);

        return self::$current_page_data = $array;
    }

    /**
     * Init the pager and loads the first page into memory
     *
     * @param array $format_vars        Only provide this if you have defined `$endpoint_format` in your class
     *                                  Example:
     *                                  ```
     *                                  class YourClass extends Pager {
     *                                  $endpoint_format = "genres/{genre_id}/movies";
     *                                  }
     *
     *                           $genre = \Genre::get()->first();
     *                           YourClass::init(
     *                              array(
     *                                  "genre_id" => $genre->GenreId
     *                              )
     *                           );
     *                           ```
     *
     * @param null  $max_execution_time Sets the maximum execution time for a script. Default: php.ini setting
     *
     * @return $this
     */
    public function init($format_vars = array(), $max_execution_time = NULL)
    {
        // I would like to discuss this if anyone has something constructive
        if (!is_null($max_execution_time) && is_integer($max_execution_time)) {
            set_time_limit($max_execution_time);
        }

        // if the developer provides this function with $format_vars, but no $endpoint_format has been provided by the
        // inheriting class then throw error
        if (!empty($format_vars) && !isset(static::$endpoint_format)) {
            throw new \RuntimeException("You have provided the \$format_vars parameter with an array, however no \$endpoint_format has been set in " . get_called_class());
        }

        // if it has been provided, and no in correct associative format then throw error
        if (!empty($format_vars) && !\ArrayLib::is_associative($format_vars)) {
            throw new \RuntimeException("You have provided the \$format_vars parameter with an array, however it is not in correct format. Please see the PHPDoc for Pager::init()");
        }

        // if it has been provided, and in correct associative format then parse $endpoint_format
        if (!empty($format_vars) && \ArrayLib::is_associative($format_vars)) {

            $format_search  = array_map(function ($input) { return "{" . $input . "}"; }, array_keys($format_vars));
            $format_replace = array_values($format_vars);

            static::$endpoint = str_replace($format_search, $format_replace, static::$endpoint_format);
        }

        // if we still don't have an endpoint after all that then throw error
        if (!isset(static::$endpoint)) {
            throw new \RuntimeException("\$endpoint must be set in " . get_called_class() . " before calling " . get_called_class() . "::getFirstPage()");
        }

        // load first page into memory
        self::getFirstOrStartingPage();

        return $this;
    }

    /**
     * Loads the first page into memory and sets up the class
     *
     * @return int|null
     */
    public function getFirstOrStartingPage()
    {
        // set endpoint
        $this->APIService->setEndpoint(static::$endpoint);

        // set query string
        if (isset(static::$starting_page)) {
            $this->APIService->setQueryString(
                array_merge(
                    static::$query_string,
                    array(
                        "page" => static::$starting_page
                    )
                )
            );
        }
        else {
            $this->APIService->setQueryString(
                static::$query_string
            );
        }

        // fetch a response
        $response = $this->APIService->request();
        $json     = $response->getBody();
        $array    = json_decode($json, TRUE);

        // ensure endpoint is pageable
        if (!array_key_exists("total_pages", $array)) {
            throw new \RuntimeException(static::$endpoint . " is not pageable");
        }

        // setup required class variables
        self::$current_page      = $array[ "page" ];
        self::$count             = $array[ "total_results" ];
        self::$total_pages       = $array[ "total_pages" ];
        self::$current_page_data = $array;

        return $array;
    }
}