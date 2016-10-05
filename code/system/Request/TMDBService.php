<?php
namespace TMDB\Request;

/**
 * Class APIService
 * @package TMDB\Request
 */
class TMDBService extends \RestfulService
{

    /**
     * Holds the JSON decoded response anytime request() is used
     *
     * @var array
     */
    public static $response;

    /**
     * Holds the \RestfulService_Response from request()
     *
     * @var \RestfulService_Response
     */
    public static $restfulResponse;

    /**
     * TheMovieDB.org API Url
     *
     * @var string
     */
    protected static $apiUrl = "http://api.themoviedb.org/3/";

    /**
     * Max Requests
     *
     * @var int
     */
    protected static $maxRequests = 40;

    /**
     * Seconds
     *
     * @var int
     */
    protected static $durationThreshold = 10;

    /**
     * The endpoint (eg "movies", "genres/movies/list")
     *
     * @var string
     */
    protected static $endpoint;

    /**
     * TheMovieDB.org API Key
     *
     * @var string
     */
    private static $apiKey;

    /**
     * @var int
     */
    protected $cacheExpire = 0;

    /**
     * APIService constructor.
     *
     * @param null $cacheExpiry
     */
    function __construct($cacheExpiry = NULL)
    {
        $apiKey = \Config::inst()->get("TMDB", "api_key") ?: getenv("TMDB_API_KEY");

        if (!isset($apiKey) || !strlen($apiKey)) {
            user_error("You must provide your own TheMovieDB.org API key");
        }

        self::$apiKey = $apiKey;
        parent::__construct(self::$apiUrl, $cacheExpiry);
    }

    /**
     * We override request here to inject our API_KEY into the queryString and also to throttle the rate of requests
     *
     * @param string $subURL      See `RestfulService::request()`
     * @param string $method      See `RestfulService::request()`
     * @param null   $data        See `RestfulService::request()`
     * @param null   $headers     See `RestfulService::request()`
     * @param array  $curlOptions See `RestfulService::request()`
     *
     * @return static
     */
    public function request($subURL = '', $method = "GET", $data = NULL, $headers = NULL, $curlOptions = array())
    {

        if (isset(static::$endpoint)) {
            $this->setEndpoint(static::$endpoint);
        }

        // we don't want the throttle to run when Travic-CI is build testing
        if (getenv("IS_TRAVIS") != "YES") {
            $this->runThrottle();
        }// pauses script execution until another request can be made

        // convert parents query string back into an array
        parse_str($this->queryString, $params);

        // if null create the array
        if (is_null($params)) {
            $params = array();
        }

        // if api_key is not in the array, add it
        if (!array_key_exists("api_key", $params)) {
            $params[ 'api_key' ] = self::$apiKey;
            $this->setQueryString($params);
        }

        // fetch a response
        $result = parent::request($subURL, $method, $data, $headers, $curlOptions);

        // increment the throttle counter
        if (getenv("IS_TRAVIS") != "YES") {
            $this->incThrottle();
        }

        // check if the request is unauthorized (usually bad api key)
        if ($result->getStatusCode() == 401) {
            $unauthorized = json_decode($result->getBody());
            throw new \RuntimeException("TheMovieDB.org said: " . $unauthorized->status_message);
        }

        $this->setResponse($result);

        return $this;
    }

    /**
     * Sets the correct baseUrl for the endpoint
     *
     * @param $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $endpoint      = trim(ltrim($endpoint, "/"));
        $this->baseURL = self::$apiUrl . $endpoint;
    }

    /**
     * Pauses script execution until another request can be made. Open to opinions or a PR?
     *
     * Testing on localhost, the request count could not get higher than 5 before resetting back to zero, though that
     * could very well be due to the crappy internet in Australia
     *
     * Yet to test on production
     *
     * @note  This is only used by server, and "should not" at any point in time be invoked by a visitor
     * @label help-wanted
     *
     * @return void
     */
    private function runThrottle()
    {

        // give the little guy something to count
        $i = 0;

        $diff = time() - $this->getThrottle()->FirstRequest;

        // rodeo time...
        while (($this->getThrottle()->Requests >= self::$maxRequests) || $diff >= self::$durationThreshold) {
            $diff = time() - $this->getThrottle()->FirstRequest; // im repeating myself, can't remove it either - looks can be deceiving :(

            if ($diff >= self::$durationThreshold) {
                // the app has been a good boy and has been punished long enough!
                $throttle           = $this->getThrottle();
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
     * Returns the throttle record
     *
     * @return \DataObject
     */
    private function getThrottle()
    {
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
    private function incThrottle()
    {
        $throttle = $this->getThrottle();

        $requests = $throttle->Requests;

        // if this is the first request getting made ($requests == 0) then let our model know
        if (!(int)$requests) {
            $throttle->FirstRequest = time();
        }

        $requests++;

        $throttle->Requests    = $requests;
        $throttle->LastRequest = time();

        return ($throttle->write()) ? TRUE : FALSE;
    }

    /**
     * Sets the response
     *
     * @param \RestfulService_Response $result
     */
    private function setResponse($result)
    {
        static::$restfulResponse = $result;
        static::$response        = json_decode($result->getBody(), TRUE);
    }

    /**
     * Gets the response
     *
     * @return array
     */
    public function getResponse()
    {
        return static::$response;
    }

    /**
     * Do we have a response?
     *
     * @return bool
     */
    public function hasResponse()
    {
        return (isset(static::$response));
    }

}