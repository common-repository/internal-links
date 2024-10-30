<?php

namespace ILJ\Database;

use ILJ\Core\Options;
use ILJ\Core\Options\Whitelist;
use ILJ\Helper\BatchBuilding;
use ILJ\Helper\BatchInfo;
use ILJ\Helper\Blacklist;
/**
 * Postmeta wrapper for the inlink postmeta
 *
 * @package ILJ\Database
 * @since   1.0.0
 */
class Postmeta
{
    const ILJ_META_KEY_LINKDEFINITION = 'ilj_linkdefinition';
    /**
     * Returns all Linkdefinitions from postmeta table
     *
     * @since  1.0.0
     * @param  string $field Fetch single field value
     * @return array
     */
    public static function getAllLinkDefinitions($field = null)
    {
        global $wpdb;
        $meta_key = self::ILJ_META_KEY_LINKDEFINITION;
        $public_post_types = Options::getOption(\ILJ\Core\Options\Whitelist::getKey());
        if (empty($public_post_types)) {
            return array();
        } else {
            $public_post_types_list = "'" . implode("','", $public_post_types) . "'";
        }
        $fetch_field = '*';
        if (null != $field) {
            $fetch_field = $field;
        }
        $query = "\n\t\t\tSELECT postmeta." . $fetch_field . "\n\t\t\tFROM {$wpdb->postmeta} postmeta\n\t\t\tLEFT JOIN {$wpdb->posts} posts ON postmeta.post_id = posts.ID\n\t\t\tWHERE postmeta.meta_key = '{$meta_key}'\n\t\t\tAND posts.post_status = 'publish'\n\t\t\tAND posts.post_type IN ({$public_post_types_list})\n\t\t";
        return $wpdb->get_results($query);
    }
    /**
     * Returns all Linkdefinitions from postmeta table
     *
     * @since  1.0.0
     * @param  int $offset
     * @return array
     */
    public static function getAllLinkDefinitionsByBatch($offset)
    {
        global $wpdb;
        $meta_key = self::ILJ_META_KEY_LINKDEFINITION;
        $public_post_types = Options::getOption(\ILJ\Core\Options\Whitelist::getKey());
        $batch_building = new BatchBuilding();
        $limit = $batch_building->get_fetch_batch_keyword_size();
        if (empty($public_post_types)) {
            return array();
        } else {
            $public_post_types_list = "'" . implode("','", $public_post_types) . "'";
        }
        $query = "\n\t\t\tSELECT postmeta.*\n\t\t\tFROM {$wpdb->postmeta} postmeta\n\t\t\tLEFT JOIN {$wpdb->posts} posts ON postmeta.post_id = posts.ID\n\t\t\tWHERE postmeta.meta_key = '{$meta_key}'\n\t\t\tAND posts.post_status = 'publish'\n\t\t\tAND posts.post_type IN ({$public_post_types_list}) LIMIT {$offset} , {$limit} \n\t\t";
        return $wpdb->get_results($query);
    }
    /**
     * Returns all Linkdefinitions from specific ID
     *
     * @param  int $id
     * @return array
     */
    public static function getLinkDefinitionsById($id)
    {
        global $wpdb;
        $meta_key = self::ILJ_META_KEY_LINKDEFINITION;
        $public_post_types = Options::getOption(\ILJ\Core\Options\Whitelist::getKey());
        if (empty($public_post_types)) {
            return array();
        } else {
            $public_post_types_list = "'" . implode("','", $public_post_types) . "'";
        }
        $query = "\n\t\t\tSELECT postmeta.*\n\t\t\tFROM {$wpdb->postmeta} postmeta\n\t\t\tLEFT JOIN {$wpdb->posts} posts ON postmeta.post_id = posts.ID\n\t\t\tWHERE postmeta.meta_key = '{$meta_key}'\n\t\t\tAND posts.post_status = 'publish'\n\t\t\tAND posts.post_type IN ({$public_post_types_list}) AND posts.ID = {$id}\n\t\t";
        return $wpdb->get_results($query);
    }
    /**
     * Removes all link definitions from postmeta table
     *
     * @since  1.1.3
     * @return int
     */
    public static function removeAllLinkDefinitions()
    {
        global $wpdb;
        $meta_key = self::ILJ_META_KEY_LINKDEFINITION;
        return $wpdb->delete($wpdb->postmeta, array('meta_key' => $meta_key));
    }
    public static function getLinkDefinitionCount()
    {
        global $wpdb;
        $meta_key = self::ILJ_META_KEY_LINKDEFINITION;
        $public_post_types = Options::getOption(\ILJ\Core\Options\Whitelist::getKey());
        if (empty($public_post_types)) {
            return 0;
        } else {
            $public_post_types_list = "'" . implode("','", $public_post_types) . "'";
        }
        $query = "\n\t\t\tSELECT COUNT(postmeta.meta_id)\n\t\t\tFROM {$wpdb->postmeta} postmeta\n\t\t\tLEFT JOIN {$wpdb->posts} posts ON postmeta.post_id = posts.ID\n\t\t\tWHERE postmeta.meta_key = '{$meta_key}'\n\t\t\tAND posts.post_status = 'publish'\n\t\t\tAND posts.post_type IN ({$public_post_types_list})\n\t\t";
        return $wpdb->get_var($query);
    }
}