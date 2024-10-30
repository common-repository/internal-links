<?php

namespace ILJ\Helper;

class Cloudflare
{
    /**
     * Max time limit for any request routed through CF (100 seconds)
     */
    const MAX_TIME_LIMIT = 95;
    /**
     * Check for a standard Cloudflare header and return the minimum between safe limit and pre configured
     *
     * @return int
     */
    public static function check_header_for_timelimit_adjust()
    {
        if (function_exists('ini_get')) {
            $current_limit = ini_get('max_execution_time');
        } else {
            $current_limit = 30;
        }
        $headers = getallheaders();
        // sometimes this function fails, we cannot do anything then
        if (!is_array($headers)) {
            return $current_limit;
        }
        $headers = array_change_key_case($headers, CASE_LOWER);
        if (0 === (int) $current_limit) {
            return self::MAX_TIME_LIMIT;
        } else if (isset($headers['cf-connecting-ip']) && '' != $headers['cf-connecting-ip']) {
            return min($current_limit, self::MAX_TIME_LIMIT);
        } else {
            return $current_limit;
        }
    }
}