<?php

/**
 * Class TMDB_Settings
 *
 * This may be removed in favor for a YML config
 */
class TMDB_Settings extends DataExtension
{
    private static $db = array(
        'tmdb_api_key' => "Varchar"
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            "Root.TMDB",
            array(
                TextField::create("tmdb_api_key", "Your TMDB API Key")
                ->setAttribute("placeholder", "XXXXXXXXXXXXXXXXXXXXXXXXXXX")
            )
        );

        parent::updateCMSFields($fields);
    }
}