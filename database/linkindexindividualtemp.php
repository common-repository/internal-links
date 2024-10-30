<?php

namespace ILJ\Database;

use ILJ\Enumeration\LinkType;
/**
 * Database wrapper for the linkindex table for individual index builds
 *
 * @package ILJ\Database
 * @since   2.1.0
 */
class LinkindexIndividualTemp
{
    const ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP = 'ilj_linkindex_individual_temp';
    public static function install_temp_db()
    {
        global $wpdb;
        $charset_collate = DatabaseCollation::get_collation(true);
        $query_linkindex = 'CREATE TABLE ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP . ' (
			`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
			`link_from` BIGINT(20) NULL,
			`link_to` BIGINT(20) NULL,
			`type_from` VARCHAR(45) NULL,
			`type_to` VARCHAR(45) NULL,
			`anchor` TEXT NULL,
			`link_type` VARCHAR(45) NULL,
			PRIMARY KEY (`id`),
			INDEX `link_from` (`link_from` ASC),
			INDEX `type_from` (`type_from` ASC),
			INDEX `type_to` (`type_to` ASC),
			INDEX `link_to` (`link_to` ASC),
			INDEX `link_type` (`link_type` ASC)) ' . $charset_collate . ';';
        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($query_linkindex);
    }
    /**
     * Drops the current temporary table
     *
     * @return void
     */
    public static function uninstall_temp_db()
    {
        global $wpdb;
        $query_linkindex = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP . ';';
        $wpdb->query($query_linkindex);
    }
    /**
     * Cleans the whole index table
     *
     * @since  1.3.10
     * @return void
     */
    public static function flush()
    {
        global $wpdb;
        $row = $wpdb->get_var("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = '" . $wpdb->dbname . "' AND table_name = '" . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP . "'");
        if (1 == $row) {
            $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP);
        }
    }
    /**
     * Returns all post outlinks from linkindex table
     *
     * @since  1.0.1
     * @param  int    $id   The post ID where outlinks should be retrieved
     * @param  string $type
     * @return array
     */
    public static function getRules($id, $type)
    {
        if (!is_numeric($id)) {
            return array();
        }
        global $wpdb;
        $query = $wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP . ' linkindex WHERE linkindex.link_from = %d AND linkindex.type_from = %s', $id, $type);
        return $wpdb->get_results($query);
    }
    /**
     * Adds a post rule to the linkindex table
     *
     * @since  1.0.1
     * @param  int    $link_from Post ID which gives the link
     * @param  int    $link_to   Post ID where the link should point to
     * @param  string $anchor    The anchor text which gets used for linking
     * @param  string $type_from The type of asset which gives the link
     * @param  string $type_to   The type of asset which receives the link
     * @param  string $link_type The type of linkbuild it was built on(incoming/outgoing), to determine the delete method
     * @return void
     */
    public static function addRule($link_from, $link_to, $anchor, $type_from, $type_to, $link_type)
    {
        if (!is_integer((int) $link_from) || !is_integer((int) $link_to) || !is_string((string) $anchor)) {
            return;
        }
        global $wpdb;
        $wpdb->insert($wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP, array('link_from' => $link_from, 'link_to' => $link_to, 'anchor' => $anchor, 'type_from' => $type_from, 'type_to' => $type_to, 'link_type' => $link_type), array('%d', '%d', '%s', '%s', '%s', '%s'));
    }
    /**
     * Imports the newly created link index from temp table to main linkindex table
     *
     * @return void
     */
    public static function importIndexFromTemp()
    {
        global $wpdb;
        Linkindex::delete_for_individual_builds();
        $query = 'INSERT INTO ' . $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX . ' (link_from,link_to,anchor,type_from,type_to) 
			SELECT link_from,link_to,anchor,type_from,type_to FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP . ' AS source_table WHERE NOT EXISTS (
				SELECT 1 FROM ' . $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX . ' as target_table 
				WHERE target_table.link_from = source_table.link_from AND target_table.link_to = source_table.link_to AND target_table.anchor = source_table.anchor
				AND target_table.type_from = source_table.type_from AND target_table.type_to = source_table.type_to
			)';
        $wpdb->query($query);
        self::uninstall_temp_db();
    }
    /**
     * Checking if Rule already exists, mainly used now on checking if Individual build initial values are added,
     * to log if an individual build was initialize and correctly execute delete_for_individual_builds and importIndexFromTemp.
     *
     * @param  mixed $link_from
     * @param  mixed $link_to
     * @param  mixed $anchor
     * @param  mixed $type_from
     * @param  mixed $type_to
     * @param  mixed $link_type
     * @return void
     */
    public static function check_exists($link_from, $link_to, $anchor, $type_from, $type_to, $link_type)
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP . ' WHERE link_from = %d AND link_to = %d AND anchor = %s AND type_from = %s AND type_to = %s AND link_type = %s', $link_from, $link_to, $anchor, $type_from, $type_to, $link_type));
        if ($row) {
            return true;
        } else {
            return false;
        }
    }
}