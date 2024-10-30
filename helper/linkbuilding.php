<?php

namespace ILJ\Helper;

use ILJ\Backend\Editor;
use ILJ\Core\Links\Text_To_Link_Converter_Factory;
use ILJ\Database\Linkindex;
/**
 * Toolset for LinkBuilding
 *
 * Methods for handling Link building for frontend
 *
 * @package ILJ\Helper
 * @since   2.0.3
 */
class LinkBuilding
{
    /**
     * Applies the linkbuilder to a piece of content
     *
     * @since 1.2.19
     * @param  mixed $content The content of an post or page
     * @return string
     */
    public function linkContent($content)
    {
        return self::link_something($content, get_the_ID(), 'post');
    }
    /**
     * Handles linking temporarily created link index to currently building content to determine the links already built on paragraphs
     *
     * @param  mixed $content
     * @param  mixed $id
     * @param  mixed $type
     * @param  mixed $build_type
     * @return void
     */
    public static function linkContentTemp($content, $id, $type, $build_type)
    {
        return self::link_something($content, $id, $type, $build_type);
    }
    /**
     * Excludes sitemap urls from applying the link builder filter
     *
     * @return bool
     */
    public static function excludeLinkBuilderFilter()
    {
        global $wp;
        $link = home_url($wp->request);
        $match = preg_match('/[a-zA-Z0-9_]*-sitemap(?:[0-9]*|_index).xml/', strtolower($link));
        return $match;
    }
    /**
     * Generate links to frontend content
     *
     * @param  mixed  $data
     * @param  int    $id
     * @param  string $type
     * @param  string $build_type
     * @return string
     */
    public static function link_something($data, $id, $type, $build_type = null)
    {
        if (is_admin()) {
            return $data;
        }
        if (self::excludeLinkBuilderFilter()) {
            return $data;
        }
        $link_builder = Text_To_Link_Converter_Factory::create($id, $type, $build_type);
        return $link_builder->link_content($data);
    }
    /**
     * Applies the linkbuilder to a term
     *
     * @since 1.2.19
     * @param  string $term_description The term description or content
     * @param  mixed  $term             The term
     * @return string
     */
    public function link_term($term_description, $term = null)
    {
        $term_id = null;
        if (null === $term) {
            $term = get_queried_object();
        }
        if (is_numeric($term)) {
            $term_id = $term;
        } elseif (is_object($term) && isset($term->term_id)) {
            $term_id = $term->term_id;
        }
        if (is_numeric($term_id)) {
            return self::link_something($this->run_shortcodes_filter_html($term_description), $term_id, 'term');
        } else {
            return $term_description;
        }
    }
    /**
     * Run content shortcodes and filter html to properly link contents and not shortcode values
     *
     * @param  string $content
     * @return string
     */
    public function run_shortcodes_filter_html($content)
    {
        $filtered_content = do_shortcode($content);
        return $filtered_content;
    }
    /**
     * Checking if content needs filtering (applying links) if not return false.
     *
     * @param  int    $id
     * @param  string $type
     * @return bool
     */
    public static function is_filter_needed($id, $type)
    {
        if (Editor::isBlacklisted($id, $type)) {
            return false;
        }
        $rules = Linkindex::getRules($id, $type);
        if (is_null($rules) || empty($rules)) {
            return false;
        }
        return true;
    }
}