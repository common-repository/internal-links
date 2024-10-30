<?php

namespace ILJ\Filters;

use ILJ\Core\Options;
/**
 * Link_Attributes_Filter
 *
 * This is used to add additional attributes to post/term link.
 *
 * @package ILJ\Backend
 * @since   2.23.5
 */
class Link_Attributes_Filter
{
    /**
     * Register the filters for link attributes.
     *
     * @return void
     */
    public function register()
    {
        if (!Options::getOption(Options\Link_Preview_Switch::getKey())) {
            return;
        }
        add_filter('ilj_post_link_attributes', function ($link_attrs, $post_id, $excerpt) {
            if (!$excerpt) {
                return $link_attrs;
            }
            $link_attrs['data-ilj-link-preview'] = 'true';
            $link_attrs['data-featured-image'] = get_the_post_thumbnail_url($post_id, 'medium');
            $link_attrs['data-excerpt'] = $excerpt;
            return $link_attrs;
        }, 10, 3);
        add_filter('ilj_term_link_attributes', function ($link_attrs, $term_id, $excerpt) {
            if (!$excerpt) {
                return $link_attrs;
            }
            $link_attrs['data-ilj-link-preview'] = 'true';
            $link_attrs['data-excerpt'] = $excerpt;
            return $link_attrs;
        }, 10, 3);
    }
}