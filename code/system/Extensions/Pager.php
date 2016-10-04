<?php
namespace TMDB\Extensions;

use TMDB\Request\APIService;
use TMDB\Request\TMDBService;

class Pager
{
    /**
     * Sets the default query string for the pager
     * @var string|null
     */
    protected static $queryString;

    /**
     * @var string
     */
    protected static $endpoint;

    /**
     * If the endpoint requires some dynamic value then you can define it here. If set, $endpoint is ignored.
     *
     * @see     \TMDB\Extensions\Pager::$format_search
     * @see     \TMDB\Extensions\Pager::$format_replace
     * @see     \TMDB\Extensions\Pager::init()
     *
     * @example "genres/{genre_id}/movies"
     *
     * @var
     */
    protected static $endpointFormat;

    /**
     * Total discovered pages
     *
     * @var int|null
     */
    protected static $totalPages = NULL;

    /**
     * The page to start on (if sync is interrupted, we can grab last page from here)
     *
     * @var int
     */
    protected static $startingPage;

    /**
     * The key used for SyncMemory to recover from sync interruption
     *
     * @var string
     */
    protected static $memoryKey;

    /**
     * Current Page
     *
     * @var int|null
     */
    protected static $currentPage = NULL;

    /**
     * Stores the TheMovieDB.org response for the current page
     *
     * @var array
     */
    protected static $currentPageData;

    /**
     * Total results
     *
     * @var int|null
     */
    protected static $count = NULL;

    /**
     * @var \TMDB\Request\TMDBService
     */
    protected $TMDBService;

    /**
     * Pager constructor.
     */
    public function __construct()
    {
        if (!$this->TMDBService instanceof TMDBService) {
            $this->TMDBService = new TMDBService();
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
        return self::$currentPage;
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
     * @return array|bool
     */
    public function next()
    {
        if (self::$currentPage >= self::$totalPages) {
            return FALSE;
        }

        // we record the current page here in SyncMemory before incrementing, in case sync is interrupted half
        // way through next page. We don't care for the first page for obvious reasons
        if (isset(static::$memoryKey) && self::$currentPage > 1) {
            $memKey = static::$memoryKey;
            $memory  = \SyncMemory::get()->first();

            if (!$memory) {
                $memory = \SyncMemory::create();
            }

            /** @todo work out how to check if $mem_key is actually a column */
            $memory->{$memKey} = self::$currentPage;
            $memory->write();
        }

        // increment and continue building page request
        self::$currentPage++;
        static::$queryString = array_merge(
            static::$queryString,
            array(
                "page" => self::$currentPage
            )
        );

        $this->TMDBService->setQueryString(
            static::$queryString
        );

        $response = $this->TMDBService->request();
        $json     = $response->getBody();
        $array    = json_decode($json, TRUE);

        return self::$currentPageData = $array;
    }

    /**
     * Returns the current page
     *
     * @return mixed
     */
    public function current()
    {
        return self::$currentPageData;
    }

    /**
     * Get Previous Page
     *
     * @todo could probably clean this up with a DRY helper as its next() but in reverse
     *
     * @return \ArrayList|bool
     */
    public function prev()
    {
        if (self::$currentPage == 0) {
            return FALSE;
        }

        self::$currentPage--;
        static::$queryString = array_merge(
            static::$queryString,
            array(
                "page" => self::$currentPage
            )
        );

        $this->TMDBService->setQueryString(
            static::$queryString
        );

        $response = $this->TMDBService->request();
        $json     = $response->getBody();
        $array    = json_decode($json, TRUE);

        return self::$currentPageData = $array;
    }

    /**
     * Init the pager and loads the first page into memory
     *
     * @param array    $formatVars      Only provide this if you have defined `$endpoint_format` in your class
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
     * @param null|int $maxRuntime      Sets the maximum execution time for a script. Default: php.ini setting
     *
     * @todo very cyclomatic, try to reduce entry points
     *
     * @return $this
     */
    public function init($formatVars = array(), $maxRuntime = NULL)
    {
        // I would like to discuss this if anyone has something constructive
        if (!is_null($maxRuntime) && is_integer($maxRuntime)) {
            set_time_limit($maxRuntime);
        }

        // if the developer provides this function with $format_vars, but no $endpoint_format has been provided by the
        // inheriting class then throw error
        if (!empty($formatVars) && !isset(static::$endpointFormat)) {
            throw new \RuntimeException("You have provided the \$format_vars parameter with an array, however no \$endpoint_format has been set in " . get_called_class());
        }

        // if it has been provided, and no in correct associative format then throw error
        if (!empty($formatVars) && !\ArrayLib::is_associative($formatVars)) {
            throw new \RuntimeException("You have provided the \$format_vars parameter with an array, however it is not in correct format. Please see the PHPDoc for Pager::init()");
        }

        // if it has been provided, and in correct associative format then parse $endpoint_format
        if (!empty($formatVars) && \ArrayLib::is_associative($formatVars)) {

            $formatSearch  = array_map(function ($input) { return "{" . $input . "}"; }, array_keys($formatVars));
            $formatReplace = array_values($formatVars);

            static::$endpoint = str_replace($formatSearch, $formatReplace, static::$endpointFormat);
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
        $this->TMDBService->setEndpoint(static::$endpoint);

        // set query string
        $params = array_merge(
            static::$queryString,
            array(
                "page" => (isset(static::$startingPage)) ? static::$startingPage : 1
            )
        );

        $this->TMDBService->setQueryString($params);

        // fetch a response
        $response = $this->TMDBService->request();
        $json     = $response->getBody();
        $array    = json_decode($json, TRUE);

        // ensure endpoint is pageable
        if (!array_key_exists("total_pages", $array)) {
            throw new \RuntimeException(static::$endpoint . " is not pageable");
        }

        // setup required class variables
        self::$currentPage     = $array[ "page" ];
        self::$count           = $array[ "total_results" ];
        self::$totalPages      = $array[ "total_pages" ];
        self::$currentPageData = $array;

        return $array;
    }
}