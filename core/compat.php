<?php

namespace ILJ\Core;

use ILJ\Backend\MenuPage\Tools;
use ILJ\Core\IndexStrategy\PolylangStrategy;
use ILJ\Core\IndexStrategy\WPMLStrategy;
use ILJ\Core\Links\Text_To_Link_Converter_Factory;
use ILJ\Core\Options\CustomFieldsToLinkPost;
use ILJ\Core\Options\CustomFieldsToLinkTerm;
use ILJ\Core\Options\Whitelist;
use ILJ\Enumeration\LinkType;
use ILJ\Helper\Ajax;
use ILJ\Helper\BatchInfo;
use ILJ\Helper\CustomMetaData;
use ILJ\Helper\IndexAsset;
use ILJ\Helper\LinkBuilding;
use ILJ\Posttypes\CustomLinks;
use ILJ\Type\KeywordList;
/**
 * Compatibility handler
 *
 * Responsible for managing compatibility with other 3rd party plugins
 *
 * @package ILJ\Core
 *
 * @since 1.2.0
 */
class Compat
{
    /**
     * Initializes the Compat module
     *
     * @static
     * @since  1.2.0
     *
     * @return void
     */
    public static function init()
    {
        self::enableWpml();
        self::enableYoast();
        self::enableRankMath();
        self::enablePolylang();
        self::enableDivi();
        self::enableACF();
    }
    /**
     * Responsible for handling Polylang integration
     *
     * @static
     * @since  1.2.2
     *
     * @return void
     */
    public static function enablePolylang()
    {
        if (!defined('POLYLANG_BASENAME')) {
            return;
        }
        add_filter(IndexBuilder::ILJ_FILTER_INDEX_STRATEGY, function () {
            return new PolylangStrategy();
        });
        // fallback language is English whatever the default language
        add_filter('pll_preferred_language', function ($slug) {
            return (false === $slug) ? 'en' : $slug;
        });
        add_filter('add_to_meta_box_exception', function ($metabox) {
            array_push($metabox, 'ml_box');
            return $metabox;
        });
        add_filter(Ajax::ILJ_FILTER_AJAX_SEARCH_POSTS, function ($data) {
            for ($i = 0; $i < count($data); $i++) {
                $data[$i]['text'] = $data[$i]['text'] . ' (' . pll_get_post_language($data[$i]['id']) . ')';
            }
            return $data;
        }, 10, 2);
        add_filter(IndexAsset::ILJ_FILTER_INDEX_ASSET, function ($meta_data, $type, $id) {
            $asset_language = '';
            $language_container = array();
            $asset_language = ('' == $asset_language) ? pll_get_post_language($id) : $asset_language;
            if (!$asset_language || '' == $asset_language) {
                return $meta_data;
            }
            if (!isset($language_container[$asset_language])) {
                $language_container[$asset_language] = PLL()->model->get_language($asset_language);
            }
            $flag_url = $language_container[$asset_language]->flag_url;
            $flag_img = sprintf('<img class="tip" src="%s" title="%s" />', $flag_url, $language_container[$asset_language]->name);
            $meta_data->title = $flag_img . ' ' . $meta_data->title;
            return $meta_data;
        }, 10, 3);
    }
    /**
     * Responsible for handling WPML integration
     *
     * @static
     * @since  1.2.0
     *
     * @return void
     */
    protected static function enableWpml()
    {
        if (!function_exists('icl_object_id') || defined('POLYLANG_BASENAME')) {
            return;
        }
        add_filter(IndexBuilder::ILJ_FILTER_INDEX_STRATEGY, function () {
            return new WPMLStrategy();
        });
        add_filter(Ajax::ILJ_FILTER_AJAX_SEARCH_POSTS, function ($data, $args) {
            global $sitepress;
            $languages = WPMLStrategy::getLanguages();
            $current_language = $sitepress->get_current_language();
            for ($i = 0; $i < count($data); $i++) {
                $data[$i]['text'] = $data[$i]['text'] . ' (' . $current_language . ')';
            }
            foreach ($languages as $language) {
                if ($language == $current_language) {
                    continue;
                }
                $sitepress->switch_lang($language, true);
                $query = new \WP_Query($args);
                foreach ($query->posts as $post) {
                    $data[] = array('id' => $post->ID, 'text' => $post->post_title . ' (' . $language . ')');
                }
                $sitepress->switch_lang($current_language, true);
            }
            return $data;
        }, 10, 2);
        add_filter(IndexAsset::ILJ_FILTER_INDEX_ASSET, function ($meta_data, $type, $id) {
            global $sitepress;
            $language_info = (!isset($language_info)) ? wpml_get_language_information(null, (int) $id) : $language_info;
            if (!$language_info) {
                return $meta_data;
            }
            $flag_url = $sitepress->get_flag_url($language_info['language_code']);
            $flag_img = sprintf('<img class="tip" src="%s" title="%s" />', $flag_url, $language_info['display_name']);
            $meta_data->title = $flag_img . ' ' . $meta_data->title;
            return $meta_data;
        }, 10, 3);
    }
    /**
     * Responsible for handling Yoast-SEO integration
     *
     * @static
     * @since  1.2.0
     *
     * @return void
     */
    protected static function enableYoast()
    {
        if (!defined('WPSEO_VERSION')) {
            return;
        }
        add_filter(Tools::ILJ_FILTER_MENUPAGE_TOOLS_KEYWORD_IMPORT_POST, function ($keyword_import_source) {
            $import_source = array('title' => __('Yoast focus keywords', 'internal-links'), 'class' => 'yoast-seo');
            $keyword_import_source[] = $import_source;
            return $keyword_import_source;
        });
        add_filter(Tools::ILJ_FILTER_MENUPAGE_TOOLS_KEYWORD_IMPORT_TERM, function ($keyword_import_source) {
            $import_source = array('title' => __('Yoast focus keywords', 'internal-links'), 'class' => 'yoast-seo');
            $keyword_import_source[] = $import_source;
            return $keyword_import_source;
        });
    }
    /**
     * Responsible for handling RankMath integration
     *
     * @static
     * @since  1.2.0
     *
     * @return void
     */
    protected static function enableRankMath()
    {
        if (!class_exists('RankMath')) {
            return;
        }
        add_filter(Tools::ILJ_FILTER_MENUPAGE_TOOLS_KEYWORD_IMPORT_POST, function ($keyword_import_source) {
            $import_source = array('title' => __('RankMath focus keywords', 'internal-links'), 'class' => 'rankmath');
            $keyword_import_source[] = $import_source;
            return $keyword_import_source;
        });
        add_filter(Tools::ILJ_FILTER_MENUPAGE_TOOLS_KEYWORD_IMPORT_TERM, function ($keyword_import_source) {
            $import_source = array('title' => __('RankMath focus keywords', 'internal-links'), 'class' => 'rankmath');
            $keyword_import_source[] = $import_source;
            return $keyword_import_source;
        });
    }
    /**
     * Responsible for loading Divi's ET Builder's code
     *
     * @static
     * @since  1.3.12
     */
    public static function enableDivi()
    {
        if (!is_plugin_active('divi-builder/divi-builder.php')) {
            return;
        }
        $index_mode = Options::getOption(\ILJ\Core\Options\IndexGeneration::getKey());
        if (\ILJ\Enumeration\IndexMode::NONE != $index_mode && \ILJ\Enumeration\IndexMode::AUTOMATIC != $index_mode) {
            return;
        }
        add_action('builder_compat', function () {
            if (!did_action('et_builder_ready')) {
                require_once ET_BUILDER_DIR . 'class-et-builder-value.php';
                require_once ET_BUILDER_DIR . 'ab-testing.php';
                require_once ET_BUILDER_DIR . 'class-et-builder-element.php';
                require_once ET_BUILDER_DIR . 'class-et-global-settings.php';
                require_once ET_BUILDER_DIR . 'framework.php';
                require_once ET_BUILDER_DIR . 'class-et-builder-settings.php';
                et_builder_init_global_settings();
                et_builder_add_main_elements();
                et_builder_settings_init();
            }
        }, 10);
    }
    /**
     * The below method iteratively search for `content` key, when the content is string, it gets
     * concatenated to single string which is used for building index.
     *
     * @param array $items
     *
     * @return string
     */
    private static function extract_content_from_field($items)
    {
        $content = '';
        $queue = $items;
        while ($queue) {
            $items = array_shift($queue);
            if (wp_is_numeric_array($items)) {
                $queue = array_merge($queue, $items);
            } elseif (is_array($items)) {
                foreach ($items as $key => $value) {
                    if ('content' === $key || is_array($value)) {
                        $queue[] = $value;
                    }
                }
            } elseif (is_string($items)) {
                $content .= wpautop($items);
            }
        }
        return $content;
    }
    /**
     * Check if ACF is enabled
     *
     * @return void
     */
    protected static function enableACF()
    {
        if (!class_exists('ACF')) {
            return;
        }
        add_filter(CustomFieldsToLinkPost::ILJ_ACF_HINT_FILTER_POST, function ($hint) {
            $hint = "<p class='description'> <span>&#8505;</span>  " . __('You can select the fields created by ACF for posts here', 'internal-links') . '</p>';
            return $hint;
        }, 10, 1);
        add_filter(CustomFieldsToLinkTerm::ILJ_ACF_HINT_FILTER_TERM, function ($hint) {
            $hint = "<p class='description'> <span>&#8505;</span>  " . __('You can select the fields created by ACF for terms here', 'internal-links') . '</p>';
            return $hint;
        }, 10, 1);
        add_action(CustomFieldsToLinkPost::ILJ_ACTION_ADD_PRO_FEATURES, function () {
            ?>
				<li><span><?php 
            esc_html_e('Full power of ACF', 'internal-links');
            ?></span>: <?php 
            esc_html_e('Configure custom fields from ACF and enable automatic linking in your custom created content.', 'internal-links');
            ?></li>
				<?php 
        }, 20);
    }
}