<?php

namespace ILJ\Helper;

/**
 * The below line sets the `ticks` directive  to prevent timeout occurring on the frontend.
 * This is set for the complete file, @see https://www.php.net/manual/en/control-structures.declare.php
 * This is needed for {@link Timeout_Monitor_Layer} to work.
 */
declare (ticks=1000);
use ILJ\Core\Options;
use ILJ\Enumeration\TagExclusion;
use ILJ\Type\Ruleset;
/**
 * Replacement helper
 *
 * Handles the operations for removing unwanted elements from a content
 *
 * @package ILJ\Helper
 * @since   1.0.0
 */
class Replacement
{
    const ILJ_FILTER_EXCLUDE_TEXT_PARTS = 'ilj_exclude_text_parts';
    /**
     * Masks areas in the document, which should net get used for further linking anymore
     *
     * @since  1.0.0
     * @param  string $content The content where the rules will get applied on
     * @return Ruleset
     */
    public static function mask(&$content)
    {
        $replace_ruleset = new Ruleset();
        $search_parts = array(
            // exclude all sensible html parts:
            '/(?<parts><a\b[^>]*>.*<\/a>)/sU',
            '/(?<parts><script\b[^>]*>.*<\/script>)/sU',
            '/(?<parts><style\b[^>]*>.*<\/style>)/sU',
        );
        $tag_exclusions = Options::getOption(\ILJ\Core\Options\NoLinkTags::getKey());
        if (is_array($tag_exclusions) && count($tag_exclusions)) {
            foreach ($tag_exclusions as $tag_exclusion) {
                $regex = TagExclusion::getRegex($tag_exclusion);
                if ($regex) {
                    $search_parts[] = $regex;
                }
            }
        }
        /**
         * Filters all parts of content that dont get used for applying link index
         *
         * @since 1.0.0
         * @param array  $search_parts  All parts as regex that get excluded
         */
        $search_parts = apply_filters(self::ILJ_FILTER_EXCLUDE_TEXT_PARTS, $search_parts);
        if (!is_array($search_parts)) {
            $search_parts = array();
        }
        $search_parts[] = '/(?<parts><.*>)/sU';
        foreach ($search_parts as $search_part) {
            preg_match_all($search_part, $content, $matches);
            if (isset($matches['parts'])) {
                foreach ($matches['parts'] as $part) {
                    $link_id = ' ' . 'ilj_' . uniqid('', true) . ' ';
                    $content = str_replace($part, $link_id, $content);
                    $replace_ruleset->addRule($link_id, $part);
                }
            }
            unset($matches);
        }
        return $replace_ruleset;
    }
}