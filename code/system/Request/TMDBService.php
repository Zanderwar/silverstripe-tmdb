<?php
namespace TMDB\Request;

/**
 * Class APIService
 * @package TMDB\Request
 */
class TMDBService extends \RestfulService {

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
     * @param null   $cache_expiry
     */
    function __construct($cache_expiry=NULL){
        $api_key = \Config::inst()->get("TMDB", "api_key") ?: getenv("TMDB_API_KEY");

        if (!isset($api_key) || !strlen($api_key)) {
            user_error("You must provide your own TheMovieDB.org API key");
        }

        self::$api_key = $api_key;
        parent::__construct(self::$api_url, $cache_expiry);
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

        $requests = $throttle->Requests;

        // if this is the first request getting made ($requests == 0) then let our model know
        if (!(int)$requests) {
            $throttle->FirstRequest = time();
        }

        $requests++;

        $throttle->Requests = $requests;
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

        // give the little guy something to count
        $i = 0;

        $diff = time() - $this->getThrottle()->FirstRequest;

        // rodeo time...
        while (($this->getThrottle()->Requests >= self::$max_requests) || $diff >= self::$max_requests_in_duration)
        {
            $diff = time() - $this->getThrottle()->FirstRequest; // im repeating myself, can't remove it either - looks can be deceiving :(

            if ($diff >= self::$max_requests_in_duration) {
                // the app has been a good boy and has been punished long enough!
                $throttle = $this->getThrottle();
                $throttle->Requests = 0;
                $throttle->write();

                // free it from its misery!
                break;
            }

            // poor little guy, wonder how high he has to count before he BREAKS!
            $i++;
        }

        // oh we are all types of good to go right now
        return;
    }

    /**
     * @return \RestfulService_Response
     */
    public function request($subURL = '', $method = "GET", $data = null, $headers = null, $curlOptions = array()) {

        // we don't want the throttle to run when Travic-CI is build testing
        if (getenv("IS_TRAVIS") != "YES") { $this->runThrottle(); }// pauses script execution until another request can be made

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
        if (getenv("IS_TRAVIS") != "YES") { $this->incThrottle(); }

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