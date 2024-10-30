<?php

namespace ILJ\Helper;

/**
 * Regex Custom Field Rule
 *
 * This class represents a single regex rule used in both custom field for post and custom fields for term
 * option
 *
 * @package ILJ\Helper
 * @since   2.23.5
 */
class Regex_Custom_Field
{
    private const STARTS_WITH_PREFIX = 'starts_with:';
    private const ENDS_WITH_PREFIX = 'ends_with:';
    private const CONTAINS_PREFIX = 'contains:';
    /**
     * This denotes the type of the rule.
     *
     * @var string Indicates the type of rule, possible values are {@link STARTS_WITH_PREFIX},
     * {@link ENDS_WITH_PREFIX}, {@link CONTAINS_PREFIX}
     */
    private $type;
    /**
     * The name of the regex field rule.
     *
     * @var string Name of the post meta field.
     */
    private $value;
    private function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }
    /**
     * Returns starts with label for displaying on ui, also used by javascript
     *
     * @return string
     */
    public static function get_starts_with_label_template()
    {
        return __('Custom field name starts with \'%s\'', 'internal-links');
    }
    /**
     * Returns ends with label for displaying on ui, also used by javascript
     *
     * @return string
     */
    public static function get_ends_with_label_template()
    {
        return __('Custom field name ends with \'%s\'', 'internal-links');
    }
    /**
     * Returns contains label for displaying on ui, also used by javascript
     *
     * @return string
     */
    public static function get_contains_label_template()
    {
        return __('Custom field name contains \'%s\'', 'internal-links');
    }
    /**
     * Return a label for the regex field, this is for displaying it on ui.
     *
     * @return string
     */
    public function get_label()
    {
        switch ($this->type) {
            case self::STARTS_WITH_PREFIX:
                return sprintf(self::get_starts_with_label_template(), $this->value);
            case self::ENDS_WITH_PREFIX:
                return sprintf(self::get_ends_with_label_template(), $this->value);
            case self::CONTAINS_PREFIX:
                return sprintf(self::get_contains_label_template(), $this->value);
        }
        return '';
    }
    /**
     * Returns to the format which can be stored in option, for example
     * starts_with:foo
     *
     * @return string
     */
    public function to_option_value()
    {
        return $this->type . $this->value;
    }
    /**
     * Determines if the regex field data is valid. It is recommended to invoke this method before
     * trying to construct the instance using any builder methods.
     *
     * @param string $item
     *
     * @return bool
     */
    public static function is_valid_rule($item)
    {
        return strpos($item, self::STARTS_WITH_PREFIX) === 0 || strpos($item, self::ENDS_WITH_PREFIX) === 0 || strpos($item, self::CONTAINS_PREFIX) === 0;
    }
    /**
     * Generates a {@link Regex_Custom_Field} instance from option, Its recommended to invoke {@link Regex_Custom_Field::is_valid_rule()}
     * before calling this function.
     *
     * @param string $option_value
     *
     * @return Regex_Custom_Field
     */
    public static function from($option_value)
    {
        $data = explode(':', $option_value);
        return new Regex_Custom_Field($data[0] . ':', $data[1]);
    }
    /**
     * Method to determine if the given meta key matches the rule.
     *
     * @param string $meta_key
     *
     * @return boolean
     */
    public function meta_key_matches_rule($meta_key)
    {
        switch ($this->type) {
            case self::STARTS_WITH_PREFIX:
                return strpos($meta_key, $this->value) === 0;
            case self::ENDS_WITH_PREFIX:
                return substr($meta_key, -strlen($this->value)) === $this->value;
            case self::CONTAINS_PREFIX:
                return strpos($meta_key, $this->value) !== false;
            default:
                return false;
        }
    }
    /**
     * Return the sql like clause value for the regex custom field, for example %apple, apple%, %apple%
     * based on the type.
     *
     * @return string
     */
    public function get_escaped_sql_like_clause()
    {
        global $wpdb;
        if (self::STARTS_WITH_PREFIX === $this->type) {
            return $wpdb->esc_like($this->value) . '%';
        }
        if (self::ENDS_WITH_PREFIX === $this->type) {
            return '%' . $wpdb->esc_like($this->value);
        }
        if (self::CONTAINS_PREFIX === $this->type) {
            return '%' . $wpdb->esc_like($this->value) . '%';
        }
        return '';
    }
}