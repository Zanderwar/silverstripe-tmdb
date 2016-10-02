<?php

/**
 * Class TMDB_Settings
 *
 * This may be removed in favor for a YML config
 */
class TMDB_Settings extends DataExtension
{
    private static $db = array(
        'tmdb_api_key'     => "Varchar",
        'tmdb_sync_adults_only' => "Int(0)",
        'tmdb_display_adults_only' => "Int(0)"
    );

    public function updateCMSFields(FieldList $fields)
    {
        $warning = '<div class="message warning">';
        $warning .= i18n::_t("TMDB_ADMIN.Warning", 'Warning') . ": ";
        $warning .= i18n::_t("TMDB_ADMIN.AdultsOnlySettingForcesResync", 'Modifying the <strong>Sync Adult Videos</strong> setting below will force the entire database to refresh and may take a few hours to complete. Your website should not receive any interruptions during this period, it just has to cycle through all movies TheMovieDB.org has again just to find the Adult Only movies');
        $warning .= "</div>";

        $fields->addFieldsToTab(
            "Root.TMDB",
            array(
                LiteralField::create("TMDB_AdultsOnlyNotice", $warning),
                TextField::create("tmdb_api_key", "Your TMDB API Key")
                    ->setAttribute("placeholder", "XXXXXXXXXXXXXXXXXXXXXXXXXXX"),
                DropdownField::create("tmdb_sync_adults_only", "Sync Adult Movies?", array( 0 => "Not Set", 1 => "No", 2 => "Yes" )),
                DropdownField::create("tmdb_display_adults_only", "Display Adult Movies?", array( 0 => "No", 1 => "Yes" ))
            )
        );

        parent::updateCMSFields($fields);
    }
}