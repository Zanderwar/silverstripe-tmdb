<?php
class Movies extends DataObject {

    private static $db = array(
        "Adult" => "Int(0)",
        "BackdropPath" => "Varchar",
        "BelongsToCollection" => "Varchar",
        "Budget" => "Int",
        "Homepage" => "Varchar",
        "TMDBId" => "Int",
        "IMDBId" => "Int",
        "OriginalLanguage" => "Varchar(3)",
        "OriginalTitle" => "Varchar",
        "Overview" => "Text",
        "Popularity" => "Decimal(2,6)",
        "PosterPath" => "Varchar",
        "ProductionCountries" => "Text", // json
        "ReleaseDate" => "Date",
        "Revenue" => "Currency",
        "Runtime" => "Int",
        "Language" => "Text", // json
        "Status" => "Varchar",
        "Tagline" => "Varchar",
        "Title" => "Varchar",
        "Video" => "Varchar",
        "VoteAverage" => "Decimal(2,2)",
        "VoteCount" => "Int"
    );

    /**
     * A movie has one or more genre
     *
     * @var array
     */
    private static $belongs_many_many = array(
        "Genres" => "Genre",
        "ProductionCompanies" => "ProductionCompany"
    );


}