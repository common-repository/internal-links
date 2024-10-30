<?php

namespace ILJ\Helper;

/**
 * Content Transient
 *
 * Methods for handling WP transient for content
 *
 * @package ILJ\Helper
 * @since   2.1.2
 */
class ContentTransient
{
    const ILJ_FILTERED_CONTENT_TRANSIENT = 'ilj_filtered_content_';
    const ILJ_TRANSIENT_EXPIRY_TIME = DAY_IN_SECONDS;
    /**
     * Get Transient data
     *
     * @param  int    $id   Post/Term ID
     * @param  string $type Check if Post/Term
     * @return mixed
     */
    public static function get_transient($id, $type)
    {
        if (!in_array($type, array('post', 'term'))) {
            return false;
        }
        $data = get_transient(ContentTransient::ILJ_FILTERED_CONTENT_TRANSIENT . $type . '_' . $id);
        return $data;
    }
    /**
     * Set Transient data
     *
     * @param  int    $id   Post/Term ID
     * @param  string $type Check if Post/Term
     * @param  mixed  $data Data to be stored
     * @return void
     */
    public static function set_transient($id, $type, $data)
    {
        if (!wp_doing_cron() && !is_admin()) {
            if (!in_array($type, array('post', 'term'))) {
                return;
            }
            set_transient(ContentTransient::ILJ_FILTERED_CONTENT_TRANSIENT . $type . '_' . $id, $data, ContentTransient::ILJ_TRANSIENT_EXPIRY_TIME);
        }
    }
    /**
     * Delete Specific transient data
     *
     * @param  int    $id   Post/Term ID
     * @param  string $type Check if Post/Term
     * @return void
     */
    public static function delete_transient($id, $type)
    {
        if (!in_array($type, array('post', 'term'))) {
            return;
        }
        delete_transient(ContentTransient::ILJ_FILTERED_CONTENT_TRANSIENT . $type . "_" . $id);
    }
    /**
     * Delete All ILJ Transient Data
     *
     * @return void
     */
    public static function delete_all_ilj_transient()
    {
        global $wpdb;
        $prefix = '_transient_' . ContentTransient::ILJ_FILTERED_CONTENT_TRANSIENT . '%';
        $sql = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE `option_name` LIKE %s", $prefix);
        $wpdb->query($sql);
    }
}