<?php

namespace ILJ\Backend;

use ILJ\Core\Options;
/**
 * Plugin Environment
 *
 * Singleton, that has information about all environment related data
 *
 * @package ILJ\Backend
 * @since   1.0.0
 */
class Environment
{
    /**
     * Instance
     *
     * @var   Environment
     * @since 1.0.1
     */
    private static $instance;
    /**
     * Environment data
     *
     * @var   array
     * @since 1.0.1
     */
    private $environment_data;
    protected function __construct()
    {
        $environment_data_default = array('last_version' => ILJ_VERSION, 'linkindex' => array('last_update' => array('date' => '', 'entries' => '', 'duration' => '')));
        $environment_data = Options::getOption(Options::ILJ_OPTION_KEY_ENVIRONMENT);
        $this->environment_data = wp_parse_args($environment_data, $environment_data_default);
    }
    /**
     * Get environment data
     *
     * @since  1.0.1
     * @param  string $key The key
     * @return string|bool
     */
    public static function get($key)
    {
        self::init();
        $environment_data = self::$instance->environment_data;
        if (array_key_exists($key, $environment_data)) {
            return $environment_data[$key];
        }
        return false;
    }
    /**
     * Update environment data
     *
     * @since  1.0.1
     * @param  string $key   The key
     * @param  mixed  $value The value
     * @return void
     */
    public static function update($key, $value)
    {
        self::init();
        $environment_data = self::$instance->environment_data;
        $environment_data[$key] = $value;
        Options::setOption(Options::ILJ_OPTION_KEY_ENVIRONMENT, $environment_data);
    }
    /**
     * Init Environment- class
     *
     * @since  1.0.1
     * @return void
     */
    private static function init()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
    }
}