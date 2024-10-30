<?php

namespace ILJ\Backend;

use ILJ\Core\Options;
use ILJ\Core\Options\CacheToggleBtnSwitch;
use ILJ\Data\Content;
use ILJ\Helper\Help;
use ILJ\Type\KeywordList;
use ILJ\Database\Postmeta;
use ILJ\Helper\Capabilities;
use ILJ\Helper\IndexAsset as IndexAsset;
/**
 * Admin views
 *
 * Responsible for all view elements which are required on the backend
 *
 * @package ILJ\Backend
 *
 * @since 1.0.0
 */
class Editor
{
    const ILJ_ADMINVIEW_NONCE = '_ilj_adminview_nonce';
    const ILJ_ACTION_AFTER_KEYWORDS_UPDATE = 'ilj_after_keywords_update';
    const ILJ_EDITOR_HANDLE = 'ilj_editor';
    const ILJ_KEYWORDS_HANDLE = 'ilj_keywords';
    const ILJ_IS_BLACKLISTED = 'ilj_is_blacklisted';
    const ILJ_META_KEY_LIMITINCOMINGLINKS = 'ilj_limitincominglinks';
    const ILJ_META_KEY_MAXINCOMINGLINKS = 'ilj_maxincominglinks';
    const ILJ_META_KEY_BLACKLISTDEFINITION = 'ilj_blacklistdefinition';
    const ILJ_META_KEY_LIMITLINKSPERPARAGRAPH = 'ilj_limitlinksperparagraph';
    const ILJ_META_KEY_LINKSPERPARAGRAPH = 'ilj_linksperparagraph';
    const ILJ_META_KEY_LIMITOUTGOINGLINKS = 'ilj_limitoutgoinglinks';
    const ILJ_META_KEY_MAXOUTGOINGLINKS = 'ilj_maxoutgoinglinks';
    const ILJ_KEYWORD_METABOX_FOOTER_HOOK = 'ilj_keyword_metabox_footer';
    /**
     * Registers the keyword metabox on all public post types
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function addKeywordMetaBox()
    {
        foreach (get_post_types(array('public' => true)) as $type) {
            add_meta_box(Postmeta::ILJ_META_KEY_LINKDEFINITION, __('Internal Links', 'internal-links'), array(__CLASS__, 'renderKeywordMetaBox'), $type, 'side', 'default');
        }
    }
    /**
     * Renders the keyword metabox
     *
     * @since 1.0.0
     *
     * @param  \WP_Post $post The post object of the current page
     * @return void
     */
    public static function renderKeywordMetaBox(\WP_Post $post)
    {
        $keyword_list = KeywordList::fromMeta($post->ID, 'post', Postmeta::ILJ_META_KEY_LINKDEFINITION);
        $blacklist_keywords = KeywordList::fromMeta($post->ID, 'post', self::ILJ_META_KEY_BLACKLISTDEFINITION);
        wp_nonce_field(basename(__FILE__), self::ILJ_ADMINVIEW_NONCE);
        ?>
		<p>
		<label for="<?php 
        echo esc_attr(Postmeta::ILJ_META_KEY_LINKDEFINITION . '_keys');
        ?>"><?php 
        esc_html_e('The keywords', 'internal-links');
        ?>:</label>
		<br />
		<input
				type="text"
				name="<?php 
        echo esc_attr(Postmeta::ILJ_META_KEY_LINKDEFINITION . '_keys');
        ?>"
				value="<?php 
        echo esc_attr($keyword_list->encoded());
        ?>" size="30"/>
		<?php 
        ?>
			<input type="text"
				   name="<?php 
        echo esc_attr(self::ILJ_IS_BLACKLISTED);
        ?>"
				   value="<?php 
        echo esc_attr(self::isBlacklisted($post->ID, 'post'));
        ?>"
				   style="display:none"/>

			<input type="text"
				   name="<?php 
        echo esc_attr(self::ILJ_META_KEY_BLACKLISTDEFINITION);
        ?>"
				   value="<?php 
        echo esc_attr($blacklist_keywords->encoded());
        ?>"
				   size="30"
				   style="display:none"/>
					</p>
		<template id="ilj_keyword_metabox_footer">
			<?php 
        $content = Content::from_post($post);
        /**
         * This action is used to render content dynamically in to metabox footer.
         * The HTML content inside <template> tag will rendered at the footer of editor.
         *
         * @param $content Content|null The content, null if its rendered on add post or add term page.
         * @since 2.23.5
         */
        do_action(self::ILJ_KEYWORD_METABOX_FOOTER_HOOK, $content);
        ?>
		</template>
		<?php 
    }
    /**
     * Hooks in to {@link self::ILJ_KEYWORD_METABOX_FOOTER_HOOK} to add custom html to <template> tag
     *
     * @param Content|null $content The content, null if its rendered on add post or add term page.
     * @return void
     */
    public static function render_keyword_metabox_footer($content)
    {
        ?>
		<div class="ilj-row">
			<div class="col-12 ilj-footer">
				<a href="https://internallinkjuicer.com/docs/editor/?utm_source=editor&utm_medium=help&utm_campaign=plugin"
				   rel="noopener" target="_blank" class="help">
					<span class="dashicons dashicons-editor-help"></span>
					<?php 
        esc_html_e('Get help', 'internal-links');
        ?>
				</a>
				<?php 
        self::render_delete_cache_button($content);
        ?>
			</div>
		</div>
		<?php 
    }
    /**
     * Renders delete cache button inside metabox.
     *
     * @param Content|null $content The content, null if its rendered on add post or add term page.
     * @return void
     */
    private static function render_delete_cache_button($content)
    {
        if (!$content) {
            return;
        }
        $link = admin_url(sprintf('admin-ajax.php?action=%s&_wpnonce=%s&ilj_transient_id=%d&ilj_transient_type=%s&ilj_skip_notice=true', 'ilj_clear_single_transient', wp_create_nonce('ilj_clear_single_transient'), $content->get_id(), $content->get_type()));
        $cache_option = Options::getOption(CacheToggleBtnSwitch::getKey());
        if ($cache_option) {
            ?>
		<div class="ilj-delete-cache-container">
			<button class="ilj-button-danger button" id="ilj-delete-cache" type="button" data-ilj-delete-cache-url="<?php 
            echo esc_attr($link);
            ?>">
				<?php 
            esc_html_e('Delete Cache', 'internal-links');
            ?>
			</button>
			<span class="spinner is-active ilj-spinner ilj-hidden"></span>
		</div>
		<?php 
        }
    }
    /**
     * Responsible for saving keyword meta values and
     * stores limit linking settings for posts and
     * stores limit linking settings for posts
     *
     * @since 1.0.0
     *
     * @param  int      $post_id The ID of the post
     * @param  \WP_Post $post    The post object
     * @return void
     */
    public static function saveKeywordMeta($post_id, \WP_Post $post)
    {
        if (is_null($post_id) || is_null($post)) {
            return;
        }
        if (!isset($_POST[self::ILJ_ADMINVIEW_NONCE]) || !wp_verify_nonce($_POST[self::ILJ_ADMINVIEW_NONCE], basename(__FILE__))) {
            return $post_id;
        }
        $post_type = get_post_type_object($post->post_type);
        if (!current_user_can($post_type->cap->edit_post, $post_id)) {
            return $post_id;
        }
        if (array_key_exists(self::ILJ_META_KEY_BLACKLISTDEFINITION, $_POST)) {
            $input_blacklist = stripslashes($_POST[self::ILJ_META_KEY_BLACKLISTDEFINITION]);
            $sanitized_blacklist_meta_value = sanitize_text_field($input_blacklist);
            $keywordsblacklist = KeywordList::fromInput($sanitized_blacklist_meta_value);
            update_post_meta($post_id, self::ILJ_META_KEY_BLACKLISTDEFINITION, array_slice($keywordsblacklist->getKeywords(), 0, 2));
        }
        if (array_key_exists(self::ILJ_IS_BLACKLISTED, $_POST) && true == $_POST[self::ILJ_IS_BLACKLISTED]) {
            self::addToBlacklist($post_id, 'post');
        } else {
            self::removeFromBlacklist($post_id, 'post');
        }
        if (array_key_exists(Postmeta::ILJ_META_KEY_LINKDEFINITION . '_keys', $_POST)) {
            $input = stripslashes($_POST[Postmeta::ILJ_META_KEY_LINKDEFINITION . '_keys']);
            $sanitized_meta_value = sanitize_text_field($input);
            $keywords = KeywordList::fromInput($sanitized_meta_value);
            $update_status = self::set_keywords($post_id, $keywords);
            /**
             * Fires after keyword meta got saved
             *
             * @since 1.0.0
             */
            if (true == $update_status) {
                do_action(self::ILJ_ACTION_AFTER_KEYWORDS_UPDATE, $_POST['post_ID'], 'post', $_POST['post_status']);
            }
        }
    }
    /**
     * Set keywords for a post id, returns boolean or int based on the value.
     *
     * @param int         $post_id  The post id.
     * @param KeywordList $keywords The keyword list for post.
     *
     * @return bool|int
     */
    public static function set_keywords($post_id, $keywords)
    {
        $prev_value = get_post_meta($post_id, Postmeta::ILJ_META_KEY_LINKDEFINITION, true);
        return update_post_meta($post_id, Postmeta::ILJ_META_KEY_LINKDEFINITION, $keywords->getKeywords(), $prev_value);
    }
    /**
     * Logic for adding the assets based on subscription
     *
     * @since 1.1.0
     *
     * @return void
     */
    public static function addAssets()
    {
        $required_role = 'administrator';
        if (!current_user_can($required_role)) {
            return;
        }
        global $pagenow;
        if (in_array($pagenow, array('post-new.php', 'post.php'))) {
            if (!isset($_GET['post_type'])) {
                self::registerAssets();
                return;
            }
            $post_type = get_post_type_object($_GET['post_type']);
            if (!$post_type || !$post_type->public) {
                return;
            }
            self::registerAssets();
        }
    }
    /**
     * Registering the assets for editor frontend
     *
     * @since 1.1.0
     *
     * @return void
     */
    private static function registerAssets()
    {
        add_action('admin_enqueue_scripts', function () {
            \ILJ\Helper\Loader::enqueue_script('jquery-ui-sortable');
            \ILJ\Helper\Loader::register_script(Editor::ILJ_KEYWORDS_HANDLE, ILJ_URL . 'admin/js/ilj_keywords.js', array(), ILJ_VERSION);
            \ILJ\Helper\Loader::register_script(Editor::ILJ_EDITOR_HANDLE, ILJ_URL . 'admin/js/ilj_editor.js', array(), ILJ_VERSION);
            wp_localize_script(Editor::ILJ_EDITOR_HANDLE, 'ilj_editor_translation', Editor::getTranslation());
            wp_add_inline_script(Editor::ILJ_EDITOR_HANDLE, 'const ilj_editor_basic_restriction = ' . json_encode(Editor::getBasicRestrictions()), 'before');
            \ILJ\Helper\Loader::enqueue_script('ilj_tipso', ILJ_URL . 'admin/js/tipso.js', array(), ILJ_VERSION);
            \ILJ\Helper\Loader::enqueue_script(Editor::ILJ_KEYWORDS_HANDLE);
            \ILJ\Helper\Loader::enqueue_script(Editor::ILJ_EDITOR_HANDLE);
            \ILJ\Helper\Loader::enqueue_style('ilj_tipso', ILJ_URL . 'admin/css/tipso.css', array(), ILJ_VERSION);
            \ILJ\Helper\Loader::enqueue_style(Editor::ILJ_EDITOR_HANDLE, ILJ_URL . 'admin/css/ilj_editor.css', array(), ILJ_VERSION);
            \ILJ\Helper\Loader::enqueue_style('ilj_ui', ILJ_URL . 'admin/css/ilj_ui.css', array(), ILJ_VERSION);
        }, 10, 1);
    }
    /**
     * Returns the frontend translation
     *
     * @since 1.0.1
     *
     * @return array
     */
    public static function getTranslation()
    {
        $translation = array('add_keyword' => __('Add Keyword', 'internal-links'), 'placeholder_keyword' => __('Keyword', 'internal-links'), 'howto_case' => __('Keywords get used <strong>case insensitive</strong>', 'internal-links'), 'howto_keyword' => __('Separate multiple keywords by commas', 'internal-links'), 'howto_gap' => __('Configure the gap dimension.', 'internal-links') . ' ' . __('It represents the number of keywords that appear between your other keywords dynamically.', 'internal-links') . ' ' . __('Learn more in our documentation:', 'internal-links') . '<br><strong><a target="_blank" rel="noopener" href="' . Help::getLinkUrl('editor/', 'gaps', 'gap help', 'editor') . '">' . __('Click here to open', 'internal-links') . '</a></strong>', 'headline_gaps' => __('Keyword gaps', 'internal-links'), 'add_gap' => __('Add gap', 'internal-links'), 'gap_type' => __('Gap type', 'internal-links'), 'type_min' => __('Minimum', 'internal-links'), 'type_exact' => __('Exact', 'internal-links'), 'type_max' => __('Maximum', 'internal-links'), 'howto_gap_min' => __('Minimum amount of keywords within the gap.', 'internal-links') . ' ' . __('No upper limits.', 'internal-links'), 'howto_gap_exact' => __('Exact amount of keywords within the gap.', 'internal-links'), 'howto_gap_max' => __('Maximum amount of keywords within the gap.', 'internal-links'), 'howto_links_per_paragraph' => __('Overrides the general setting for the current asset.', 'internal-links'), 'howto_add_to_blacklist' => __('Toggle to add/remove from global blacklist.', 'internal-links'), 'howto_limit_incoming_links' => __('Toggle to add/remove incoming links limit.', 'internal-links'), 'howto_limit_outgoing_links' => __('Toggle to add/remove outgoing links limit.', 'internal-links'), 'insert_gaps' => __('Insert gaps between keywords', 'internal-links'), 'headline_configured_keywords' => __('Configured keywords', 'internal-links'), 'message_keyword_exists' => __('This keyword already exists.', 'internal-links'), 'message_no_keyword' => __('No keyword defined.', 'internal-links'), 'message_length_not_valid' => __('Length of given keyword not valid.', 'internal-links'), 'message_multiple_placeholder' => __('Multiple consecutive placeholders are not allowed.', 'internal-links'), 'no_keywords' => __('No keywords configured.', 'internal-links'), 'gap_hover_exact' => __('Exact keyword gap:', 'internal-links'), 'gap_hover_max' => __('Maximum keyword gap:', 'internal-links'), 'gap_hover_min' => __('Minimum keyword gap:', 'internal-links'), 'limit_incoming_links' => __('Limit incoming Links:', 'internal-links'), 'max_incoming_links' => __('Maximum incoming links:', 'internal-links'), 'blacklist_incoming_links' => __('Keywords, that don`t get linked in the current content:', 'internal-links'), 'message_limited_blacklist_keyword' => __('With the free Basic version of the Iternal Link Juicer, you can block 2 keywords from being linked.', 'internal-links'), 'message_limited_blacklist_keyword_upgrade' => sprintf('&raquo; <a href="%s">', get_admin_url(null, 'admin.php?page=' . AdminMenu::ILJ_MENUPAGE_SLUG . '-pricing')) . __('Upgrade to Pro and add unlimited keywords', 'internal-links') . '</a>', 'headline_configured_keywords_blacklist' => __('Configured keyword blacklist:', 'internal-links'), 'is_blacklisted' => __('Is on global blacklist:', 'internal-links'), 'limit_links_per_paragraph' => __('Limit links per paragraph:', 'internal-links'), 'max_links_per_paragraph' => __('Maximum links per paragraph:', 'internal-links'), 'limit_outgoing_links' => __('Limit outgoing Links:', 'internal-links'), 'max_outgoing_links' => __('Maximum outgoing links:', 'internal-links'), 'cache_cleared' => __('Cache cleared.', 'internal-links'), 'upgrade_to_pro_button_text' => __('Upgrade to Pro', 'internal-links'), 'upgrade_to_pro_link' => get_admin_url(null, 'admin.php?billing_cycle=annual&trial=true&page=' . AdminMenu::ILJ_MENUPAGE_SLUG . '-pricing'), 'pro_feature_title' => __('This is a PRO Feature.', 'internal-links'));
        return $translation;
    }
    /**
     * Sets Basic version restrictions
     *
     * @version 1.2.15
     *
     * @return array
     */
    protected static function getBasicRestrictions()
    {
        $current_screen = get_current_screen();
        $basic_restrictions = array('blacklist_limit' => 2, 'is_active' => true, 'disable_title' => 'class="pro-title"', 'disable_setting' => 'pro-setting', 'disabled' => 'disabled', 'lock_icon' => '<span class="dashicons dashicons-lock tip" title="' . __('This feature is part of the Pro version', 'internal-links') . '"></span>', 'current_screen' => $current_screen->post_type);
        return $basic_restrictions;
    }
    /**
     * Checks if an asset is on the blacklist
     *
     * @since 1.2.15
     *
     * @param  int    $id   The asset ID
     * @param  string $type The asset type
     * @return bool True if blacklisted , false if not
     */
    public static function isBlacklisted($id, $type)
    {
        if ('post' == $type) {
            $postBlacklist = Options::getOption(\ILJ\Core\Options\Blacklist::getKey());
            $blacklisted = false;
            if (is_array($postBlacklist)) {
                if (in_array($id, $postBlacklist)) {
                    $blacklisted = true;
                }
            }
            return $blacklisted;
        }
        return false;
    }
    /**
     * Removes an asset from blacklist
     *
     * @since 1.2.15
     *
     * @param  int    $id   The asset id
     * @param  string $type The asset type
     * @return void
     */
    protected static function removeFromBlacklist($id, $type)
    {
        $blacklist = array();
        if ('post' == $type) {
            $blacklist = Options::getOption(\ILJ\Core\Options\Blacklist::getKey());
        }
        $blacklist = is_array($blacklist) ? $blacklist : array();
        if (($key = array_search($id, $blacklist)) !== false) {
            unset($blacklist[$key]);
        } else {
            return;
        }
        if ('post' == $type) {
            Options::setOption(\ILJ\Core\Options\Blacklist::getKey(), $blacklist);
        }
    }
    /**
     * Adds ID to Blacklist Option of post/terms
     *
     * @since 1.2.15
     *
     * @param  int    $id   The asset id
     * @param  string $type The asset type
     * @return void
     */
    protected static function addToBlacklist($id, $type)
    {
        $blacklist = array();
        if ('post' == $type) {
            $blacklist = Options::getOption(\ILJ\Core\Options\Blacklist::getKey());
        }
        $blacklist = is_array($blacklist) ? $blacklist : array();
        if (in_array($id, $blacklist)) {
            return;
        }
        $blacklist[] = $id;
        if ('post' == $type) {
            Options::setOption(\ILJ\Core\Options\Blacklist::getKey(), $blacklist);
        }
    }
}