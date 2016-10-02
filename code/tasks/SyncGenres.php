<?php

/**
 * Background Task: Sync Genres
 */
class SyncGenres extends BuildTask
{

    /**
     * @var string
     */
    protected $title = "TMDB: Sync Genres";

    /**
     * @var string
     */
    protected $description = "Updates the database with a fresh list of genres. Should only be needed to run once or anytime the locale changes, or should a new genre ever exist.";

    /**
     * @var bool
     */
    protected $enabled = TRUE;

    /**
     * @param $request
     */
    public function run($request)
    {
        $locale = str_replace("_", "-", i18n::get_locale());

        $response = \TMDB\Genre::inst()->fetch($locale, TRUE);
        // These 2 scenarios should never occur
        if (!is_array($response)) {
            die(i18n::_t("TMDB_API_ERROR.MalformedResponse", "The API response is not an array?"));
        }
        if (!isset($response[ 'genres' ]) || empty($response)) {
            die(i18n::_t("TMDB_API_ERROR.EmptyResponse", "The API response is empty?"));
        }
        ////////////////////////////////////////

        $updated = 0;
        $new     = 0;

        foreach ($response[ 'genres' ] as $genre) {

            $record = Genre::get()
                ->filter(
                    array(
                        "GenreId" => $genre['id']
                    )
                )
                ->first();

            if (!$record) {
                $new++;
                $record = Genre::create();
                $record->GenreId = $genre['id'];
            }
            else {
                $updated++;
            }

            $record->Name = $genre['name'];
            $record->write();
        }

        echo i18n::_t(
            "TMDB_TASKS.SyncGenresResult",
            "Updated: {updated} | New: {new}",
            "This is the response given when the SyncGenres task has completed",
            array(
                'updated' => $updated,
                'new' => $new
            )
        );
    }
}