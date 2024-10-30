<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Global setting to enable/disable incoming links limit for a post
 *
 * @package ILJ\Core\Options
 * @since   2.23.5
 */
class Limit_Incoming_Links extends AbstractOption
{
    /**
     * {@inheritDoc}
     */
    public static function isPro()
    {
        return true;
    }
    /**
     * {@inheritDoc}
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'limit_incoming_links';
    }
    /**
     * {@inheritDoc}
     */
    public static function getDefault()
    {
        return false;
    }
    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return __('Limit incoming links', 'internal-links');
    }
    /**
     * Outputs the field to store the maximum incoming links.
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
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return __('This is an option to globally set a limit for all post/pages/terms to have a limit on the number of incoming links each can have.', 'internal-links');
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