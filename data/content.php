<?php

namespace ILJ\Data;

use ILJ\Backend\Editor;
use ILJ\Database\Postmeta;
use ILJ\Enumeration\Content_Type;
use ILJ\Type\KeywordList;
/**
 * Class to encapsulate post or term.
 *
 * @package ILJ\Data
 * @since   2.23.5
 */
final class Content
{
    /**
     * The numerical identifier.
     *
     * @var int $id;
     */
    private $id;
    /**
     * The type of content, which can be  {@link Content_Type::POST} or {@link Content_Type::TERM}
     *
     * @var string $type
     */
    private $type;
    /**
     * The type of post or term, which can be page, product, download or category, tag, product_cat
     *
     * @var string $sub_type
     */
    private $sub_type;
    /**
     * Class constructor.
     *
     * @param int    $id       Id of the content.
     * @param string $type     Type of the content. It can be either a post or a term.
     * @param string $sub_type Sub type of the content.
     */
    private function __construct($id, $type, $sub_type = '')
    {
        $this->id = $id;
        $this->type = $type;
        $this->sub_type = $sub_type;
    }
    /**
     * Get the numerical identifier of the object.
     *
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }
    /**
     * Set keywords for content
     *
     * @param KeywordList $keyword_list The keyword list.
     *
     * @return Content
     */
    public function set_keywords($keyword_list)
    {
        $is_meta_updated = update_metadata($this->get_type(), $this->get_id(), Postmeta::ILJ_META_KEY_LINKDEFINITION, $keyword_list->getKeywords(), get_metadata($this->get_type(), $this->get_id(), Postmeta::ILJ_META_KEY_LINKDEFINITION, true));
        if ($is_meta_updated) {
            /*
             * The last argument for this legacy action passes publish if its a term, if its a post it gets the post status,
             * to retain this behavior this uses the same structure.
             */
            $status = (Content_Type::POST == $this->get_type()) ? get_post_status($this->get_id()) : 'publish';
            do_action(Editor::ILJ_ACTION_AFTER_KEYWORDS_UPDATE, $this->get_id(), $this->get_type(), $status);
        }
        return $this;
    }
    /**
     * Set whether the limit for incoming links to a content needs to be enabled.
     *
     * @param bool $is_limit_enabled State to denote if the limit is enabled
     *
     * @return Content
     */
    public function set_limit_incoming_links($is_limit_enabled)
    {
        update_metadata($this->get_type(), $this->get_id(), Editor::ILJ_META_KEY_LIMITINCOMINGLINKS, $is_limit_enabled, get_metadata($this->get_type(), $this->get_id(), Editor::ILJ_META_KEY_LIMITINCOMINGLINKS, true));
        return $this;
    }
    /**
     * Set the number of incoming links to a content, this will be applied only when link is enabled.
     *
     * @param int $max_incoming_links The incoming links limit.
     *
     * @return Content
     */
    public function set_max_incoming_links($max_incoming_links)
    {
        update_metadata($this->get_type(), $this->get_id(), Editor::ILJ_META_KEY_MAXINCOMINGLINKS, $max_incoming_links, get_metadata($this->get_type(), $this->get_id(), Editor::ILJ_META_KEY_MAXINCOMINGLINKS, true));
        return $this;
    }
    /**
     * Get the type of the identifier.
     *
     * @return string The type of content, which can be  {@link Content_Type::POST} or {@link Content_Type::TERM}
     */
    public function get_type()
    {
        return $this->type;
    }
    /**
     * Creates instance of content from post id.
     *
     * @param int    $post_id          The post id.
     * @param string $content_sub_type The type of post, which can be page, product, download.
     * @return Content
     */
    public static function from_post_id($post_id, $content_sub_type = '')
    {
        return new self($post_id, Content_Type::POST, $content_sub_type);
    }
    /**
     * Construct a content instance from content type and id.
     *
     * @param string $content_type     The type of content, which can be  {@link Content_Type::POST} or {@link Content_Type::TERM}
     * @param string $content_sub_type The type of post or term, which can be category, tag, product_cat
     * @param int    $id               The numerical identifier.
     *
     * @return Content|null
     */
    public static function from_content_type_and_id($content_type, $content_sub_type, $id)
    {
        if (Content_Type::POST == $content_type) {
            return self::from_post_id($id, $content_sub_type);
        } elseif (Content_Type::TERM == $content_type) {
            return self::from_term_id($id, $content_sub_type);
        }
    }
    /**
     * Creates instance of content from term id.
     *
     * @param int    $term_id          The post id.
     * @param string $content_sub_type The type of post or term, which can be category, tag, product_cat.
     * @return Content
     */
    public static function from_term_id($term_id, $content_sub_type = '')
    {
        return new self($term_id, Content_Type::TERM, $content_sub_type);
    }
    /**
     * Creates instance of content from {@link \WP_Post}
     *
     * @param \WP_Post $post The post instance.
     * @return Content
     */
    public static function from_post($post)
    {
        return new self($post->ID, Content_Type::POST);
    }
    /**
     * Creates instance of content from {@link \WP_Term}
     *
     * @param \WP_Term $term The term instance.
     * @return Content
     */
    public static function from_term($term)
    {
        return new self($term->term_id, Content_Type::TERM);
    }
    /**
     * Returns the keywords for the content.
     *
     * @return array
     */
    public function get_keywords()
    {
        return KeywordList::fromMeta($this->get_id(), $this->get_type())->getKeywords();
    }
    /**
     * Return the title string.
     *
     * @return string
     */
    public function get_title()
    {
        switch ($this->get_type()) {
            case Content_Type::POST:
                return get_the_title($this->get_id());
            case Content_Type::TERM:
                return get_term($this->get_id())->name;
        }
        return '';
    }
    /**
     * Return the edit link
     *
     * @return string
     */
    public function get_edit_link()
    {
        if ('post' === $this->get_type()) {
            return get_edit_post_link($this->get_id());
        } elseif ('term' === $this->get_type()) {
            return get_edit_term_link($this->get_id(), get_term($this->get_id())->taxonomy);
        }
        return '';
    }
    /**
     * Return the edit title
     *
     * @return string edit title
     */
    public function get_edit_title()
    {
        if ('post' === $this->get_type()) {
            return sprintf(__('Edit %s', 'internal-links'), $this->get_cpt_singular_name());
        } elseif ('term' === $this->get_type()) {
            return sprintf(__('Edit %s', 'internal-links'), $this->get_taxonomy_singular_name());
        }
        return '';
    }
    /**
     * Get custom post type singular name.
     *
     * @return string custom post type singular name.
     */
    private function get_cpt_singular_name()
    {
        return get_post_type_object($this->sub_type)->labels->singular_name;
    }
    /**
     * Get taxonomy singular name.
     *
     * @return string taxonomy singular name.
     */
    private function get_taxonomy_singular_name()
    {
        return get_taxonomy($this->get_sub_type())->labels->singular_name;
    }
    /**
     * Get sub type of content.
     *
     * @return string Sub type of content.
     */
    private function get_sub_type()
    {
        return $this->sub_type;
    }
    /**
     * Return the permalink
     *
     * @return string
     */
    public function get_permalink()
    {
        if ('post' === $this->get_type()) {
            return get_permalink(absint($this->get_id()));
        } elseif ('term' === $this->get_type()) {
            return get_term_link(absint($this->get_id()));
        }
        return '';
    }
    /**
     * Get view title.
     *
     * @return string view title.
     */
    public function get_permalink_title()
    {
        if ('post' === $this->get_type()) {
            return sprintf(__('View %s', 'internal-links'), $this->get_cpt_singular_name());
        } elseif ('term' === $this->get_type()) {
            return sprintf(__('View %s', 'internal-links'), $this->get_taxonomy_singular_name());
        }
        return '';
    }
    /**
     * Return the entity type based on the content type ( it can be post type or taxonomy )
     *
     * @return string
     */
    public function get_entity_type()
    {
        $type = $this->get_type();
        if ('post' === $type) {
            return get_post_type($this->get_id());
        } elseif ('term' === $type) {
            $term = get_term($this->get_id());
            $taxonomy_name = ($term instanceof \WP_Term) ? $term->taxonomy : '';
            if (!$taxonomy_name) {
                return '';
            }
            $taxonomy = get_taxonomy($taxonomy_name);
            if (!$taxonomy instanceof \WP_Taxonomy) {
                return '';
            }
            return property_exists($taxonomy->labels, 'singular_name') ? $taxonomy->labels->singular_name : '';
        }
        return '';
    }
}