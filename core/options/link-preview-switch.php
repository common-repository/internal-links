<?php

namespace ILJ\Core\Options;

use ILJ\Core\IndexBuilder;
use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Link Summary Card Switch
 *
 * @package ILJ\Core\Options
 * @since   2.24.4
 */
class Link_Preview_Switch extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'link_preview_switch';
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
        return __('Link Preview', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Enabling this allows users to see post/term previews for generated links.', 'internal-links');
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