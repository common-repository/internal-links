<?php

namespace ILJ\Cache;

class Transient_Cache
{
    const CACHE_PREFIX = 'ilj_filtered_content';
    const TRANSIENT_PREFIX = '_transient_';
    /**
     * Set the cache.
     *
     * @param string $key   The cache key.
     * @param string $value The cache value.
     * @return void
     */
    private static function set(string $key, string $value)
    {
        set_transient($key, $value, DAY_IN_SECONDS);
    }
    /**
     * Delete all cache.
     *
     * @return void
     */
    public static function delete_all()
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like(self::TRANSIENT_PREFIX . self::CACHE_PREFIX) . '%'));
    }
    /**
     * Delete cache for the supplied params.
     *
     * @param int    $id   The post/term id.
     * @param string $type One of types 'post', 'term', 'post_meta', 'term_meta'
     * @return string|false
     */
    public static function delete_cache_for_content($id, $type)
    {
        global $wpdb;
        $key = self::build_cache_key($id, $type);
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like(self::TRANSIENT_PREFIX . $key) . '%'));
    }
    /**
     * Get the cache value.
     *
     * @param string $key
     * @return string | false
     */
    private static function get(string $key)
    {
        return get_transient($key);
    }
    /**
     * Build cache key from the supplied parameters.
     *
     * @param int    $id            The post/term id.
     * @param string $type          One of types 'post', 'term', 'post_meta', 'term_meta'
     * @param string $input_content The input content.
     * @return string
     */
    private static function build_cache_key_for_content($id, $type, $input_content): string
    {
        return sprintf('%s_%d_%s_%s', self::CACHE_PREFIX, $id, $type, md5($input_content));
    }
    /**
     * Build cache key from the supplied parameters.
     *
     * @param int    $id   The post/term id.
     * @param string $type One of types 'post', 'term', 'post_meta', 'term_meta'
     * @return string
     */
    private static function build_cache_key($id, $type): string
    {
        return sprintf('%s_%d_%s', self::CACHE_PREFIX, $id, $type);
    }
    /**
     * Set cache for the supplied parameters.
     *
     * @param int    $id     The post/term id.
     * @param string $type   One of types 'post', 'term', 'post_meta', 'term_meta'
     * @param string $input  The input content before linking, this will be hashed for generating cache key
     * @param string $output The output content after linking.
     * @return void
     */
    public static function set_cache_for_content($id, $type, $input, $output)
    {
        self::set(self::build_cache_key_for_content($id, $type, $input), $output);
    }
    /**
     * Get cache for the supplied params.
     *
     * @param int    $id            The post/term id.
     * @param string $type          One of types 'post', 'term', 'post_meta', 'term_meta'
     * @param string $input_content The input content.
     * @return string|false
     */
    public static function get_cache_for_content($id, $type, $input_content)
    {
        return self::get(self::build_cache_key_for_content($id, $type, $input_content));
    }
}