<?php

namespace ILJ\Database;

use ILJ\Backend\Editor;
use ILJ\Helper\Statistic;
/**
 * Database wrapper for the keyword meta
 *
 * @package ILJ\Database
 * @since   2.1.2
 */
class Keywords
{
    const ILJ_KEYWORD_META_KEY = "ilj_linkdefinition";
    /**
     * Handles all functions to reset keywords and ILJ meta data
     *
     * @return void
     */
    public static function reset_all_keywords()
    {
        self::reset_meta_value("postmeta");
    }
    /**
     * Handles resetting of other ILJ meta data
     *
     * @param  mixed $meta_table
     * @return void
     */
    public static function reset_meta_value($meta_table)
    {
        $ilj_meta_keys = array(self::ILJ_KEYWORD_META_KEY, Editor::ILJ_META_KEY_LIMITINCOMINGLINKS, Editor::ILJ_META_KEY_MAXINCOMINGLINKS, Editor::ILJ_META_KEY_BLACKLISTDEFINITION, Editor::ILJ_META_KEY_LIMITLINKSPERPARAGRAPH, Editor::ILJ_META_KEY_LINKSPERPARAGRAPH, Editor::ILJ_META_KEY_LIMITOUTGOINGLINKS, Editor::ILJ_META_KEY_MAXOUTGOINGLINKS);
        global $wpdb;
        $table = $wpdb->prefix . $meta_table;
        $wpdb->query("DELETE FROM {$table} WHERE meta_key IN ('" . implode("','", $ilj_meta_keys) . "')");
        Statistic::count_all_configured_keywords();
    }
}