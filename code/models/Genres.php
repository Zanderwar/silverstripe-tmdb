<?php

class Genres extends DataObject {
    private static $db = array(
        "GenreId" => "Int",
        "Name" => "Varchar"
    );

    private static $many_many = array(
        "Movies" => "Movie"
    );
}