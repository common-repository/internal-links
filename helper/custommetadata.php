<?php

namespace ILJ\Helper;

use ILJ\Backend\Editor;
use ILJ\Core\LinkBuilder;
use ILJ\Core\Links\Text_To_Link_Converter_Factory;
use ILJ\Core\Options;
use ILJ\Posttypes\CustomLinks;
/**
 * This is where custom meta data output is filtered
 *
 * @package ILJ\Helper
 * @since   2.0.0
 */
class CustomMetaData
{
    /**
     * LinkBuilder for postmeta
     *
     * @var LinkBuilder
     */
    private $link_builder_postmeta;
    /**
     * LinkBuilder for termmeta
     *
     * @var LinkBuilder
     */
    private $link_builder_termmeta;
    const ILJ_MUFFIN_BUILDER_META_FIELD = 'mfn-page-items';
    const ILJ_OXYGEN_BUILDER_META_FIELD = 'ct_builder_shortcodes';
    /**
     * Determines if the custom field can be linked.
     *
     * @param string $meta_key
     * @param array  $saved_custom_fields
     *
     * @return boolean
     */
    private function can_field_be_linked($meta_key, $saved_custom_fields)
    {
        if (in_array($meta_key, $saved_custom_fields)) {
            return true;
        }
        // we can check if we have any regex rules inside the configured options.
        foreach ($saved_custom_fields as $regex_rule) {
            if (!Regex_Custom_Field::is_valid_rule($regex_rule)) {
                continue;
            }
            $rule = Regex_Custom_Field::from($regex_rule);
            if ($rule->meta_key_matches_rule($meta_key)) {
                return true;
            }
        }
        return false;
    }
}