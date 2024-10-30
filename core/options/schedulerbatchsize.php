<?php

namespace ILJ\Core\Options;

use ActionScheduler;
use ILJ\Helper\Help;
use ILJ\Enumeration\ActionSchedulerOptions;
use ILJ\Core\Options;
/**
 * Option: Size of the work batch for action scheduler plugin
 *
 * @package ILJ\Core\Options
 */
class SchedulerBatchSize extends AbstractOption
{
    /**
     * Adds the option to an option group
     *
     * @param  string $option_group The option group to which the option gets connected
     * @return void
     */
    public function register($option_group)
    {
        register_setting($option_group, static::getKey(), array('type' => 'string', 'sanitize_callback' => array($this, 'isValidValue')));
    }
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'scheduler_batch_size';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return ActionSchedulerOptions::DEFAULT;
    }
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
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Action Scheduler Batch Size', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Configure according to your environment CPU and RAM availability', 'internal-links');
    }
    /**
     * Returns a hint text for the option, if given
     *
     * @return string
     */
    public function getHint()
    {
        return '<ul class="description">' . '<li><p class="description"><code>' . __('Small value', 'internal-links') . '</code>: ' . __('For resource-limited servers, you should set a value close to 1, so the plugin uses less CPU and memory.', 'internal-links') . ' ' . sprintf(__('Minimum value is %s', 'internal-links'), ActionSchedulerOptions::MINIMAL) . '.</p></li>' . '<li><p class="description"><code>' . __('Large value', 'internal-links') . '</code>: ' . __('If you have a lot of RAM and CPU, you can set a value like 100.', 'internal-links') . ' ' . sprintf(__('Maximum value is %s', 'internal-links'), ActionSchedulerOptions::MAXIMAL) . '.</p></li>' . '</ul>';
    }
    /**
     * Outputs the options form element for backend administration
     *
     * @param  mixed $value
     * @return mixed
     */
    public function renderField($value)
    {
        if (null == $value) {
            $value = self::getDefault();
        }
        echo '<input type="number" min="' . esc_attr(ActionSchedulerOptions::MINIMAL) . '" max="' . esc_attr(ActionSchedulerOptions::MAXIMAL) . '" name="' . esc_attr(self::getKey()) . '" id="' . esc_attr(self::getKey()) . '" value="' . esc_attr($value) . '">';
    }
    /**
     * Checks if a value is a valid value for option
     *
     * @param  mixed $value The value that gets validated
     * @return bool
     */
    public function isValidValue($value)
    {
        $old_value = Options::getOption(self::getKey());
        $has_errors = false;
        if ($value < ActionSchedulerOptions::MINIMAL || $value > ActionSchedulerOptions::MAXIMAL) {
            add_settings_error('internal-links', 'internal-links-error', sprintf(__('%s value must be a number between %s and %s', 'internal-links'), $this->getTitle(), ActionSchedulerOptions::MINIMAL, ActionSchedulerOptions::MAXIMAL), 'error');
            $has_errors = true;
        }
        if ($has_errors) {
            $value = $old_value;
        }
        return $value;
    }
    /**
     * Fetch the batch size from the saved options, this method is called once via add_filter()
     * Uses max() and min() to make sure the value is always within valid boundaries
     *
     * @return int
     */
    public static function set_scheduler_batch_size()
    {
        return min(ActionSchedulerOptions::MAXIMAL, max(ActionSchedulerOptions::MINIMAL, (int) Options::getOption(self::getKey())));
    }
}