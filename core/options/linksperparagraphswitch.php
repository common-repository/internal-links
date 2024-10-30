<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Help;
use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Links Per Paragraph Switch
 *
 * @package ILJ\Core\Options
 * @since   1.2.19
 */
class LinksPerParagraphSwitch extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'links_per_paragraph_switch';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return false;
    }
    /**
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Limit links per paragraph', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Limit the links created per paragraph', 'internal-links');
    }
    /**
     * Identifies if the current option is pro only
     *
     * @return bool
     */
    public static function isPro()
    {
        return true;
    }
    /**
     * Outputs the options form element for backend administration
     *
     * @param  mixed $value
     * @return mixed
     */
    public function renderField($value)
    {
        $value = false;
        OptionsHelper::renderToggle($this, $value);
    }
    /**
     * Checks if a value is a valid value for option
     *
     * @param  mixed $value The value that gets validated
     * @return bool
     */
    public function isValidValue($value)
    {
        return 1 === (int) $value || 0 === (int) $value;
    }
}