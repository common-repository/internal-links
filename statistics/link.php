<?php

namespace ILJ\Statistics;

use ILJ\Data\Content;
use ILJ\Database\Linkindex;
/**
 * Gets Link statistics
 *
 * Gets the link statistics for the dashboard
 *
 * @package ILJ\Statistics
 * @since   2.23.5
 */
class Link
{
    /**
     * Arguments for statistics query.
     *
     * @var array $args {
     * @type int             $limit                  The number of rows which needs to be returned.
     * @type int             $offset                 The number of rows which needs to be offset (used for pagination).
     * @type string          $sort_by                The field which needs to be used for sorting, one of 'title', 'keywords_count', 'incoming_links', 'outgoing_links'
     * @type string          $sort_direction         The sort direction, it can be ASC or DESC
     * @type string          $search                 The search query (optional)
     * @type array           $main_types             The main types filter (optional)
     * @type array           $sub_types              The sub types filter (optional)
     * }
     */
    private $args;
    /**
     * Constructor for {@link Link} class
     *
     * @param array $args {
     * @type int             $limit                  The number of rows which needs to be returned.
     * @type int             $offset                 The number of rows which needs to be offset (used for pagination).
     * @type string          $sort_by                The field which needs to be used for sorting, one of 'title', 'keywords_count', 'incoming_links', 'outgoing_links'
     * @type string          $sort_direction         The sort direction, it can be ASC or DESC
     * @type string          $search                 The search query (optional)
     * @type array           $main_types             The main types filter (optional)
     * @type array           $sub_types              The sub types filter (optional)
     * }
     */
    public function __construct($args)
    {
        $this->args = wp_parse_args($args, array('sort_by' => 'title', 'sort_direction' => 'ASC', 'limit' => 10, 'offset' => 0, 'search' => '', 'types' => array('post', 'term'), 'main_types' => array(), 'sub_types' => array()));
    }
    private function should_apply_main_types_filter()
    {
        return !empty($this->args['main_types']);
    }
    private function should_apply_sub_types_filter()
    {
        return !empty($this->args['sub_types']);
    }
    private function get_sql_escaped_main_types()
    {
        return sprintf("'%s'", implode("','", array_map('esc_sql', $this->args['main_types'])));
    }
    private function get_sql_escaped_sub_types()
    {
        return sprintf("'%s'", implode("','", array_map('esc_sql', $this->args['sub_types'])));
    }
    /**
     * Get sort_by after validation.
     *
     * @return string
     */
    private function get_sort_by()
    {
        $allowed_sorting_columns = array('title', 'keywords_count', 'incoming_links', 'outgoing_links');
        return in_array($this->args['sort_by'], $allowed_sorting_columns, true) ? $this->args['sort_by'] : 'title';
    }
    /**
     * Get sort direction after validation.
     *
     * @return string
     */
    private function get_sort_direction()
    {
        $allowed_sorting_directions = array('ASC', 'DESC');
        return in_array($this->args['sort_direction'], $allowed_sorting_directions, true) ? $this->args['sort_direction'] : 'ASC';
    }
    /**
     * Return the query for sub_type, since the link statistics is now paginated, these types
     * needs to be available in the query for filtering, the equivalent function is
     * {@link \ILJ\Helper\IndexAsset::getDetailedType}
     *
     * @return string
     */
    private static function get_sub_type_query()
    {
        $sub_type_query = "\n\t\t    CASE\n\t\t        WHEN idx.type = 'post' THEN items.entity_type\n\t\t        ELSE ''\n\t\t    END AS sub_type";
        return $sub_type_query;
    }
    /**
     * Return a map of main_type and sub_type which will be used for filtering in the
     * link statistics ui screen.
     *
     * @return array
     */
    public static function get_types()
    {
        global $wpdb;
        $link_index_table_name = $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX;
        $sub_type_query = self::get_sub_type_query();
        $term_query = "";
        $type_condition_query = "WHERE idx.type = items.type AND (idx.type != CONCAT(items.type, '_meta') OR items.entity_type != 'ilj_customlinks' OR items.entity_type != 'term')";
        $query = "\n\t\t\tSELECT\n\t\t\t    idx.type AS main_type,\n\t\t\t    {$sub_type_query}\n\t\t\tFROM\n\t\t\t    (\n\t\t\t        SELECT\n\t\t\t            p.ID AS id,\n\t\t\t            'post' AS type,\n\t\t\t            p.post_type as entity_type\n\t\t\t        FROM\n\t\t\t            {$wpdb->posts} p\n\t\t\t        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'ilj_linkdefinition'\n\t\t\t\t\tWHERE p.post_status = 'publish'\n\t\t\t    \t{$term_query}\n\t\t\t    ) items\n\t\t\t\n\t\t\tRIGHT JOIN (\n\t\t\t        SELECT DISTINCT link_from as id, type_from AS type FROM {$link_index_table_name}\n\t\t\t\t\tUNION\n\t\t\t\t\tSELECT DISTINCT link_to AS id, type_to AS type FROM {$link_index_table_name}\n\t\t\t) AS idx ON items.id = idx.id\n\t\t\t{$type_condition_query}\n\t\t\tGROUP BY main_type, sub_type\n\t\t\t";
        return $wpdb->get_results($query);
    }
    /**
     * Returns the statistics for linkindex table.
     *
     * @return array
     */
    public function get_statistics()
    {
        global $wpdb;
        $link_index_table_name = $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX;
        $term_query = "";
        $type_condition_query = "AND idx.type = items.type";
        $query = "\n\t\t\tSELECT * FROM (\n\t\t\t\t\tSELECT\n\t\t\t\t\t    items.id, idx.type AS main_type, items.type, items.keywords_count, items.title,\n\t\t\t\t\t    COALESCE(incoming_links.count, 0) AS incoming_links,\n\t\t\t\t\t    COALESCE(outgoing_links.count, 0) AS outgoing_links,\n\t\t\t\t\t    {$this->get_sub_type_query()}\n\t\t\t\t\tFROM\n\t\t\t\t\t    (\n\t\t\t\t\t        SELECT\n\t\t\t\t\t            p.ID AS id,\n\t\t\t\t\t            p.post_title AS title,\n\t\t\t\t\t            'post' AS type,\n\t\t\t\t\t            COALESCE(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value, 'a:', -1), ':', 1) AS SIGNED), 0) AS keywords_count,\n\t\t\t\t\t            p.post_type as entity_type\n\t\t\t\t\t        FROM\n\t\t\t\t\t            {$wpdb->posts} p\n\t\t\t\t\t        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'ilj_linkdefinition'\n\t\t\t\t\t\t\tWHERE p.post_status = 'publish'\n\t\t\t\t\t       {$term_query}\n\t\t\t\t\t    ) items\n\t\t\t\t\t\n\t\t\t\t\tLEFT JOIN (\n\t\t\t\t\t        SELECT DISTINCT link_from AS id, type_from AS type FROM {$link_index_table_name}\n\t\t\t\t\t\t\tUNION\n\t\t\t\t\t\t\tSELECT DISTINCT link_to AS id, type_to AS type FROM {$link_index_table_name}\n\t\t\t\t\t) AS idx ON items.id = idx.id {$type_condition_query}\n\t\t\t\t\t\n\t\t\t\t\tLEFT JOIN (\n\t\t\t\t\t    SELECT link_to AS id, type_to AS TYPE, COUNT(1) AS count\n\t\t\t\t\t    FROM {$link_index_table_name}\n\t\t\t\t\t    GROUP BY link_to, type_to\n\t\t\t\t\t) AS incoming_links ON items.id = incoming_links.id AND idx.type = incoming_links.type\n\t\t\t\t\t\n\t\t\t\t\tLEFT JOIN (\n\t\t\t\t\t    SELECT link_from AS id, type_from AS TYPE, COUNT(1) AS count\n\t\t\t\t\t    FROM {$link_index_table_name}\n\t\t\t\t\t    GROUP BY link_from, type_from\n\t\t\t\t\t) AS outgoing_links ON items.id = outgoing_links.id AND idx.type = outgoing_links.type\n\t\t\t) AS results WHERE 1=1\n";
        if (!empty($this->args['search'])) {
            $query .= " AND title LIKE %s";
        }
        if ($this->should_apply_main_types_filter() && $this->should_apply_sub_types_filter()) {
            $query .= " AND (main_type IN ({$this->get_sql_escaped_main_types()}) AND sub_type IN ({$this->get_sql_escaped_sub_types()}) )";
        }
        $query .= "\tORDER BY {$this->get_sort_by()} {$this->get_sort_direction()} LIMIT %d OFFSET %d;";
        if (!empty($this->args['search'])) {
            $prepared_query = $wpdb->prepare($query, (!empty($this->args['search'])) ? '%' . $wpdb->esc_like($this->args['search']) . '%' : '', $this->args['limit'], $this->args['offset']);
        } else {
            $prepared_query = $wpdb->prepare($query, $this->args['limit'], $this->args['offset']);
        }
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
        return array_map(function ($result) {
            $content = Content::from_content_type_and_id($result['type'], $result['sub_type'], $result['id']);
            $result['edit_link'] = $content->get_edit_link();
            $result['edit_title'] = $content->get_edit_title();
            $result['permalink'] = $content->get_permalink();
            $result['permalink_title'] = $content->get_permalink_title();
            return $result;
        }, $results);
    }
    /**
     * Return the total number of filtered rows.
     *
     * @return int
     */
    public function get_filtered_results_count()
    {
        global $wpdb;
        $link_index_table_name = $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX;
        $query = "\n\t\t\tSELECT COUNT(1) FROM (\n\t\t\tSELECT\n\t\t\tidx.type as main_type,\n\t\t\titems.title,\n\t\t\t{$this->get_sub_type_query()}\n\t\t\tFROM\n\t\t\t    (\n\t\t\t        SELECT\n\t\t\t            p.ID AS id,\n\t\t\t            'post' AS type,\n\t\t\t            p.post_type as entity_type,\n\t\t\t            p.post_title AS title\n\t\t\t        FROM\n\t\t\t            {$wpdb->posts} p\n\t\t\t        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'ilj_linkdefinition'\n\t\t\t\t\tWHERE p.post_status = 'publish'\n\t\t\t        UNION\n\t\t\t\n\t\t\t        SELECT\n\t\t\t            t.term_id AS id,\n\t\t\t            'term' AS type,\n\t\t\t            tt.taxonomy as entity_type,\n\t\t\t            t.name as title\n\t\t\t        FROM\n\t\t\t            {$wpdb->terms} t\n\t\t\t        LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id\n\t\t\t    ) items\n\t\t\t\n\t\t\tRIGHT JOIN (\n\t\t\t        SELECT\n\t\t\t\t        link_from, type_from AS type\n\t\t\t\t    FROM\n\t\t\t\t        {$link_index_table_name}\n\t\t\t\t    GROUP BY\n\t\t\t\t        link_from,\n\t\t\t\t        type_from\n\t\t\t) AS idx ON items.id = idx.link_from  AND (idx.type = items.type OR idx.type = CONCAT(items.type, '_meta'))\n\t\t\t\n\t\t\tUNION\n\n\t\t\tSELECT\n\t\t\tidx.type as main_type,\n\t\t\titems.title,\n\t\t\t{$this->get_sub_type_query()}\n\t\t\tFROM\n\t\t\t    (\n\t\t\t        SELECT\n\t\t\t            p.ID AS id,\n\t\t\t            'post' AS type,\n\t\t\t            p.post_type as entity_type,\n\t\t\t            p.post_title AS title\n\t\t\t        FROM\n\t\t\t            {$wpdb->posts} p\n\t\t\t        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'ilj_linkdefinition'\n\t\t\t\t\tWHERE p.post_status = 'publish'\n\t\t\t        UNION\n\t\t\t\n\t\t\t        SELECT\n\t\t\t            t.term_id AS id,\n\t\t\t            'term' AS type,\n\t\t\t            tt.taxonomy as entity_type,\n\t\t\t            t.name as title\n\t\t\t        FROM\n\t\t\t            {$wpdb->terms} t\n\t\t\t        LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id\n\t\t\t    ) items\n\t\t\t\n\t\t\tRIGHT JOIN (\n\t\t\t        SELECT\n\t\t\t\t        link_to, type_to AS type\n\t\t\t\t    FROM\n\t\t\t\t        {$link_index_table_name}\n\t\t\t\t    GROUP BY\n\t\t\t\t        link_to,\n\t\t\t\t        type_to\n\t\t\t) AS idx ON items.id = idx.link_to  AND (idx.type = items.type OR idx.type = CONCAT(items.type, '_meta'))) AS results WHERE 1=1";
        if (!empty($this->args['search'])) {
            $query .= " AND title LIKE %s";
        }
        if ($this->should_apply_main_types_filter() && $this->should_apply_sub_types_filter()) {
            $query .= " AND (main_type IN ({$this->get_sql_escaped_main_types()}) AND sub_type IN ({$this->get_sql_escaped_sub_types()}) )";
        }
        if (!empty($this->args['search'])) {
            return intval($wpdb->get_var($wpdb->prepare($query, '%' . $wpdb->esc_like($this->args['search']) . '%')));
        } else {
            return intval($wpdb->get_var($query));
        }
    }
    /**
     * Return the total number of rows in link statistics.
     *
     * @return int
     */
    public function get_total()
    {
        global $wpdb;
        $link_index_table_name = $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX;
        $count_query = "\n\t\t\tSELECT COUNT(DISTINCT id, type) \n\t\t\tFROM (\n\t\t\t\tSELECT\n\t\t\t\t\titems.id,\n\t\t\t\t\tCASE \n\t\t\t\t\t\tWHEN idx.type = CONCAT(items.type, '_meta') THEN CONCAT(items.type, '_meta') \n\t\t\t\t\t\tELSE items.type\n\t\t\t\t\tEND AS type\n\t\t\t\tFROM\n\t\t\t\t(\n\t\t\t\t\tSELECT\n\t\t\t\t\t\tp.ID AS id,\n\t\t\t\t\t\t'post' AS type\n\t\t\t\t\tFROM\n\t\t\t\t\t\t{$wpdb->posts} p\n\t\t\t\t\tWHERE p.post_status = 'publish'\n\t\t\t\t\tUNION\n\t\t\t\t\tSELECT\n\t\t\t\t\t\tt.term_id AS id,\n\t\t\t\t\t\t'term' AS type\n\t\t\t\t\tFROM\n\t\t\t\t\t\t{$wpdb->terms} t\n\t\t\t\t) items\n\t\t\t\tRIGHT JOIN (\n\t\t\t\t\tSELECT\n\t\t\t\t\t\tlink_from AS id, \n\t\t\t\t\t\ttype_from AS type\n\t\t\t\t\tFROM\n\t\t\t\t\t\t{$link_index_table_name}\n\t\t\t\t\tGROUP BY\n\t\t\t\t\t\tlink_from, type_from\n\t\t\t\t) AS idx \n\t\t\t\tON items.id = idx.id \n\t\t\t\tAND (idx.type = items.type OR idx.type = CONCAT(items.type, '_meta'))\n\n\t\t\t\tUNION\n\n\t\t\t\tSELECT\n\t\t\t\t\titems.id,\n\t\t\t\t\tCASE \n\t\t\t\t\t\tWHEN idx.type = CONCAT(items.type, '_meta') THEN CONCAT(items.type, '_meta') \n\t\t\t\t\t\tELSE items.type\n\t\t\t\t\tEND AS type\n\t\t\t\tFROM\n\t\t\t\t(\n\t\t\t\t\tSELECT\n\t\t\t\t\t\tp.ID AS id,\n\t\t\t\t\t\t'post' AS type\n\t\t\t\t\tFROM\n\t\t\t\t\t\t{$wpdb->posts} p\n\t\t\t\t\tWHERE p.post_status = 'publish'\n\t\t\t\t\tUNION\n\t\t\t\t\tSELECT\n\t\t\t\t\t\tt.term_id AS id,\n\t\t\t\t\t\t'term' AS type\n\t\t\t\t\tFROM\n\t\t\t\t\t\t{$wpdb->terms} t\n\t\t\t\t) items\n\t\t\t\tRIGHT JOIN (\n\t\t\t\t\tSELECT\n\t\t\t\t\t\tlink_to AS id, \n\t\t\t\t\t\ttype_to AS type\n\t\t\t\t\tFROM\n\t\t\t\t\t\t{$link_index_table_name}\n\t\t\t\t\tGROUP BY\n\t\t\t\t\t\tlink_to, type_to\n\t\t\t\t) AS idx \n\t\t\t\tON items.id = idx.id \n\t\t\t\tAND (idx.type = items.type OR idx.type = CONCAT(items.type, '_meta')  OR idx.type = 'custom')\n\t\t\t) AS combined_results;\n\n\t\t";
        return intval($wpdb->get_var($count_query));
    }
}