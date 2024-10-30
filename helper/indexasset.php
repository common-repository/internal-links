<?php

namespace ILJ\Helper;

use ILJ\Core\Options as CoreOptions;
use ILJ\Posttypes\CustomLinks;
use ILJ\Helper\Blacklist;
use ILJ\Backend\Editor;
use ILJ\Core\App;
use ILJ\Database\Linkindex;
use ILJ\Database\LinkindexIndividualTemp;
use ILJ\Database\LinkindexTemp;
/**
 * Toolset for linkindex assets
 *
 * Methods for handling linkindex data
 *
 * @package ILJ\Helper
 * @since   1.1.0
 */
class IndexAsset
{
    const ILJ_FILTER_INDEX_ASSET = 'ilj_index_asset_title';
    const ILJ_FILTER_MUFFIN_BUILDER_FIELD = 'ilj_index_mb_field';
    const ILJ_FULL_BUILD = 'full';
    const ILJ_INDIVIDUAL_BUILD = 'individual';
    /**
     * Returns all meta data to a specific asset from index
     *
     * @since  1.1.0
     * @param  int    $id   The id of the asset
     * @param  string $type The type of the asset (post, term or custom)
     * @return object
     */
    public static function getMeta($id, $type)
    {
        if ('post' != $type || 'post_meta' == $type) {
            return null;
        }
        if ('post' == $type || 'post_meta' == $type) {
            $post = get_post($id);
            if (!$post) {
                return null;
            }
            $asset_title = $post->post_title;
            $asset_url = get_the_permalink($post->ID);
            $asset_url_edit = get_edit_post_link($post->ID);
        }
        if (!isset($asset_title) || !isset($asset_url)) {
            return null;
        }
        $meta_data = (object) array('title' => $asset_title, 'url' => $asset_url, 'url_edit' => $asset_url_edit);
        /**
         * Filters the index asset
         *
         * @since 1.6.0
         *
         * @param object $meta_data The index asset
         * @param string $type The asset type
         * @param int $id The asset id
         */
        $meta_data = apply_filters(self::ILJ_FILTER_INDEX_ASSET, $meta_data, $type, $id);
        return $meta_data;
    }
    /**
     * Returns all relevant posts for linking
     *
     * @param  mixed $fetch_fields Optional, define the needed fields in some use case
     * @since  1.2.0
     * @return array
     */
    public static function getPosts($fetch_fields = null)
    {
        $whitelist = CoreOptions::getOption(\ILJ\Core\Options\Whitelist::getKey());
        if (!is_array($whitelist) || !count($whitelist)) {
            return array();
        }
        global $wpdb;
        $addition_query = '';
        $blacklisted_posts = Blacklist::getBlacklistedList('post');
        // If fetch_fields is null use default Fields
        $default_fields = array('ID', 'post_content');
        if (null != $fetch_fields) {
            $fields = $fetch_fields;
        } else {
            $fields = $default_fields;
        }
        if (!empty($blacklisted_posts)) {
            $addition_query = ' ID NOT IN (' . self::escape_array($blacklisted_posts) . ') AND ';
        }
        // this separates the $fields with comma
        $fields_placeholder = implode(', ', $fields);
        $post_query = "SELECT {$fields_placeholder} FROM {$wpdb->posts} WHERE" . $addition_query . " post_type IN (" . self::escape_array($whitelist) . ") AND post_status = 'publish' ORDER BY ID DESC ";
        $posts = $wpdb->get_results($post_query, OBJECT);
        return $posts;
    }
    /**
     * Returns relevant post ids for linking
     *
     * @since  2.0.3
     * @param  mixed $building_batch_size
     * @param  mixed $offset
     *
     * @return array
     */
    public static function getPostsBatched($building_batch_size, $offset)
    {
        $whitelist = CoreOptions::getOption(\ILJ\Core\Options\Whitelist::getKey());
        if (!is_array($whitelist) || !count($whitelist)) {
            return array();
        }
        $args = array('posts_per_page' => $building_batch_size, 'post__not_in' => Blacklist::getBlacklistedList('post'), 'post_type' => $whitelist, 'post_status' => array('publish'), 'suppress_filters' => true, 'offset' => $offset, 'orderby' => 'ID', 'order' => 'DESC', 'lang' => 'all', 'fields' => 'ids');
        $query = new \WP_Query($args);
        return $query->posts;
    }
    /**
     * Gets the concrete type of an asset
     *
     * @since 1.2.5
     * @param string $id   ID of asset
     * @param string $type Generic type of asset
     *
     * @return string
     */
    public static function getDetailedType($id, $type)
    {
        if ('post' == $type) {
            $detailed_type = get_post_type($id);
        }
        return $detailed_type;
    }
    /**
     * Get Incoming Links Count
     *
     * @param  int    $id           Post/term ID to count incoming links
     * @param  string $type
     * @param  string $scope
     * @param  int    $exclude_id   Exclude this Post/term ID to count incoming links
     * @param  string $exclude_type
     * @return int
     */
    public static function getIncomingLinksCount($id, $type, $scope, $exclude_id = null, $exclude_type = null)
    {
        global $wpdb;
        if (self::ILJ_FULL_BUILD == $scope) {
            $ilj_linkindex_table = $wpdb->prefix . LinkindexTemp::ILJ_DATABASE_TABLE_LINKINDEX_TEMP;
        } elseif (self::ILJ_INDIVIDUAL_BUILD == $scope) {
            $ilj_linkindex_table = $wpdb->prefix . LinkindexIndividualTemp::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP;
        }
        $query = '';
        if (null != $exclude_id) {
            $ilj_linkindex_table = $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX;
            $query = " AND (link_from != '" . $exclude_id . "' AND type_from != '" . $exclude_type . "')";
            $incoming_links_old = $wpdb->get_var("SELECT count(link_to) FROM {$ilj_linkindex_table} WHERE (link_to = '" . $id . "' AND type_to = '" . $type . "') " . $query);
            $ilj_linkindex_table_new = $wpdb->prefix . LinkindexIndividualTemp::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP;
            $incoming_links_new = $wpdb->get_var("SELECT count(link_to) FROM {$ilj_linkindex_table_new} WHERE (link_from != 0 AND type_from != '') AND ((link_to = '" . $id . "' AND type_to = '" . $type . "'))");
            $incoming_links = (int) $incoming_links_old + (int) $incoming_links_new;
            return (int) $incoming_links;
        }
        $incoming_links = $wpdb->get_var("SELECT count(link_to) FROM {$ilj_linkindex_table} WHERE (link_from != 0 AND type_from != '') AND ((link_to = '" . $id . "' AND type_to = '" . $type . "') " . $query . ')');
        return (int) $incoming_links;
    }
    /**
     * Get Outgoing Links Count
     *
     * @param  int    $id    Post/Tax ID
     * @param  string $type  Type
     * @param  string $scope
     * @return int
     */
    public static function getOutgoingLinksCount($id, $type, $scope)
    {
        global $wpdb;
        if (self::ILJ_FULL_BUILD == $scope) {
            $ilj_linkindex_table = $wpdb->prefix . LinkindexTemp::ILJ_DATABASE_TABLE_LINKINDEX_TEMP;
        } elseif (self::ILJ_INDIVIDUAL_BUILD == $scope) {
            $ilj_linkindex_table = $wpdb->prefix . LinkindexIndividualTemp::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP;
        }
        $additional_query = '';
        $outgoing = $wpdb->get_var("SELECT count(link_from) FROM {$ilj_linkindex_table} WHERE (link_to != 0 AND type_to != '') AND (link_from = '" . $id . "' AND (type_from = '" . $type . "' " . $additional_query . '))');
        return (int) $outgoing;
    }
    /**
     * Get the already linked url count
     *
     * @param  int    $link_to_id
     * @param  int    $id
     * @param  string $type
     * @param  string $scope
     * @return int
     */
    public static function getLinkedUrlsCount($link_to_id, $id, $type, $scope)
    {
        global $wpdb;
        if (self::ILJ_FULL_BUILD == $scope) {
            $ilj_linkindex_table = $wpdb->prefix . LinkindexTemp::ILJ_DATABASE_TABLE_LINKINDEX_TEMP;
        } elseif (self::ILJ_INDIVIDUAL_BUILD == $scope) {
            $ilj_linkindex_table = $wpdb->prefix . LinkindexIndividualTemp::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP;
        }
        $additional_query = '';
        $linked_url_value = array();
        $linked_urls = $wpdb->get_var("SELECT count(link_to) FROM {$ilj_linkindex_table} WHERE (link_from = '" . $id . "' AND link_to = '" . $link_to_id . "' AND (type_from = '" . $type . "' " . $additional_query . '))');
        $linked_url_value[$link_to_id] = (int) $linked_urls;
        return $linked_urls;
    }
    /**
     * Get Linked Anchors
     *
     * @param  int    $id    Post/Tax ID
     * @param  string $type  Type
     * @param  string $scope
     * @return mixed
     */
    public static function getLinkedAnchors($id, $type, $scope)
    {
        global $wpdb;
        if (self::ILJ_FULL_BUILD == $scope) {
            $ilj_linkindex_table = $wpdb->prefix . LinkindexTemp::ILJ_DATABASE_TABLE_LINKINDEX_TEMP;
        } elseif (self::ILJ_INDIVIDUAL_BUILD == $scope) {
            $ilj_linkindex_table = $wpdb->prefix . LinkindexIndividualTemp::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP;
        }
        $additional_query = '';
        $linked_anchors = $wpdb->get_results("SELECT anchor FROM {$ilj_linkindex_table} WHERE (link_to != 0 AND type_to != '' AND anchor != '') AND (link_from = '" . $id . "' AND (type_from = '" . $type . "' " . $additional_query . '))', ARRAY_A);
        $anchors = array();
        foreach ($linked_anchors as $value) {
            $anchors[] = $value['anchor'];
        }
        return $anchors;
    }
    /**
     * Checks if the phrase is included in the blacklist of keywords
     *
     * @param  int    $link_from post/term ID
     * @param  string $phrase    string to check for
     * @param  string $type      could be term/post
     * @return bool
     */
    public static function checkIfBlacklistedKeyword($link_from, $phrase, $type)
    {
        if ('post' == $type || 'post_meta' == $type) {
            $keyword_blacklist = get_post_meta($link_from, Editor::ILJ_META_KEY_BLACKLISTDEFINITION, true);
        }
        if ('term' == $type || 'term_meta' == $type) {
            $keyword_blacklist = get_term_meta($link_from, Editor::ILJ_META_KEY_BLACKLISTDEFINITION, true);
        }
        if (!empty($keyword_blacklist) || false != $keyword_blacklist) {
            $keyword_blacklist = array_slice($keyword_blacklist, 0, 2);
            foreach ($keyword_blacklist as $keyword) {
                if (strtolower($phrase) == strtolower($keyword)) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * This function removes post/term metas that starts with _ or ilj_
     *
     * @param  array $allmeta Array of all meta fields
     * @return array $custom_fields Array of custom metas
     */
    public static function filter_custom_fields($allmeta)
    {
        $custom_fields = array();
        if ($allmeta) {
            foreach ($allmeta as $value) {
                if (substr($value['meta_key'], 0, 1) != '_' && substr($value['meta_key'], 0, 4) != 'ilj_') {
                    $custom_fields[$value['meta_key']] = $value['meta_value'];
                }
            }
        }
        $custom_fields = array_map('maybe_unserialize', $custom_fields);
        return $custom_fields;
    }
    /**
     * Get the metadata from database
     *
     * @param  int    $id
     * @param  string $type
     * @param  mixed  $key
     * @param  bool   $single
     * @return mixed
     */
    public static function getMetaData($id, $type, $key = null, $single = true)
    {
        global $wpdb;
        if (!is_numeric($id)) {
            return;
        }
        $type_key = 'post_id';
        $table = $wpdb->postmeta;
        if ('term' == $type) {
            $type_key = 'term_id';
            $table = $wpdb->termmeta;
        }
        $query = " AND meta_key = '{$key}' ";
        if (null == $key) {
            $query = '';
        }
        $data = 'meta_value';
        if (!$single) {
            $data = '*';
        }
        $select = 'SELECT ' . $data . ' FROM ' . $table;
        $query = $wpdb->prepare($select . " WHERE {$type_key} = %d " . $query, $id);
        if ($single) {
            $meta_data = $wpdb->get_var($query);
        } elseif (!$single) {
            $meta_data = $wpdb->get_results($query, ARRAY_A);
        }
        return $meta_data;
    }
    /**
     * Escape Array for WPDB Query
     *
     * @param  mixed $arr
     * @return void
     */
    public static function escape_array($arr)
    {
        global $wpdb;
        if (is_array($arr) && !empty($arr)) {
            $escaped = array();
            foreach ($arr as $v) {
                $v = stripslashes($v);
                // Remove extra slashes
                if (is_numeric($v)) {
                    $escaped[] = $wpdb->prepare('%d', $v);
                } else {
                    $escaped[] = $wpdb->prepare('%s', $v);
                }
            }
            return implode(',', $escaped);
        }
        return $arr;
    }
}