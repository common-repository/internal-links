<?php

namespace ILJ\Enumeration;

/**
 * Class to represent different types of wordpress content ( Post, Term)
 *
 * @package ILJ\Enumeration
 * @since   2.23.5
 */
final class Content_Type
{
    const POST = 'post';
    const TERM = 'term';
    const ALL_CONTENT_TYPES = array(self::POST, self::TERM);
    /**
     * check if content type is valid.
     *
     * @param string $content_type The string to check.
     * @return bool
     */
    public static function is_valid($content_type)
    {
        return in_array($content_type, self::ALL_CONTENT_TYPES, true);
    }
}