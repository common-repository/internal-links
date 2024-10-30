<?php

namespace ILJ\Helper;

/**
 * Loader
 *
 * Class for registering and enqueuing scripts.
 *
 * @package ILJ\Helper
 * @since   2.23.6
 */
final class Loader
{
    /**
     * Boolean to determine whether we need to use minified script or not.
     *
     * @var bool
     */
    public static $use_minified_resources = null;
    /**
     * This function acts as proxy for `wp_enqueue_script`, This automatically enqueues the dependencies
     * from *.asset.php file if $src is provided.
     *
     * @param string $handle The script handle.
     * @param string $src    The source file url.
     * @param array  $deps   The array of dependencies.
     * @param string $ver    The script version.
     * @param array  $args   Additional arguments.
     * @return void
     */
    public static function enqueue_script($handle, $src = '', $deps = array(), $ver = false, $args = array())
    {
        self::proxy_wp_script_call($handle, $src, $deps, $ver, $args, 'wp_enqueue_script');
    }
    /**
     * This function acts as proxy for 'wp_register_script', This automatically enqueues the dependencies
     * from *.asset.php file if $src is provided.
     *
     * @param string $handle The script handle.
     * @param string $src    The source file url.
     * @param array  $deps   The array of dependencies.
     * @param string $ver    The script version.
     * @param array  $args   Additional arguments.
     * @return void
     */
    public static function register_script($handle, $src = '', $deps = array(), $ver = false, $args = array())
    {
        self::proxy_wp_script_call($handle, $src, $deps, $ver, $args, 'wp_register_script');
    }
    /**
     * Boolean to determine if we need to use minified resources in styles and scripts.
     *
     * @return bool
     */
    private static function should_use_minified_resources()
    {
        if (null === self::$use_minified_resources) {
            self::$use_minified_resources = !defined('SCRIPT_DEBUG') || !SCRIPT_DEBUG;
        }
        return self::$use_minified_resources;
    }
    /**
     * This function acts as proxy for `wp_enqueue_style`, this will
     *
     * @param string $handle The style handle.
     * @param string $src    The source file url.
     * @param array  $deps   The array of dependencies.
     * @param string $ver    The style version.
     * @param array  $media  The media for which this stylesheet has been defined.
     * @return void
     */
    public static function enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
    {
        if (!$src || 0 !== strpos($src, ILJ_URL)) {
            wp_enqueue_style($handle, $src, $deps, $ver, $media);
            return;
        }
        if (self::should_use_minified_resources()) {
            $minified_css_suffix = '.min.css';
            // check if style ends with min.css, if its already ending with .min.css, do nothing.
            $src = (substr($src, -strlen($minified_css_suffix)) === $minified_css_suffix) ? $src : preg_replace('/\.css$/', $minified_css_suffix, $src);
        }
        wp_enqueue_style($handle, $src, $deps, $ver, $media);
    }
    /**
     * This function acts as proxy for `wp_enqueue_script` and 'wp_register_script', This automatically enqueues the dependencies
     * from *.asset.php file if $src is provided.
     *
     * @param string   $handle   The script handle.
     * @param string   $src      The source file url.
     * @param array    $deps     The array of dependencies.
     * @param string   $ver      The script version.
     * @param array    $args     Additional arguments.
     * @param callable $callable The function which needs to be invoked.
     * @return void
     */
    private static function proxy_wp_script_call($handle, $src = '', $deps = array(), $ver = false, $args = array(), $callable = 'wp_enqueue_script')
    {
        // Bail out If source not provided or the dependencies don't start with ILJ_URL.
        if (!$src || 0 !== strpos($src, ILJ_URL, 0)) {
            call_user_func($callable, $handle, $src, $deps, $ver, $args);
            return;
        }
        $should_use_minified_resources = self::should_use_minified_resources();
        if ($should_use_minified_resources) {
            $minified_js_suffix = '.min.js';
            // check if style ends with min.js, if its already ending with .min.js, do nothing.
            $src = (substr($src, -strlen($minified_js_suffix)) === $minified_js_suffix) ? $src : preg_replace('/\.js$/', $minified_js_suffix, $src);
        }
        $assets_path = rtrim(str_replace(ILJ_URL, ILJ_PATH, $src), '.js') . '.asset.php';
        // Merge manual dependencies with automatically extracted dependencies.
        if (file_exists($assets_path)) {
            $assets = include $assets_path;
            if ($assets && is_array($assets)) {
                $deps = array_key_exists('dependencies', $assets) ? array_unique(array_merge($deps, $assets['dependencies'])) : $deps;
                // Use version from *.asset.php if script debug is enabled.
                $ver = ($should_use_minified_resources && array_key_exists('version', $assets)) ? $assets['version'] : $ver;
            }
        }
        call_user_func($callable, $handle, $src, $deps, $ver, $args);
    }
}