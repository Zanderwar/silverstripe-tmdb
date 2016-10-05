<?php
namespace TMDB\Request;

class PageRequestResponse {
    
    protected static $currentPage;
    protected static $totalPages;
    protected static $totalResults;

    /**
     * @var array
     */
    public $results = array();

    /**
     * PageRequestResponse constructor.
     *
     * @param $response
     */
    public function __construct($response) {
        static::$currentPage = $response['page'];
        static::$totalPages = $response['total_pages'];
        static::$totalResults = $response['total_results'];
        $this->results = $response['results'];
    }

    /**
     * @return mixed
     */
    public static function getCurrentPage()
    {
        return self::$currentPage;
    }

    /**
     * @return mixed
     */
    public static function getTotalPages()
    {
        return self::$totalPages;
    }

    /**
     * @return mixed
     */
    public static function getTotalResults()
    {
        return self::$totalResults;
    }

    public function __toString()
    {
        return 'wtf';
        // TODO: Implement __toString() method.
    }
}