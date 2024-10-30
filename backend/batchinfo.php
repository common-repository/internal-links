<?php

namespace ILJ\Backend;

use ILJ\Core\Options;
/**
 * Handles Plugin Batch Information
 *
 * @package ILJ\Backend
 * @since   2.0.0
 */
class BatchInfo
{
    /**
     * Batch info instance
     *
     * @var   BatchInfo
     * @since 2.0.0
     */
    private static $instance;
    /**
     * Batch data
     *
     * @var   array
     * @since 2.0.0
     */
    private $batch_data;
    protected function __construct()
    {
        $batch_data_default = array('batch_build' => array('last_update' => array('batch_count' => '', 'batch_finished' => '', 'last_update' => '', 'status' => '')));
        $batch_data = Options::getOption(Options::ILJ_OPTION_KEY_BATCH);
        $this->batch_data = wp_parse_args($batch_data, $batch_data_default);
    }
    /**
     * Get data from batch data
     *
     * @since  2.0.0
     * @param  string $key The key
     * @return string|bool
     */
    public static function get($key)
    {
        self::init();
        $batch_data = self::$instance->batch_data;
        if (array_key_exists($key, $batch_data)) {
            return $batch_data[$key];
        }
        return false;
    }
    /**
     * Update batch data
     *
     * @since  2.0.0
     * @param  string $key   The key
     * @param  mixed  $value The value
     * @return bool
     */
    public static function update($key, $value)
    {
        self::init();
        $batch_data = self::$instance->batch_data;
        $batch_data[$key] = $value;
        Options::setOption(Options::ILJ_OPTION_KEY_BATCH, $batch_data);
        return true;
    }
    /**
     * Init Environment- class
     *
     * @since  2.0.0
     * @return void
     */
    private static function init()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
    }
}