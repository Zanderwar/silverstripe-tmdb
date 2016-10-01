<?php
namespace TMDB\Request;

/**
 * Class APIService
 * @package TMDB\Request
 */
class APIService extends \RestfulService {

    /**
     * TheMovieDB.org API Url
     *
     * @var string
     */
    protected static $api_url = "http://api.themoviedb.org/3/"; // version 3

    /**
     * Max Requests
     *
     * @var int
     */
    protected static $max_requests = 40;

    /**
     * Seconds
     *
     * @var int
     */
    protected static $max_requests_in_duration = 10;

    /**
     * TheMovieDB.org API Key
     * 
     * @var string
     */
    private static $api_key;

    /**
     * The endpoint (eg "movies", "genres/movies/list")
     *
     * @var string
     */
    protected static $endpoint;

    /**
     * @var int
     */
    protected $cache_expire = 0;

    /**
     * APIService constructor.
     *
     * @param null   $expiry
     */
    function __construct($expiry=NULL){
        $config = \SiteConfig::current_site_config();

        if (!isset($config->tmdb_api_key) || !strlen($config->tmdb_api_key)) {
            user_error("You must provide your own TheMovieDB.org API key");
        }

        self::$api_key = $config->tmdb_api_key;
        parent::__construct(self::$api_url, $expiry);
    }

    /**
     * Returns the throttle record
     *
     * @return \DataObject
     */
    private function getThrottle() {
        $throttle = \Throttle::get()->first();

        if (!$throttle) {
            $throttle = \Throttle::create();
        }

        return $throttle;
    }

    /**
     * Increments the throttle counter
     *
     * @return bool
     */
    private function incThrottle() {
        $throttle = $this->getThrottle();
        $throttle->Requests = $throttle->Requests++;
        $throttle->LastRequest = time();

        return ($throttle->write()) ? true : false;
    }

    /**
     * Pauses script execution until another request can be made. Open to opinions or a PR?
     *
     * @note This is only used by server, and "should not" at any point in time be invoked by a visitor
     * @label help-wanted
     *
     * @return void
     */
    private function runThrottle() {

        $i = 0;
        $trigger = false;
        while (($this->getThrottle()->Requests >= self::$max_requests) && ((time() - $this->getThrottle()->LastRequest) <= self::$max_requests_in_duration))
        {
            $trigger = true; // should this ever be true, then requests will reset back to zero once the loop ends;
            $i++;
        }

        if ($trigger) {
            $this->getThrottle()->Requests = 0;
            $this->getThrottle()->write();
        }

        return;
    }

    /**
     * @return \RestfulService_Response
     */
    public function request() {

        $this->runThrottle(); // pauses script execution until another request can be made

        // convert parents query string back into an array
        parse_str($this->queryString, $params);

        // if null create the array
        if (is_null($params)) {
            $params = array();
        }

        // if api_key is not in the array, add it
        if (!array_key_exists("api_key", $params)) {
            $params['api_key'] = static::$api_key;
            $this->setQueryString($params);
        }

        // fetch a response
        $result = parent::request();

        // increment the throttle counter
        $this->incThrottle();

        // check if the request is unauthorized (usually bad api key)
        if ($result->getStatusCode() == 401) {
            $unauthorized = json_decode($result->getBody());
            throw new \RuntimeException("TheMovieDB.org said: " . $unauthorized->status_message);
        }

        return $result;
    }

    /**
     * Modify the baseURL set by \RestfulService on construct
     *
     * @param $base_url
     */
    public function setBaseUrl($base_url) {
        $this->baseURL = $base_url;
    }

    /**
     * Sets the correct baseUrl for the endpoint
     *
     * @param $endpoint
     */
    public function setEndpoint($endpoint) {
        self::$endpoint = $endpoint;

        // trim starting slash
        $endpoint = ltrim($endpoint, "/");

        $this->setBaseUrl(self::$api_url . $endpoint);
    }

}