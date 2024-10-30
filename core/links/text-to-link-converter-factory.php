<?php

namespace ILJ\Core\Links;

use ILJ\Core\LinkBuilder;
use ILJ\Core\Options\CacheToggleBtnSwitch;
/**
 * Factory for creating {@link Text_To_Link_Converter_Interface} instance.
 */
final class Text_To_Link_Converter_Factory
{
    /**
     * The below method determines how many seconds needs to be allocated for content linking, so that it doesnt cause timeout.
     *
     * @param int $default_value The default seconds value which will be returned when method can't determine the time required.
     * @return int The number of seconds.
     */
    private static function get_time_required_for_content_linking($default_value): int
    {
        if (!isset($_SERVER['REQUEST_TIME']) || !function_exists('ini_get') || !ini_get('max_execution_time') || !intval($_SERVER['REQUEST_TIME']) || !intval(ini_get('max_execution_time'))) {
            return $default_value;
        }
        $elapsed_time_in_secs = time() - intval($_SERVER['REQUEST_TIME']);
        $available_time_in_secs = intval(ini_get('max_execution_time')) - $elapsed_time_in_secs;
        // use 70 percent of available time.
        $time_in_secs = round(0.7 * $available_time_in_secs, 2);
        /**
         * There can be two scenarios
         *
         * 1. max_execution_time set to 30 secs, when our code is invoked 25 secs are already consumed, in that case we cant
         * use default_value because it will cause timeout, so 70% of remaining time (0.7 * 5 secs) would be used.
         *
         * 2. max_execution_time set to 60 secs, when our code is invoked 10 secs are already consumed, if we use 70% (50 * 0.7 secs) this will
         * cause slow loading in the page, so default value will be returned.
         */
        return min($time_in_secs, $default_value);
    }
    /**
     * The below method builds {@link Text_To_Link_Converter_Interface} instance and wraps several layers around the instance
     * using decorator design pattern.
     *
     * @see https://en.wikipedia.org/wiki/Decorator_pattern
     *
     * +-------------+-----------------------+--------------------------------------------------------------------------------------------------------+
     * | Layer Order |      Layer Name       |                                              Description                                               |
     * +-------------+-----------------------+--------------------------------------------------------------------------------------------------------+
     * |           1 | Timeout_Monitor_Layer | Checks if linking content takes place with in specified number of seconds, if not it stops the process |
     * |           2 | Cache_Layer           | Checks if the cache is available, if not it will pass the request to next layer and sets the cache.    |
     * |           3 | LinkBuilder           | Actual class responsible for linking the content.                                                      |
     * +-------------+-----------------------+--------------------------------------------------------------------------------------------------------+
     *
     * Factory method to create new {@link Text_To_Link_Converter_Interface} instance
     *
     * @param int     $id                           The ID of the current subject
     * @param string  $type                         The type of the current subject
     * @param string  $build_type
     * @param boolean $content_type                 The content type in which link replacements take place, defaults to html.
     * @param int     $default_allowed_time_in_secs The max allowed time in seconds for this linking operation, this will be **only used** when it can't determine how much time needs to be allocated.
     *
     * @return Text_To_Link_Converter_Interface
     * @since  1.0.1
     */
    public static function create($id, $type, $build_type = null, $content_type = 'html', $default_allowed_time_in_secs = 20): Text_To_Link_Converter_Interface
    {
        $link_builder = new LinkBuilder($id, $type, $build_type, $content_type);
        $cache_option = get_option(CacheToggleBtnSwitch::getKey(), 'options');
        if ($cache_option) {
            // Put a caching layer over link builder.
            $with_cache = new Cache_Layer($id, $type, $link_builder);
            return new Timeout_Monitor_Layer(self::get_time_required_for_content_linking($default_allowed_time_in_secs), $with_cache);
        } else {
            return new Timeout_Monitor_Layer(self::get_time_required_for_content_linking($default_allowed_time_in_secs), $link_builder);
        }
    }
}