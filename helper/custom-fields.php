<?php

namespace ILJ\Helper;

/**
 * Custom Fields
 *
 * This class represents a collection of custom field names, this is an abstract representation
 * which will be used in filters.
 *
 * @package ILJ\Helper
 * @since   2.23.5
 */
class Custom_Fields
{
    /**
     * A list of custom field names.
     *
     * @var array<string>
     */
    private $fields;
    /**
     *  Boolean to represent if the custom fields have a regex field.
     *
     * @var boolean $has_regex_custom_field
     */
    private $has_regex_custom_field;
    /**
     * Constructor for CustomFields
     *
     * @param array<string> $fields                 A list of custom field names.
     * @param boolean       $has_regex_custom_field Boolean to represent if the custom fields have a regex field.
     */
    private function __construct($fields, $has_regex_custom_field)
    {
        $this->fields = $fields;
        $this->has_regex_custom_field = $has_regex_custom_field;
    }
    /**
     * Returns true if it has custom field.
     *
     * @return bool
     */
    public function has_regex_custom_field()
    {
        return $this->has_regex_custom_field;
    }
    /**
     * Construct the instance from a list of custom field names.
     *
     * @param array<string> $fields A list of custom field names.
     *
     * @return Custom_Fields
     */
    public static function from_custom_field_names($fields)
    {
        foreach ($fields as $field) {
            if (Regex_Custom_Field::is_valid_rule($field)) {
                return new Custom_Fields($fields, true);
            }
        }
        return new Custom_Fields($fields, false);
    }
    /**
     * Expands the custom fields based on the post context, this is only needed if it has regex
     * custom fields.
     *
     * @param int $post_id The post id.
     *
     * @return array
     */
    public function expand_post_custom_fields($post_id)
    {
        if (!$this->has_regex_custom_field()) {
            return $this->fields;
        }
        $result = array();
        $regex_fields = array();
        foreach ($this->fields as $field) {
            if (!Regex_Custom_Field::is_valid_rule($field)) {
                $result[] = $field;
            } else {
                $regex_fields[] = Regex_Custom_Field::from($field);
            }
        }
        return array_merge($result, $this->expand_regex_post_custom_fields($regex_fields, $post_id));
    }
    /**
     * Expands the custom fields based on the term context, this is only needed if it has regex
     * custom fields.
     *
     * @param int $term_id The term id.
     *
     * @return array
     */
    public function expand_term_custom_fields($term_id)
    {
        if (!$this->has_regex_custom_field()) {
            return $this->fields;
        }
        $result = array();
        $regex_fields = array();
        foreach ($this->fields as $field) {
            if (!Regex_Custom_Field::is_valid_rule($field)) {
                $result[] = $field;
            } else {
                $regex_fields[] = Regex_Custom_Field::from($field);
            }
        }
        return array_merge($result, $this->expand_regex_term_custom_fields($regex_fields, $term_id));
    }
    /**
     * Applies the regex rules and expands the custom post fields.
     *
     * @param array<Regex_Custom_Field> $regex_fields
     * @param int                       $post_id
     *
     * @return array
     */
    private function expand_regex_post_custom_fields($regex_fields, $post_id)
    {
        global $wpdb;
        $query = "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE post_id=%d AND (";
        $query .= join('OR', array_fill(0, count($regex_fields), ' meta_key LIKE %s '));
        $names = array_map(function ($field) {
            return $field->get_escaped_sql_like_clause();
        }, $regex_fields);
        $query .= ' )';
        $data = $wpdb->get_results($wpdb->prepare($query, array_merge(array($post_id), $names)), ARRAY_N);
        return array_map(function ($item) {
            return $item[0];
        }, $data);
    }
    /**
     * Applies the regex rules and expands the custom term fields.
     *
     * @param array<Regex_Custom_Field> $regex_fields
     * @param int                       $term_id
     *
     * @return array
     */
    private function expand_regex_term_custom_fields($regex_fields, $term_id)
    {
        global $wpdb;
        $query = "SELECT DISTINCT meta_key FROM {$wpdb->termmeta} WHERE term_id=%d AND (";
        $query .= join('OR', array_fill(0, count($regex_fields), ' meta_key LIKE %s '));
        $names = array_map(function ($field) {
            return $field->get_escaped_sql_like_clause();
        }, $regex_fields);
        $query .= ' )';
        $data = $wpdb->get_results($wpdb->prepare($query, array_merge(array($term_id), $names)), ARRAY_N);
        return array_map(function ($item) {
            return $item[0];
        }, $data);
    }
}