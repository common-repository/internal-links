<?php

namespace ILJ\Core\Options;

use ILJ\Core\IndexBuilder;
use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Case Sensitive Mode Switch
 *
 * @package ILJ\Core\Options
 * @since   2.23.5
 */
class Case_Sensitive_Mode_Switch extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'case_sensitive_mode_switch';
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
        return __('Case sensitive mode', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('When this mode is on, keywords will be matched considering their case.', 'internal-links');
    }
    /**
     * Identifies if the current option is pro only
     *
     * @return bool
     */
    public static function isPro()
    {
        return false;
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