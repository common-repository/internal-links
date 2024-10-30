<?php

namespace ILJ\Database;

/**
 * Database wrapper for the database collation tool fix.
 *
 * @package ILJ\Database
 * @since   2.24.4
 */
class DatabaseCollation
{
    const ILJ_DEFAULT_COLLATION = 'utf8mb4_unicode_ci';
    const ILJ_DEFAULT_CHARSET_COLLATE = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    /**
     * Get Collation to update to
     *
     * @param  bool $for_linkindex This parameter determines if the function is used for database creation or not.
     * @return string
     */
    public static function get_collation($for_linkindex = false)
    {
        global $wpdb;
        $collation = self::ILJ_DEFAULT_COLLATION;
        if (defined('DB_COLLATE')) {
            if (!empty(DB_COLLATE) || DB_COLLATE != '') {
                if ($for_linkindex) {
                    $charset = (defined('DB_CHARSET') && DB_CHARSET) ? DB_CHARSET : 'utf8mb4';
                    $collation = "DEFAULT CHARACTER SET " . $charset . " COLLATE " . DB_COLLATE;
                    return $collation;
                }
                $collation = DB_COLLATE;
                return $collation;
            }
        }
        if ($wpdb->has_cap('collation')) {
            $charset_collation = $wpdb->get_charset_collate();
            if (!empty($charset_collation)) {
                if ($for_linkindex) {
                    return $charset_collation;
                }
                $collation = self::extract_collation($charset_collation);
                return $collation;
            } else {
                if ($for_linkindex) {
                    $collation = self::ILJ_DEFAULT_CHARSET_COLLATE;
                    return $collation;
                }
                $collation = self::ILJ_DEFAULT_COLLATION;
                return $collation;
            }
        }
        return $collation;
    }
    /**
     * Split the string to extract the collation
     *
     * @param  string $charset_collate
     * @return string
     */
    private static function extract_collation($charset_collate)
    {
        // Example: 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        if (preg_match('/ COLLATE ([a-zA-Z0-9._-]+)/i', $charset_collate, $matches)) {
            $collation = $matches[1];
        } else {
            $collation = null;
            // Collation not found
        }
        return $collation;
    }
    /**
     * Modify the database collation
     *
     * @return void
     */
    public static function modify_collation()
    {
        global $wpdb;
        $collation = self::get_collation();
        $sql = "SHOW TABLES LIKE '{$wpdb->prefix}ilj_%'";
        $res = $wpdb->get_col($sql);
        if (null !== $res) {
            foreach ($res as $table) {
                self::modify_table($table, $collation);
            }
        }
    }
    /**
     * Modify the collation per database table
     *
     * @param  string $table          Name of the table to alter.
     * @param  string $collation_term Collation term to update to.
     * @return void
     */
    private static function modify_table($table, $collation_term)
    {
        global $wpdb;
        $sql = "SHOW CREATE TABLE `{$table}`";
        $create_table_res = $wpdb->get_row($sql, ARRAY_A);
        $create_table = $create_table_res['Create Table'];
        // determine current collation value
        $old_coll = '';
        preg_match('/ COLLATE=([a-zA-Z0-9._-]+)/i', $create_table, $collate_match);
        if (false !== $collate_match[1] && "" != $collate_match[1]) {
            $old_coll = $collate_match[1];
        }
        // check table collation and modify if it's an undesired algorithm
        if ($old_coll != $collation_term) {
            $sql = "ALTER TABLE `{$table}` COLLATE={$collation_term}";
            $wpdb->query($sql);
        }
        $sql = "SHOW FULL COLUMNS FROM `{$table}`";
        $columns_res = $wpdb->get_results($sql, ARRAY_A);
        if (null !== $columns_res) {
            foreach ($columns_res as $row) {
                // Skip non text fields.
                if (false === stripos($row['Type'], 'text') && false === stripos($row['Type'], 'char') && false === stripos($row['Type'], 'enum')) {
                    continue;
                }
                if ($row['Collation'] != $collation_term) {
                    $null = ('NO' === $row['Null']) ? 'NOT NULL' : 'NULL';
                    $default = (null !== $row['Default']) ? " DEFAULT '{$row['Default']}' " : '';
                    $sql = "ALTER TABLE `{$table}`\n\t\t\t\t\t\tCHANGE `{$row['Field']}` `{$row['Field']}` {$row['Type']} COLLATE {$collation_term} {$null} {$default}";
                    $wpdb->query($sql);
                }
            }
        }
    }
}