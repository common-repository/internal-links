<?php

namespace ILJ\Database;

use ILJ\Enumeration\LinkType;
/**
 * Database wrapper for the linkindex table
 *
 * @package ILJ\Database
 * @since   1.0.0
 */
class Linkindex
{
    const ILJ_DATABASE_TABLE_LINKINDEX = 'ilj_linkindex';
    const ILJ_ACTION_AFTER_DELETE_LINKINDEX = 'ilj_after_delete_linkindex';
    /**
     * Cleans the whole index table
     *
     * @since  1.0.0
     * @return void
     */
    public static function flush()
    {
        global $wpdb;
        $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX);
    }
    /**
     * Handles the delete function for link_to
     *
     * @param  int    $id
     * @param  string $type
     * @return void
     */
    public static function delete_link_to($id, $type)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX . '  WHERE link_to = %d AND type_to = %s', $id, $type));
    }
    /**
     * Handles the delete function for link_from
     *
     * @param  int    $id
     * @param  string $type
     * @return void
     */
    public static function delete_link_from($id, $type)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX . ' WHERE link_from = %d AND type_from = %s', $id, $type));
    }
    public static function delete_for_individual_builds()
    {
        global $wpdb;
        $addition_query = '';
        $outgoing = $wpdb->prepare('SELECT DISTINCT link_from, type_from FROM ' . $wpdb->prefix . LinkindexIndividualTemp::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP . ' WHERE link_type = %s ' . $addition_query, LinkType::OUTGOING);
        $outgoing_ids = $wpdb->get_results($outgoing, ARRAY_A);
        if (is_array($outgoing_ids) && !empty($outgoing_ids)) {
            foreach ($outgoing_ids as $value) {
                self::delete_link_from($value['link_from'], $value['type_from']);
            }
        }
        $incoming = $wpdb->prepare('SELECT DISTINCT link_to, type_to FROM ' . $wpdb->prefix . LinkindexIndividualTemp::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP . ' WHERE link_type = %s ' . $addition_query, LinkType::INCOMING);
        $incoming_ids = $wpdb->get_results($incoming, ARRAY_A);
        if (is_array($incoming_ids) && !empty($incoming_ids)) {
            foreach ($incoming_ids as $value) {
                self::delete_link_to($value['link_to'], $value['type_to']);
            }
        }
        self::delete_individual_build_initialization_values();
    }
    /**
     * Deletes the individual build initialization values so it does not get included in the linkindex importfromtemp function
     *
     * @return void
     */
    public static function delete_individual_build_initialization_values()
    {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . LinkindexIndividualTemp::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP . "'") == $wpdb->prefix . LinkindexIndividualTemp::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP) {
            // delete initialization from outgoing build
            $wpdb->query('DELETE FROM ' . $wpdb->prefix . LinkindexIndividualTemp::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP . " WHERE link_to = 0 AND type_to = '' AND anchor = '' AND link_type = 'outgoing'");
            // delete initialization from incoming build
            $wpdb->query('DELETE FROM ' . $wpdb->prefix . LinkindexIndividualTemp::ILJ_DATABASE_TABLE_LINKINDEX_INDIVIDUAL_TEMP . " WHERE link_from = 0 AND type_from = '' AND anchor = '' AND link_type = 'incoming'");
        }
    }
    /**
     * Handles the delete function for deleting links by ID
     *
     * @param  int    $id
     * @param  string $type
     * @return void
     */
    public static function delete_link_by_id($id, $type)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX . ' WHERE (link_from = %d AND type_from = %s) OR (link_to = %d AND type_to = %s)', $id, $type, $id, $type));
        /**
         * Fires linkindex is deleted
         *
         * @since 1.2.23
         */
        do_action(self::ILJ_ACTION_AFTER_DELETE_LINKINDEX);
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
        $query = $wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX . ' linkindex WHERE linkindex.link_from = %d AND linkindex.type_from = %s', $id, $type);
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
     * @return void
     */
    public static function addRule($link_from, $link_to, $anchor, $type_from, $type_to)
    {
        if (!is_integer((int) $link_from) || !is_integer((int) $link_to) || !is_string((string) $anchor)) {
            return;
        }
        global $wpdb;
        $wpdb->insert($wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX, array('link_from' => $link_from, 'link_to' => $link_to, 'anchor' => $anchor, 'type_from' => $type_from, 'type_to' => $type_to), array('%d', '%d', '%s', '%s', '%s'));
    }
    /**
     * Aggregates and counts entries for a given column
     *
     * @since  1.0.0
     * @param  string $column The column in the linkindex table
     * @return array
     */
    public static function getGroupedCount($column)
    {
        $allowed_columns = array('link_from', 'link_to', 'anchor');
        if (!in_array($column, $allowed_columns)) {
            return null;
        }
        $type_mapping = array('link_from' => 'type_from', 'link_to' => 'type_to');
        $type = in_array($column, array_keys($type_mapping)) ? ', ' . $type_mapping[$column] . ' AS type ' : '';
        global $wpdb;
        $query = sprintf('SELECT  %1$s, COUNT(*) AS elements%2$s FROM %3$s linkindex GROUP BY %1$s ORDER BY elements DESC', $column, $type, $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX);
        return $wpdb->get_results($query);
    }
    /**
     * Returns all link data grouped by in- and outlink countings
     *
     * @since  1.1.0
     * @param  string $order  The column in the linkindex table
     * @param  int    $limit  Count of selected results
     * @param  int    $offset Offset of selected results
     * @return array
     */
    public static function getGroupedCountFull($order, $limit, $offset)
    {
        $allowed_orders = array('elements_from', 'elements_to');
        if (!in_array($order, $allowed_orders)) {
            $order = 'elements_from';
        }
        $limit = ($limit > 0) ? sprintf(' LIMIT %1$d OFFSET %2$d', $limit, $offset) : '';
        global $wpdb;
        $query = sprintf('
            SELECT asset_id, elements_from, elements_to, asset_type
            FROM(
                (
                    SELECT link_from AS asset_id, COUNT(*) AS elements_from, type_from AS asset_type
                    FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX . ' GROUP BY asset_id,asset_type) outlinks
                    LEFT JOIN
                    (
                        SELECT link_to AS asset_id_, COUNT(*) AS elements_to, type_to AS asset_type_
                        FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX . ' GROUP BY asset_id_,asset_type_
                	) inlinks
                    ON
                    (outlinks.asset_id = inlinks.asset_id_)
                    AND
                    (outlinks.asset_type = inlinks.asset_type_)
            	)
                UNION
                SELECT asset_id, elements_from, elements_to, asset_type
                FROM(
                    (SELECT link_from AS asset_id_, COUNT(*) AS elements_from, type_from AS asset_type_ FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX . ' a GROUP BY asset_id_,asset_type_) outlinks
                    RIGHT JOIN
                    (SELECT link_to AS asset_id, COUNT(*) AS elements_to, type_to AS asset_type FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX . ' GROUP BY asset_id,asset_type) inlinks
                    ON
                    (outlinks.asset_id_ = inlinks.asset_id)
                    AND
                    (outlinks.asset_type_ = inlinks.asset_type)
                )
            ORDER BY %1$s DESC' . $limit, $order);
        return $wpdb->get_results($query);
    }
    /**
     * Returns all links, pointing to or from a single resource
     *
     * @since  1.2.5
     * @param  int    $id
     * @param  string $type
     * @param  string $direction
     * @return array
     */
    public static function getDirectiveLinks($id, $type, $direction)
    {
        global $wpdb;
        if (!is_numeric($id)) {
            return;
        }
        $select = 'SELECT * FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX . ' linkindex';
        if ('from' == $direction) {
            $query = $wpdb->prepare($select . ' WHERE linkindex.link_from = %d AND linkindex.type_from = %s', $id, $type);
        } elseif ('to' == $direction) {
            $query = $wpdb->prepare($select . ' WHERE linkindex.link_to = %d AND linkindex.type_to = %s', $id, $type);
        } else {
            return null;
        }
        return $wpdb->get_results($query);
    }
    /**
     * Returns all anchor texts with their frequency
     *
     * @since  1.1.0
     * @param  array $request Data of the requested form contains limit, orders, filters indices
     * @return array
     */
    public static function get_anchor_count_full($request)
    {
        global $wpdb;
        $table = $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX;
        $bindings = array();
        $columns = array(array('db' => 'anchor', 'dt' => 0), array('db' => 'frequency', 'dt' => 3));
        $limit = self::ilj_grid_limit($request);
        $order = self::ilj_grid_order($request, $columns);
        $where = self::ilj_grid_filter($request, $columns, $bindings);
        $where = ('' != $where) ? " WHERE {$where} " : $where;
        $sql = "SELECT *, count(anchor) as frequency FROM `{$table}` {$where} GROUP BY anchor  {$order} {$limit}";
        $prepared_query = empty($bindings) ? $sql : $wpdb->prepare($sql, $bindings);
        $data = $wpdb->get_results($prepared_query);
        $sql1 = "SELECT COUNT(`id`) FROM `{$table}` {$where} GROUP BY anchor ";
        $prepared_query1 = empty($bindings) ? $sql1 : $wpdb->prepare($sql1, $bindings);
        $res_filter_length = $wpdb->get_results($prepared_query1);
        $records_filtered = count($res_filter_length);
        $res_total_length = $wpdb->get_results("SELECT COUNT(`id`) FROM `{$table}` GROUP BY anchor");
        $records_total = count($res_total_length);
        return array("draw" => isset($request['draw']) ? intval($request['draw']) : 0, "recordsTotal" => intval($records_total), "recordsFiltered" => intval($records_filtered), "data" => $data);
    }
    /**
     * Paging
     *
     * Construct the LIMIT clause for server-side processing SQL query
     *
     * @param array $request Data sent to server by DataTables
     * @return string SQL limit clause
     */
    public static function ilj_grid_limit($request)
    {
        $limit = '';
        if (isset($request['start']) && -1 != $request['length']) {
            $limit = "LIMIT " . intval($request['start']) . ", " . intval($request['length']);
        }
        return $limit;
    }
    /**
     * Ordering
     *
     * Construct the ORDER BY clause for server-side processing SQL query
     *
     * @param array $request Data sent to server by DataTables
     * @param array $columns Column information array
     * @return string SQL order by clause
     */
    private static function ilj_grid_order($request, $columns)
    {
        $order = '';
        if (isset($request['order']) && count($request['order'])) {
            $order_by = array();
            $dt_columns = self::ilj_grid_pluck($columns, 'dt');
            for ($i = 0, $ien = count($request['order']); $i < $ien; $i++) {
                $column_idx = intval($request['order'][$i]['column']);
                $request_column = $request['columns'][$column_idx];
                if ('true' == $request_column['orderable']) {
                    $column = $columns[array_search($request_column['data'], $dt_columns)];
                    $dir = ('asc' == $request['order'][$i]['dir']) ? 'ASC' : 'DESC';
                    $order_by[] = '`' . $column['db'] . '` ' . $dir;
                }
            }
            $order = 'ORDER BY ' . implode(', ', $order_by);
        }
        return $order;
    }
    /**
     * Searching / Filtering
     *
     * Construct the WHERE clause for server-side processing SQL query.
     *
     * @param array $request  Data sent to server by DataTables
     * @param array $columns  Column information array
     * @param array $bindings Array of values for PDO bindings, used to prevent SQL injection
     * @return string SQL where clause
     */
    public static function ilj_grid_filter($request, $columns, &$bindings)
    {
        $global_search = array();
        $column_search = array();
        $dt_columns = self::ilj_grid_pluck($columns, 'dt');
        if (isset($request['search']) && '' != $request['search']['value']) {
            $str = $request['search']['value'];
            for ($i = 0, $ien = count($request['columns']); $i < $ien; $i++) {
                $request_column = $request['columns'][$i];
                $column_idx = array_search($request_column['data'], $dt_columns);
                $column = $columns[$column_idx];
                if ('true' == $request_column['searchable']) {
                    $binding = self::ilj_grid_bind($bindings, '%' . $str . '%', '%s');
                    $global_search[] = "`" . $column['db'] . "` LIKE " . $binding;
                }
            }
        }
        // Individual column filtering
        for ($i = 0, $ien = count($request['columns']); $i < $ien; $i++) {
            $request_column = $request['columns'][$i];
            $column_idx = array_search($request_column['data'], $dt_columns);
            $column = $columns[$column_idx];
            $str = $request_column['search']['value'];
            if ('true' == $request_column['searchable'] && '' != $str) {
                $binding = self::ilj_grid_bind($bindings, '%' . $str . '%', '%s');
                $column_search[] = "`" . $column['db'] . "` LIKE " . $binding;
            }
        }
        // Combine the filters into a single string
        $where = '';
        if (count($global_search)) {
            $where = '(' . implode(' OR ', $global_search) . ')';
        }
        if (count($column_search)) {
            $where = ('' == $where) ? implode(' AND ', $column_search) : ($where . ' AND ' . implode(' AND ', $column_search));
        }
        return $where;
    }
    /**
     * Return a string from an array or a string
     *
     * @param  array  $a    Array to get the value from
     * @param string $prop Property name to return
     * @return array Array of property values
     */
    private static function ilj_grid_pluck($a, $prop)
    {
        $out = array();
        for ($i = 0, $len = count($a); $i < $len; $i++) {
            $out[] = $a[$i][$prop];
        }
        return $out;
    }
    /**
     * Create a PDO binding key which can be used for escaping variables safely when executing a query
     *
     * @param  array  $a   value of bindings
     * @param  string $val Value to bind
     * @return string Bound key to be used in the SQL where this parameter would be used
     */
    private static function ilj_grid_bind(&$a, $val)
    {
        $a[] = $val;
        return '%s';
    }
    /**
     * Count inbound/outbound entries for a given index type
     *
     * @param  string $column The column in the linkindex table
     * @param  string $type   The index type
     * @param  int    $id     The ID of the element we want to count rows for
     * @return int
     */
    public static function get_grouped_count_for_type($column, $type, $id)
    {
        global $wpdb;
        $allowed_columns = array('link_from', 'link_to');
        if (!in_array($column, $allowed_columns)) {
            throw new Exception(esc_html(sprintf("Invalid column name %s", $column)));
        }
        $allowed_types = array('post', 'term', 'custom');
        if (!in_array($type, $allowed_types)) {
            throw new Exception(esc_html(sprintf("Invalid element type %s", $type)));
        }
        $type_mapping = array('link_from' => 'type_from', 'link_to' => 'type_to');
        $type_field = $type_mapping[$column];
        $query = $wpdb->prepare('SELECT %1$s, COUNT(1) FROM %2$s linkindex WHERE linkindex.%3$s = %4$s AND %5$s = "%6$s" GROUP BY %7$s', $column, $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX, $column, $id, $type_field, $type, $column);
        return (int) $wpdb->get_var($query, 1);
    }
    /**
     * Delete link index entries by type
     *
     * @param  String $type
     * @return void
     */
    public static function delete_links_by_type($type)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX . ' WHERE type_from = %s OR type_to = %s', $type, $type));
    }
}