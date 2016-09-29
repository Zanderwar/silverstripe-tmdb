<?php

class ProductionCompanies extends DataObject {
    private static $db = array(
        "CompanyId" => "Int",
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