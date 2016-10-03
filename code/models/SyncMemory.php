<?php

/**
 * Class SyncMemory
 *
 * Currently only used in case of sync interruption.
 *
 */
class SyncMemory extends DataObject {
    protected static $db = array(
        "Movies" => "Int",
        "Genre" => "Int"
    );
}