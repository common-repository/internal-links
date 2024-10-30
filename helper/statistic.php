<?php

namespace ILJ\Helper;

use ILJ\Backend\Editor;
use ILJ\Backend\Environment;
use ILJ\Database\Linkindex;
use ILJ\Database\Postmeta;
use ILJ\Database\Termmeta;
use ILJ\Helper\Stopwatch;
/**
 * Statistics toolset
 *
 * Methods for providing statistics
 *
 * @package ILJ\Helper
 * @since   1.0.0
 */
class Statistic
{
    const ILJ_STATISTIC_CONFIGURED_KEYWORDS_COUNT_OPTION = 'ilj_configured_keywords_count';
    /**
     * Returns the amount of configured keywords
     *
     * @since  1.1.3
     * @return int
     */
    public static function get_all_configured_keywords_count()
    {
        $stored_count = get_option(self::ILJ_STATISTIC_CONFIGURED_KEYWORDS_COUNT_OPTION, null);
        if (is_numeric($stored_count)) {
            return $stored_count;
        } else {
            return self::count_all_configured_keywords();
        }
    }
    /**
     * Count the configured keywords
     *
     * @return int
     */
    public static function count_all_configured_keywords()
    {
        $configured_keywords_count = 0;
        $postmeta = Postmeta::getAllLinkDefinitions('meta_value');
        foreach ($postmeta as $meta) {
            $keywords = maybe_unserialize($meta->meta_value);
            if (is_array($keywords)) {
                $configured_keywords_count += count($keywords);
            }
        }
        update_option(self::ILJ_STATISTIC_CONFIGURED_KEYWORDS_COUNT_OPTION, $configured_keywords_count);
        return $configured_keywords_count;
    }
    /**
     * Returns the count of configured keywords by a given asset type
     *
     * @since 1.2.5
     * @param int    $asset_id   The Id of the asset
     * @param string $asset_type The type of the asset
     *
     * @return int
     */
    public static function getConfiguredKeywordsCountForAsset($asset_id, $asset_type)
    {
        $allowed_asset_types = array('post');
        if (!in_array($asset_type, $allowed_asset_types)) {
            return 0;
        }
        $data = get_post_meta($asset_id, Postmeta::ILJ_META_KEY_LINKDEFINITION);
        $count = 0;
        if (is_array($data) && 0 != count($data) && is_array($data[0])) {
            $count = count($data[0]);
        }
        return $count;
    }
    /**
     * Returns the statistics for links
     *
     * @since  1.1.0
     * @param  int $results Number of results to display
     * @param  int $page    Number of page to display
     * @return array
     */
    public static function getLinkStatistics($results = -1, $page = 0)
    {
        $page = (0 < $page) ? $page : 1;
        $limit = (int) $results;
        $offset = (int) ($page - 1) * $results;
        $links = Linkindex::getGroupedCountFull('elements_to', $limit, $offset);
        return $links;
    }
    /**
     * Returns the statistics for anchor texts
     *
     * @since  1.1.0
     * @param  array $request Data of datatable form to send to server for populating data
     * @return array
     */
    public static function get_anchor_statistics($request)
    {
        $anchors = Linkindex::get_anchor_count_full($request);
        return $anchors;
    }
    /**
     * A configureable wrapper for the aggregation of columns of the linkindex
     *
     * @deprecated
     * @since      1.0.0
     * @param      array $args Configuration of the selection
     * @return     array
     */
    public static function getAggregatedCount($args = array())
    {
        $defaults = array('type' => 'link_from', 'limit' => 10);
        $limit = $defaults["limit"];
        $type = $defaults["type"];
        if (!empty($args)) {
            $args = wp_parse_args($args, $defaults);
            $limit = $args["limit"];
            $type = $args["type"];
        }
        $inlinks = Linkindex::getGroupedCount($type);
        return array_slice($inlinks, 0, $limit);
    }
    /**
     * Get the total count of the link index table
     *
     * @return int
     */
    public static function getLinkIndexCount()
    {
        global $wpdb;
        $ilj_linkindex_table = $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX;
        $index_count = $wpdb->get_var("SELECT count(*) FROM {$ilj_linkindex_table}");
        return (int) $index_count;
    }
    /**
     * Handles the updating of the statistics information
     *
     * @param  mixed $start
     * @return void
     */
    public static function updateStatisticsInfo($start = null)
    {
        if (null == $start) {
            $stopwatch = new Stopwatch();
        } else {
            $stopwatch = new Stopwatch($start);
        }
        $index_count = self::getLinkIndexCount();
        $duration = $stopwatch->duration();
        $feedback = array("last_update" => array("date" => Stopwatch::timestamp(), "entries" => $index_count, "duration" => $duration));
        Environment::update('linkindex', $feedback);
    }
    /**
     * Reset Linkindex info
     *
     * @return void
     */
    public static function reset_statistics_info()
    {
        $default_data = array("last_update" => array("date" => Stopwatch::timestamp(), "entries" => 0, "duration" => 0));
        Environment::update('linkindex', $default_data);
    }
}