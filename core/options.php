<?php

namespace ILJ\Core;

use ILJ\Backend\Notices;
use ILJ\Core\Options\AbstractOption;
use ILJ\Core\Options\Case_Sensitive_Mode_Switch;
use ILJ\Core\Options\IndexGeneration;
use ILJ\Core\Options\Link_Preview_Switch;
use ILJ\Core\Options\Link_Preview_Template;
use ILJ\Core\Options\LinkOutputCustom;
use ILJ\Core\Options\LinkOutputInternal;
use ILJ\Core\Options\OptionInterface;
use ILJ\Core\Options\SchedulerBatchSize;
use ILJ\Enumeration\IndexMode;
use ILJ\Helper\Options as OptionsHelper;
/**
 * Options Wrapper
 *
 * Holds all the options, which can be configured by the site administrator
 * as well as system related settings
 *
 * @package ILJ\Core
 * @since   1.0.0
 */
class Options
{
    const KEY = 'ilj_options';
    /**
     * Prefixes
     */
    const ILJ_OPTION_PREFIX_PAGE = 'ilj_settings_section_';
    const ILJ_OPTION_PREFIX_ID = 'ilj_settings_';
    /**
     * Option sections
     */
    const ILJ_OPTION_SECTION_GENERAL = 'general';
    const ILJ_OPTION_SECTION_CONTENT = 'content';
    const ILJ_OPTION_SECTION_LINKS = 'links';
    const ILJ_OPTION_SECTION_ACTIONS = 'actions';
    /**
     * Other (internal) options
     */
    const ILJ_OPTION_KEY_ENVIRONMENT = 'ilj_environment';
    const ILJ_OPTION_KEY_BATCH = 'ilj_batch_info';
    const ILJ_OPTION_KEY_INDEX_NOTIFY = 'ilj_option_index_notify';
    private static $instance;
    /**
     * Sections variable
     *
     * @var   array
     * @since 1.1.3
     */
    private $sections = array();
    /**
     * Option keys
     *
     * @var   array
     * @since 1.1.3
     */
    private $keys = array();
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    public function __construct()
    {
        $this->sections = array(self::ILJ_OPTION_SECTION_GENERAL => array('options' => array(new Options\KeepSettings(), new Options\HideStatusBar(), new Options\SchedulerBatchSize(), new Options\EditorRole(), new Options\IndexGeneration())), self::ILJ_OPTION_SECTION_CONTENT => array('options' => array(new Options\Whitelist(), new Options\TaxonomyWhitelist(), new Options\Blacklist(), new Options\BlacklistChildPages(), new Options\TermBlacklist(), new Options\KeywordOrder(), new Options\LinksPerPage(), new Options\LinksPerParagraphSwitch(), new Options\LinksPerParagraph(), new Options\LinksPerTarget(), new Options\Limit_Incoming_Links(), new Options\Max_Incoming_Links(), new Options\MultipleKeywords(), new Case_Sensitive_Mode_Switch(), new Options\NoLinkTags(), new Options\RespectExistingLinks(), new Options\LimitTaxonomyList(), new Options\CustomFieldsToLinkPost(), new Options\CustomFieldsToLinkTerm())), self::ILJ_OPTION_SECTION_LINKS => array('options' => array(new Options\LinkOutputInternal(), new Options\InternalNofollow(), new Options\LinkOutputCustom(), new Link_Preview_Switch(), new Link_Preview_Template())), self::ILJ_OPTION_SECTION_ACTIONS => array('actions' => array(new Options\CancelAllILJSchedules(), new Options\Fix_Database_Collation()), 'options' => array(new Options\CacheToggleBtnSwitch())));
        return $this;
    }
    public static function init()
    {
        $options = self::getInstance();
        $options->addSettingsSections()->addOptions()->addActions();
    }
    /**
     * Retrieves the internal option value with different defaults
     *
     * @since  1.0.0
     * @param  string $key The option value which should be returned
     * @return mixed
     */
    public static function getOption($key)
    {
        $option_value = get_option($key, new \WP_Error());
        if ($option_value instanceof \WP_Error) {
            $option = self::getInstance()->getOptionByKey($key);
            if ($option instanceof OptionInterface) {
                return $option::getDefault();
            }
            return false;
        }
        return $option_value;
    }
    /**
     * Retrieve section data
     *
     * @param  string $section_title The title of the section
     * @since  1.1.3
     * @return array|null
     */
    public static function getSection($section_title)
    {
        foreach (self::getInstance()->sections as $section => $data) {
            if ($section == $section_title) {
                return $data;
            }
        }
        return null;
    }
    /**
     * Sets the value of a plugin option
     *
     * @since  1.0.0
     * @param  string $key   The option key
     * @param  string $value The option value
     * @return bool
     */
    public static function setOption($key, $value)
    {
        $options = self::getInstance();
        $available_keys = $options->getKeys();
        if (!in_array($key, $available_keys)) {
            return false;
        }
        if (in_array($key, array(self::ILJ_OPTION_KEY_ENVIRONMENT, self::ILJ_OPTION_KEY_INDEX_NOTIFY, self::ILJ_OPTION_KEY_BATCH, Notices::ILJ_DISMISS_ADMIN_WARNING_LITESPEED))) {
            return update_option($key, $value);
        }
        $option = $options->getOptionByKey($key);
        if (!$option instanceof OptionInterface || !$option->isValidValue($value)) {
            return false;
        }
        return update_option($key, $value);
    }
    /**
     * Returns a type of AbstractOption by its key
     *
     * @since 1.2.0
     * @param string $key The key of the option
     *
     * @return AbstractOption|null
     */
    public function getOptionByKey($key)
    {
        foreach ($this->sections as $section) {
            foreach ($section['options'] as $option) {
                if ($option->getKey() == $key) {
                    return $option;
                }
            }
        }
        return null;
    }
    /**
     * Sets the default options
     *
     * @since  1.1.0
     * @return void
     */
    public static function setOptionsDefault()
    {
        $options = self::getInstance();
        $defaults = $options->getDefaults();
        foreach ($defaults as $option => $default) {
            if (is_string($default)) {
                $default = esc_html($default);
            }
            $existent_option = get_option($option, false);
            if (!$existent_option) {
                add_option($option, $default);
            }
            if (IndexGeneration::getKey() == $option && (IndexMode::NONE != get_option($option, false) && IndexMode::AUTOMATIC != get_option($option, false))) {
                update_option($option, $default);
            }
        }
    }
    /**
     * Remove all options of the plugin from db
     *
     * @since  1.1.3
     * @return void
     */
    public static function removeAllOptions()
    {
        $options = self::getInstance();
        foreach ($options->getKeys() as $key) {
            delete_option($key);
        }
        delete_option(self::ILJ_OPTION_KEY_INDEX_NOTIFY);
        delete_option(self::ILJ_OPTION_KEY_ENVIRONMENT);
        delete_option(self::ILJ_OPTION_KEY_BATCH);
    }
    /**
     * Get key value pairs of the default for each option
     *
     * @since  1.1.3
     * @return array
     */
    protected function getDefaults()
    {
        $defaults = array();
        foreach ($this->sections as $section) {
            if (isset($section['options'])) {
                foreach ($section['options'] as $option) {
                    if ($option->isPro()) {
                        continue;
                    }
                    $defaults[$option::getKey()] = $option::getDefault();
                }
            }
        }
        return $defaults;
    }
    /**
     * Returns all option keys
     *
     * @since  1.1.3
     * @return array
     */
    protected function getKeys()
    {
        if (!count($this->keys)) {
            foreach ($this->sections as $section) {
                if (isset($section['options'])) {
                    foreach ($section['options'] as $option) {
                        $this->keys[] = $option->getKey();
                    }
                }
            }
            $this->keys = array_merge($this->keys, array(self::ILJ_OPTION_KEY_ENVIRONMENT, self::ILJ_OPTION_KEY_INDEX_NOTIFY, self::ILJ_OPTION_KEY_BATCH, Notices::ILJ_DISMISS_ADMIN_WARNING_LITESPEED));
            $this->keys = array_unique($this->keys);
        }
        return $this->keys;
    }
    /**
     * Imports an array of option key/value pairs (without prefix)
     *
     * @since 1.2.0
     * @param array $options The options as key/value pairs
     *
     * @return int
     */
    public static function importOptions(array $options)
    {
        $import_count = 0;
        if (empty($options)) {
            return $import_count;
        }
        foreach ($options as $key => $value) {
            $key = AbstractOption::ILJ_OPTIONS_PREFIX . $key;
            $to_sanitize = array(LinkOutputInternal::getKey());
            if (in_array($key, $to_sanitize)) {
                $value = esc_html($value);
            }
            $import = self::setOption($key, $value);
            if ($import) {
                $import_count++;
            }
        }
        return $import_count;
    }
    /**
     * Exports all options and their current values as array (without prefix)
     *
     * @since  1.2.0
     * @return array
     */
    public static function exportOptions()
    {
        $options = self::getInstance();
        $export = array();
        foreach ($options->sections as $section) {
            foreach ($section['options'] as $option) {
                $key = $option->getKey();
                $key_output = substr($key, strlen(AbstractOption::ILJ_OPTIONS_PREFIX));
                $escaped_option_values = array(LinkOutputInternal::getKey());
                $option_output = self::getOption($key);
                if (in_array($key, $escaped_option_values)) {
                    $option_output = htmlspecialchars_decode($option_output);
                }
                if (!$option->isPro()) {
                    $export[$key_output] = $option_output;
                }
            }
        }
        return $export;
    }
    /**
     * Responsible for the registration of settings sections
     *
     * @since  1.0.0
     * @return void
     */
    protected function addSettingsSections()
    {
        $sections = array_merge($this->sections, array(self::ILJ_OPTION_SECTION_GENERAL => array('title' => __('General settings section', 'internal-links'), 'description' => __('All settings related to the use of the plugin.', 'internal-links')), self::ILJ_OPTION_SECTION_CONTENT => array('title' => __('Content settings section', 'internal-links'), 'description' => __('Configure how the plugin should behave regarding the internal linking.', 'internal-links')), self::ILJ_OPTION_SECTION_LINKS => array('title' => __('Links settings section', 'internal-links'), 'description' => __('Setting options for the output of the generated links.', 'internal-links')), self::ILJ_OPTION_SECTION_ACTIONS => array('title' => __('Actions section', 'internal-links'), 'description' => __('Different action buttons related to the use of the plugin', 'internal-links'))));
        foreach ($sections as $section => $section_data) {
            add_settings_section(self::ILJ_OPTION_PREFIX_ID . $section, $section_data['title'], function () use ($section_data) {
                printf('<p class="section-description">%s</p>', esc_html($section_data['description']));
            }, self::ILJ_OPTION_PREFIX_PAGE . $section);
        }
        return $this;
    }
    /**
     * Initiates the options
     *
     * @since  1.1.3
     * @return $this
     */
    protected function addOptions()
    {
        foreach ($this->sections as $section => $section_data) {
            if (isset($section_data['options'])) {
                foreach ($section_data['options'] as $option) {
                    if (!$option instanceof Options\AbstractOption || $option::getKey() == '') {
                        continue;
                    }
                    add_settings_field($option::getKey(), OptionsHelper::getTitle($option), function () use ($option) {
                        OptionsHelper::renderFieldComplete($option, self::getOption($option::getKey()));
                    }, self::ILJ_OPTION_PREFIX_PAGE . $section, self::ILJ_OPTION_PREFIX_ID . $section);
                    $option->register(self::ILJ_OPTION_PREFIX_PAGE . $section);
                }
            }
        }
        return $this;
    }
    /**
     * Initiate the actions
     *
     * @return void
     */
    protected function addActions()
    {
        foreach ($this->sections as $section => $section_data) {
            if (isset($section_data['actions'])) {
                foreach ($section_data['actions'] as $action) {
                    add_settings_field($action::get_key(), $action::get_title(), function () use ($action) {
                        $action::render_action();
                    }, self::ILJ_OPTION_PREFIX_PAGE . $section, self::ILJ_OPTION_PREFIX_ID . $section);
                }
            }
        }
        return $this;
    }
}