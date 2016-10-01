<?php

class ProductionCompany extends DataObject {
    private static $db = array(
        "CompanyId" => "Int", // used in further requests to TheMovieDB.org
        "Description" => "Text",
        "Headquarters" => "Varchar",
        "Homepage" => "Varchar",
        "LogoPath" => "Varchar",
        "Name" => "Varchar",
    );

    private static $many_many = array(
        "Movies" => "Movie"
    );
}