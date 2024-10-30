<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Help;
use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Keep existing settings and configured keywords
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class KeepSettings extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'keep_settings';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return true;
    }
    /**
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Keep configured keywords and plugin settings after plugin uninstall', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('If activated, all your configured keywords and your plugin settings will remain saved - if not, everything from Internal Link Juicer gets deleted when you uninstall the plugin.', 'internal-links');
    }
    /**
     * Outputs the options form element for backend administration
     *
     * @param  mixed $value
     * @return mixed
     */
    public function renderField($value)
    {
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