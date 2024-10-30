<?php

namespace ILJ\Core\Options;

/**
 * Abstract implementation of OptionInterface
 *
 * Provides some generic defaults for option instances
 *
 * @package ILJ\Core\Options
 *
 * @since 1.1.3
 */
abstract class AbstractOption implements OptionInterface
{
    const ILJ_OPTIONS_PREFIX = 'ilj_settings_field_';
    /**
     * Check if the option is pro
     *
     * @return void
     */
    public static function isPro()
    {
        return false;
    }
    /**
     * Adds the option to an option group
     *
     * @param  string $option_group The option group to which the option gets connected
     * @return void
     */
    public function register($option_group)
    {
        register_setting($option_group, static::getKey());
    }
    /**
     * Get the option's description
     *
     * @return void
     */
    public function getDescription()
    {
        return '';
    }
    /**
     * Get the option's hint
     *
     * @return void
     */
    public function getHint()
    {
        return '';
    }
}