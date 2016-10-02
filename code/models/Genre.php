<?php

class Genre extends DataObject {
    private static $db = array(
        "GenreId" => "Int", // used in further requests to TheMovieDB.org
        "Name" => "Varchar",
        "LastFullSync" => "Date"
    );

    private static $many_many = array(
        "Movies" => "Movie"
    );
}