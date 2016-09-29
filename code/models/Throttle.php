<?php

/**
 * Class Throttle
 *
 * TheMovieDB.org API will only allow a maximum of 40 requests per 10 seconds.
 *
 * This seems a little intensive and I'd like to be able to do it another way,
 * but better than risking being blacklisted
 *
 * @see https://www.themoviedb.org/faq/api
 */

class Throttle extends DataObject {
    private static $db = array(
        "Requests" => "Int",
        "LastRequest" => "Int"
    );
}