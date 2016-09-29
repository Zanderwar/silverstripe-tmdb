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
    protected static $api_url = "http://api.themoviedb.org/3/";

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
    public function getThrottle() {
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
    public function incThrottle() {
        $throttle = $this->getThrottle();
        $throttle->Requests = $throttle->Requests++;
        $throttle->LastRequest = time();

        return ($throttle->write()) ? true : false;
    }

    /**
     * Pauses script execution until another request can be made. Open to opinions or a PR?
     *
     * @note This is only used by server, and "should not" at any point in time be invoked by a visitor
     *
     * @return void
     */
    public function runThrottle() {
        $throttle = $this->getThrottle();

        $i = 0;
        while (($throttle->Requests >= self::$max_requests) && ((time() - $throttle->LastRequest) <= self::$max_requests_in_duration))
        {
            $i++;
        }

        return;
    }

    /**
     * @param string $subURL
     * @param string $method
     * @param null   $data
     * @param null   $headers
     * @param array  $curlOptions
     *
     * @return \RestfulService_Response
     */
    public function request($subURL = '', $method = "GET", $data = null, $headers = null, $curlOptions = array()) {

        $this->runThrottle();

        $params = parse_str($this->queryString);

        if (is_null($params)) {
            $params = array();
        }

        if (!array_key_exists("api_key", $params)) {
            $params['api_key'] = static::$api_key;
            $this->setQueryString($params);
        }

        $result = parent::request($subURL, $method, $data, $headers, $curlOptions);

        $this->incThrottle();

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