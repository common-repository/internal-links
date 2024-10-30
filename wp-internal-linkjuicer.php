<?php

// @codingStandardsIgnoreStart
/**
 * Internal Link Juicer
 *
 * @version 2.24.5
 * @package ILJ
 *
 * @wordpress-plugin
 * Plugin Name: Internal Link Juicer
 * Plugin URI: https://www.internallinkjuicer.com
 * Version: 2.24.5
 * Description: A performant solution for high class internal linkbuilding automation.
 * Author: TeamUpdraft
 * Author URI: https://www.internallinkjuicer.com
 * License: GPL v3
 * Domain Path: /languages/
 * Text Domain: internal-links
 *
 * Internal Link Juicer
 * Copyright (C) 2018, Mark Schatz - dev@internallinkjuicer.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
// @codingStandardsIgnoreEnd
namespace ILJ;

use ILJ\Core\App;
if (!function_exists('add_filter')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}
if (!defined('ILJ_VERSION')) {
    define('ILJ_VERSION', '2.24.5');
}
if (!defined('ILJ_FILE')) {
    define('ILJ_FILE', __FILE__);
}
if (!defined('ILJ_PATH')) {
    define('ILJ_PATH', plugin_dir_path(ILJ_FILE));
}
if (!defined('ILJ_URL')) {
    define('ILJ_URL', plugin_dir_url(ILJ_FILE));
}
if (!defined('ILJ_NAME')) {
    define('ILJ_NAME', plugin_basename(ILJ_FILE));
}
if (!defined('ILJ_FREEMIUS_PLUGIN_ID')) {
    define('ILJ_FREEMIUS_PLUGIN_ID', '2610');
}
if (function_exists('\ILJ\ilj_fs')) {
    ilj_fs()->set_basename(false, __FILE__);
    return;
} else {
    function ilj_fs()
    {
        global $ilj_fs;
        if (!isset($ilj_fs)) {
            require_once ILJ_PATH . '/vendor/freemius/wordpress-sdk/start.php';
            $first_path = 'admin.php?page=internal_link_juicer-tour';
            if (is_plugin_active_for_network(ILJ_NAME)) {
                $first_path = '';
            }
            $ilj_fs = fs_dynamic_init(array('id' => ILJ_FREEMIUS_PLUGIN_ID, 'slug' => 'internal-links', 'type' => 'plugin', 'public_key' => 'pk_624b0309eb6afc65f81d070e79fcd', 'is_premium' => false, 'premium_suffix' => '(Pro)', 'has_addons' => false, 'has_paid_plans' => true, 'trial' => array('days' => 14, 'is_require_payment' => true), 'has_affiliation' => 'selected', 'menu' => array('slug' => 'internal_link_juicer', 'first-path' => $first_path, 'support' => false, 'affiliation' => false), 'is_live' => true));
        }
        return $ilj_fs;
    }
    ilj_fs();
    do_action('ilj_fs_loaded');
    spl_autoload_register(function ($class_name) {
        if (false !== strpos($class_name, 'ILJ\\')) {
            $file = strtolower(str_replace('\\', '/', substr($class_name, 4))) . '.php';
            if (file_exists(ILJ_PATH . $file)) {
                include_once $file;
            } else {
                /**
                 * The new classes follows UDP Standards, class names are separated by underscores, so they need to be
                 * to be replaced with hyphen, for example Content_Type class would have file name content-type.php
                 *
                 * @since 2.23.5
                 */
                $file = strtolower(str_replace('\\', '/', substr(str_replace('_', '-', $class_name), 4))) . '.php';
                if (file_exists(ILJ_PATH . $file)) {
                    include_once $file;
                }
            }
        }
    });
    App::init();
}