<?php

/**
 * Movie Model
 *
 * When movies are originally synced, most of the elements below are not included in the initial payload we receive
 * from TheMoveDB.org and it proves beneficial in the way we handle it.
 *
 * Thankfully the payload does include what a visitor needs to initially interact with most predominately "Title",
 * "PosterPath", "Popularity", "Overview", "VoteAverage", "VoteCount" and "OriginalLanguage" which allows the end-user
 * to search and easily navigate through the movies and then when they want more details about the movie they simply
 * navigate to it pull the rest of the data in, this data can be stored for as long as you define in the admin settings
 * and fresh data will be obtained after that period.
 *
 * This ensures that the database is kept as minimal as possible and the only movies that have synced entirely, are
 * movies that your users wanted more details about.
 */
class Movie extends DataObject
{

    private static $db = array(
        "Adult"               => "Int(0)",
        "BackdropPath"        => "Varchar",
        "BelongsToCollection" => "Varchar",
        "Budget"              => "Int",
        "Homepage"            => "Varchar",
        "MovieId"             => "Int", // used in further requests to TheMovieDB.org
        "IMDBId"              => "Int",
        "OriginalLanguage"    => "Varchar(3)",
        "OriginalTitle"       => "Varchar",
        "Overview"            => "Text",
        "Popularity"          => "Decimal",
        "PosterPath"          => "Varchar",
        "ProductionCountries" => "Text", // json
        "ReleaseDate"         => "Date",
        "Revenue"             => "Currency",
        "Runtime"             => "Int",
        "Language"            => "Text", // json
        "Status"              => "Varchar",
        "Tagline"             => "Varchar",
        "Title"               => "Varchar",
        "Video"               => "Varchar",
        "VoteAverage"         => "Decimal",
        "VoteCount"           => "Int",
        "LastFullSync"        => "Date"
    );

    /**
     * A movie has one or more genre
     *
     * @var array
     */
    private static $belongs_many_many = array(
        "ProductionCompanies" => "ProductionCompany",
        "Genres"              => "Genre"
    );

}