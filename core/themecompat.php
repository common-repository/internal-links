<?php

namespace ILJ\Core;

/**
 * Compatibility handler
 *
 * Responsible for managing compatibility with other 3rd party themes
 *
 * @package ILJ\Core
 *
 * @since 1.3.11
 */
class ThemeCompat
{
    /**
     * Initializes the Compat module
     *
     * @static
     * @since  1.3.11
     *
     * @return void
     */
    public static function init()
    {
        self::enableDivi();
        define('ILJ_THEME_COMPAT', true);
    }
    /**
     * Responsible for loading Divi's ET Builder's code
     *
     * @static
     * @since  1.3.10
     */
    public static function enableDivi()
    {
        if (!defined('ET_BUILDER_THEME')) {
            return;
        }
        $index_mode = Options::getOption(\ILJ\Core\Options\IndexGeneration::getKey());
        if (\ILJ\Enumeration\IndexMode::NONE != $index_mode && \ILJ\Enumeration\IndexMode::AUTOMATIC != $index_mode) {
            return;
        }
        add_action('builder_compat', function () {
            if (!did_action('et_builder_ready')) {
                require_once get_template_directory() . '/includes/builder/' . 'framework.php';
                require_once get_template_directory() . '/includes/builder/' . 'class-et-builder-value.php';
                require_once get_template_directory() . '/includes/builder/' . 'ab-testing.php';
                require_once get_template_directory() . '/includes/builder/' . 'class-et-builder-element.php';
                require_once get_template_directory() . '/includes/builder/' . 'class-et-global-settings.php';
                require_once get_template_directory() . '/includes/builder/' . 'class-et-builder-settings.php';
                if (function_exists('et_builder_init_global_settings')) {
                    et_builder_init_global_settings();
                }
                if (function_exists('et_builder_add_main_elements')) {
                    et_builder_add_main_elements();
                }
                if (function_exists('et_builder_settings_init')) {
                    et_builder_settings_init();
                }
            }
        }, 10);
    }
}