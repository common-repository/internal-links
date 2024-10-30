<?php

namespace ILJ\Helper;

/**
 * Regex toolset
 *
 * Methods for regex operations
 *
 * @package ILJ\Helper
 * @since   1.2.0
 */
class Regex
{
    /**
     * Validates if a regex pattern is valid
     *
     * @since  1.2.0
     * @param  string $pattern The regular expression
     * @return bool
     */
    public static function isValid($pattern)
    {
        if (preg_match('/' . $pattern . '/', '') === false) {
            return false;
        }
        return true;
    }
    /**
     * Escapes the dot character in a text
     *
     * @since 1.2.0
     * @param string $text Text that gets escaped
     *
     * @return string
     */
    public static function escapeDot($text)
    {
        return str_replace('.', '\.', $text);
    }
}