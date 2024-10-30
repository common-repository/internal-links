<?php

namespace ILJ\Core;

use ActionScheduler;
use ActionScheduler_Store;
use ILJ\Backend\AdminMenu;
use ILJ\Backend\Editor;
use ILJ\Backend\MenuPage\Tools;
use ILJ\Backend\RatingNotifier;
use ILJ\Backend\Environment;
use ILJ\Cache\Transient_Cache;
use ILJ\Core\Options\Link_Preview_Switch;
use ILJ\Core\Options\Limit_Incoming_Links;
use ILJ\Core\Options\Max_Incoming_Links;
use ILJ\Core\Options\MultipleKeywords;
use ILJ\Helper\Capabilities;
use ILJ\Backend\Menupage\Settings;
use ILJ\Backend\Notices;
use ILJ\Core\Options\CustomFieldsToLinkPost;
use ILJ\Core\Options\TaxonomyWhitelist;
use ILJ\Core\Options\Whitelist;
use ILJ\Database\Keywords;
use ILJ\Database\Linkindex;
use ILJ\Enumeration\LinkType;
use ILJ\Enumeration\TagExclusion;
use ILJ\Helper\BatchBuilding;
use ILJ\Helper\BatchInfo as HelperBatchInfo;
use ILJ\Helper\Blacklist;
use ILJ\Helper\CustomMetaData;
use ILJ\Helper\IndexAsset;
use ILJ\Helper\LinkBuilding;
use ILJ\Helper\Options as OptionHelper;
use ILJ\Helper\Replacement;
use ILJ\Helper\Statistic;
use ILJ\Posttypes\CustomLinks;
use ILJ\Helper\Cloudflare;
/**
 * The main app
 *
 * Coordinates all steps for the plugin usage
 *
 * @package ILJ\Core
 *
 * @since 1.0.1
 */
class App
{
    private static $instance = null;
    const ILJ_FILTER_BATCH_SIZE = 'ilj_batch_size';
    /**
     * Initializes the construction of the app
     *
     * @static
     * @since  1.0.1
     *
     * @return void
     */
    public static function init()
    {
        if (null !== self::$instance) {
            return;
        }
        self::$instance = new self();
        $last_version = Environment::get('last_version');
        if (ILJ_VERSION != $last_version) {
            ilj_install_db();
            Options::setOptionsDefault();
        }
    }
    protected function __construct()
    {
        $this->initSettings();
        $this->loadIncludes();
        add_action('admin_init', array('\ILJ\Core\Options', 'init'));
        add_action('admin_init', array('\ILJ\Backend\Editor', 'addAssets'));
        add_action('future_to_publish', array($this, 'publishFuturePost'), 99);
        add_action('plugins_loaded', array($this, 'afterPluginsLoad'));
        add_action('after_setup_theme', array($this, 'afterThemesLoad'));
        add_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(new BatchBuilding(), "ilj_set_individual_index_rebuild"), 10, 1);
        add_action(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_OUTGOING, array(new BatchBuilding(), "ilj_individual_index_rebuild_outgoing"), 10, 1);
        add_action(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_INCOMING, array(new BatchBuilding(), "ilj_individual_index_rebuild_incoming"), 10, 1);
        add_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD_INCOMING, array(new BatchBuilding(), "ilj_set_individual_index_rebuild_incoming"), 10, 1);
        add_action(IndexBuilder::ILJ_INITIATE_BATCH_REBUILD, array(new BatchBuilding(), "initiate_ilj_batch_rebuild"), 10);
        add_action(IndexBuilder::ILJ_RUN_SETTING_BATCHED_INDEX_REBUILD, array(new BatchBuilding(), "ilj_run_setting_batched_index_rebuild"), 10);
        add_action(IndexBuilder::ILJ_SET_BATCHED_INDEX_REBUILD, array(new BatchBuilding(), "ilj_set_batched_index_rebuild"), 10, 1);
        add_action(IndexBuilder::ILJ_BUILD_BATCHED_INDEX, array(new BatchBuilding(), "ilj_build_batched_index"), 10, 1);
        add_action(IndexBuilder::ILJ_UPDATE_STATISTICS_INFO, array(new BatchBuilding(), "ilj_update_statistics_info"), 10, 1);
        // Used by blacklist options
        add_action(IndexBuilder::ILJ_DELETE_INDEX_BY_ID, array($this, "ilj_delete_index_by_id"), 10, 1);
        // Used by post/terms in individual index rebuilds
        add_action(IndexBuilder::ILJ_INDIVIDUAL_DELETE_INDEX, array($this, "ilj_individual_delete_index"), 10, 1);
        add_action(IndexBuilder::ILJ_ACTION_AFTER_INDEX_BUILT, function () {
            $batch_build_info = new HelperBatchInfo();
            $batch_build_info->incrementBatchFinished();
            $batch_build_info->updateBatchBuildInfo();
            Statistic::updateStatisticsInfo();
        }, 10);
    }
    /**
     * Initialising all menu and settings related stuff
     *
     * @since 1.0.1
     *
     * @return void
     */
    protected function initSettings()
    {
        add_action('admin_menu', array('\ILJ\Backend\AdminMenu', 'init'));
        add_filter('plugin_action_links_' . ILJ_NAME, array($this, 'addSettingsLink'));
    }
    /**
     * Loads all include files
     *
     * @since 1.0.1
     *
     * @return void
     */
    public function loadIncludes()
    {
        require_once ILJ_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
        $include_files = array('install', 'uninstall');
        foreach ($include_files as $file) {
            include_once ILJ_PATH . 'includes/' . $file . '.php';
        }
    }
    /**
     * Handles post transitions for scheduled posts
     *
     * @since 1.1.5
     * @param object $post
     *
     * @return void
     */
    public function publishFuturePost($post)
    {
        if (!$this->postAffectsIndex($post->ID)) {
            return;
        }
        $whitelisted_post_types = \ILJ\Core\Options::getOption(Whitelist::getKey());
        if (!in_array(get_post_type($post->ID), $whitelisted_post_types)) {
            return;
        }
        $batch_build_info = new HelperBatchInfo();
        if (false === as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post->ID, "type" => "post", "link_type" => LinkType::INCOMING)), HelperBatchInfo::ILJ_ASYNC_GROUP)) {
            $batch_build_info->incrementBatchCounter();
            $batch_build_info->updateBatchBuildInfo();
            as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post->ID, "type" => "post", "link_type" => LinkType::INCOMING)), HelperBatchInfo::ILJ_ASYNC_GROUP);
        }
        if (Blacklist::checkIfBlacklisted("post", $post->ID)) {
            return;
        }
        if (false === as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post->ID, "type" => "post", "link_type" => LinkType::OUTGOING)), HelperBatchInfo::ILJ_ASYNC_GROUP)) {
            $batch_build_info->incrementBatchCounter();
            as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post->ID, "type" => "post", "link_type" => LinkType::OUTGOING)), HelperBatchInfo::ILJ_ASYNC_GROUP);
        }
        $batch_build_info->updateBatchBuildInfo();
    }
    /**
     * Gets called after all plugins are loaded for registering actions and filter
     *
     * @since 1.0.1
     *
     * @return void
     */
    public function afterPluginsLoad()
    {
        Compat::init();
        RatingNotifier::init();
        $this->registerActions();
        $this->registerFilter();
        load_plugin_textdomain('internal-links', false, false);
    }
    /**
     * Gets called after themes are loaded for registering actions and filter
     *
     * @since 1.3.11
     * @return void
     */
    public function afterThemesLoad()
    {
        ThemeCompat::init();
    }
    /**
     * Registers all actions for the plugin
     *
     * @since 1.1.5
     *
     * @return void
     */
    protected function registerActions()
    {
        $capability = current_user_can('administrator');
        add_action('admin_post_' . Options::KEY, array('\ILJ\Helper\Post', 'option_actions'));
        if (!$capability) {
            return;
        }
        // LiteSpeed has a generic problem with terminating cron jobs, this shows the admin notice
        if (false == Options::getOption(Notices::ILJ_DISMISS_ADMIN_WARNING_LITESPEED) && isset($_SERVER['SERVER_SOFTWARE']) && false !== strpos($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed')) {
            if (!is_file(ABSPATH . '.htaccess') || !preg_match('/noabort/i', file_get_contents(ABSPATH . '.htaccess'))) {
                add_action('all_admin_notices', array('\ILJ\Backend\Notices', 'show_admin_warning_litespeed'));
            }
        }
        add_action('wp_enqueue_scripts', array($this, 'enqueueAdminBarScripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminBarScripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueRebuildIndexAssets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_ilj_admin_scripts'));
        add_action('load-post.php', array('\ILJ\Backend\Editor', 'addKeywordMetaBox'));
        add_action(Editor::ILJ_KEYWORD_METABOX_FOOTER_HOOK, array('\ILJ\Backend\Editor', 'render_keyword_metabox_footer'));
        add_action('load-post-new.php', array('\ILJ\Backend\Editor', 'addKeywordMetaBox'));
        add_action('save_post', array('\ILJ\Backend\Editor', 'saveKeywordMeta'), 10, 2);
        add_action('wp_ajax_ilj_search_posts', array('\ILJ\Helper\Ajax', 'searchPostsAction'));
        add_action('wp_ajax_ilj_hide_promo', array('\ILJ\Helper\Ajax', 'hidePromo'));
        add_action('wp_loaded', array('\ILJ\Backend\Column', 'addConfiguredLinksColumn'));
        add_action('wp_ajax_ilj_rating_notification_add', array('\ILJ\Helper\Ajax', 'ratingNotificationAdd'));
        add_action('wp_ajax_ilj_upload_import', array('\ILJ\Helper\Ajax', 'uploadImport'));
        add_action('wp_ajax_ilj_start_import', array('\ILJ\Helper\Ajax', 'startImport'));
        add_action('wp_ajax_ilj_export_settings', array('\ILJ\Helper\Ajax', 'exportSettings'));
        add_action('wp_ajax_ilj_render_link_detail_statistic', array('\ILJ\Helper\Ajax', 'renderLinkDetailStatisticAction'));
        add_action('wp_ajax_ilj_render_links_statistic', array('\ILJ\Helper\Ajax', 'renderLinksStatisticAction'));
        add_action('wp_ajax_ilj_render_anchor_detail_statistic', array('\ILJ\Helper\Ajax', 'renderAnchorDetailStatisticAction'));
        add_action('wp_ajax_ilj_render_anchors_statistic', array('\ILJ\Helper\Ajax', 'renderAnchorsStatistic'));
        add_action('wp_ajax_ilj_rebuild_index', array('\ILJ\Helper\Ajax', 'indexRebuildAction'));
        add_action('wp_ajax_load_link_statistics', array('\ILJ\Helper\Ajax', 'load_link_statistics'));
        add_action('wp_ajax_ilj_cancel_schedules', array('\ILJ\Helper\Ajax', 'cancel_all_schedules'));
        add_action('wp_ajax_ilj_fix_database_collation', array('\ILJ\Helper\Ajax', 'fix_database_collation'));
        add_action('wp_ajax_ilj_clear_all_transient', array('\ILJ\Helper\Ajax', 'clear_all_transient'));
        add_action('wp_ajax_ilj_clear_single_transient', array('\ILJ\Helper\Ajax', 'clear_single_transient'));
        add_action('wp_ajax_load_anchor_statistics_chunk', array('\ILJ\Helper\Ajax', 'load_anchor_statistics_chunk_callback'));
        add_action('updated_option', array('ILJ\Helper\Options', 'updateOptionIndexRebuild'), 10, 3);
        add_action('wp_ajax_ilj_dismiss_admin_warning_litespeed', array('\ILJ\Backend\Notices', 'dismiss_admin_warning_litespeed'));
        $hide_status_bar = Options::getOption(\ILJ\Core\Options\HideStatusBar::getKey());
        if (!$hide_status_bar) {
            add_action('admin_bar_menu', array('\ILJ\Backend\AdminBar', 'addLink'), 999);
        }
        add_action('wp_ajax_ilj_render_batch_info', array('\ILJ\Helper\Ajax', 'renderBatchInfo'));
        $this->addPostIndexTrigger();
        add_action(CustomFieldsToLinkPost::ILJ_ACTION_ADD_PRO_FEATURES, function () {
            ?>
			<li><span><?php 
            esc_html_e('Activate custom fields', 'internal-links');
            ?></span>:
				<?php 
            echo wp_kses(__('Maximize compatibility with builders, themes and plugins enabling linking from <strong>custom fields</strong>.', 'internal-links'), array('strong' => array()));
            ?>
			</li>
			<?php 
        }, 10);
        add_filter('action_scheduler_queue_runner_time_limit', array('\ILJ\Helper\Cloudflare', 'check_header_for_timelimit_adjust'));
        add_action('action_scheduler_before_process_queue', function () {
            BatchBuilding::add_scheduler_batch_size_setting();
        });
    }
    /**
     * Enqueue the admin bar scripts and style
     *
     * @return void
     */
    public function enqueueAdminBarScripts()
    {
        \ILJ\Helper\Loader::register_script('ilj-link-index-status-func-script', ILJ_URL . 'admin/js/ilj-link-index-status-func.js', array('jquery'), ILJ_VERSION);
        $this->localize_ilj_ajax_object('ilj-link-index-status-func-script');
        $hide_status_bar = Options::getOption(\ILJ\Core\Options\HideStatusBar::getKey());
        if (!$hide_status_bar) {
            \ILJ\Helper\Loader::enqueue_style('ilj_admin_menu_bar_style', ILJ_URL . 'admin/css/ilj_admin_menu_bar.css', array(), ILJ_VERSION);
            \ILJ\Helper\Loader::register_script('ilj_admin_menu_bar_script', ILJ_URL . 'admin/js/ilj_admin_menu_bar.js', array('ilj-link-index-status-func-script'), ILJ_VERSION);
            \ILJ\Helper\Loader::enqueue_script('ilj_admin_menu_bar_script');
            wp_localize_script('ilj_admin_menu_bar_script', 'ilj_ajax_menu_bar_script', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ilj-general-nonce')));
        }
    }
    /**
     * Registers all assets for the frontend rebuild notification
     *
     * @return void
     * @since  2.0.0
     */
    public function enqueueRebuildIndexAssets()
    {
        $current_screen = get_current_screen();
        if ('toplevel_page_internal_link_juicer' != $current_screen->base) {
            return;
        }
        \ILJ\Helper\Loader::register_script('ilj_index_rebuild_button', ILJ_URL . 'admin/js/ilj_ajax_index_rebuild.js', array('ilj-link-index-status-func-script'), ILJ_VERSION);
        \ILJ\Helper\Loader::enqueue_script('ilj_index_rebuild_button');
    }
    /**
     * Enqueue the admin scripts and styles
     *
     * @return void
     */
    public function enqueue_ilj_admin_scripts()
    {
        \ILJ\Helper\Loader::register_script('ilj_admin_script', ILJ_URL . 'admin/js/ilj_admin_script.js', array('jquery'), ILJ_VERSION);
        \ILJ\Helper\Loader::enqueue_script('ilj_admin_script');
        $this->localize_ilj_ajax_object('ilj_admin_script');
    }
    /**
     * Localize ilj_ajax_object to $handle enqueue script.
     *
     * @param String $handle enqueue script handle.
     * @return void
     */
    private function localize_ilj_ajax_object($handle)
    {
        wp_localize_script($handle, 'ilj_ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ilj-general-nonce')));
    }
    /**
     * Delete incoming and outgoing link index by ID
     *
     * @param  array $data
     * @return void
     */
    public function ilj_delete_index_by_id($data)
    {
        Linkindex::delete_link_by_id($data['id'], $data['type']);
        $batch_build_info = new HelperBatchInfo();
        $batch_build_info->updateBatchBuildInfo();
    }
    /**
     * Delete outgoing link index by ID directly from current linkindex
     *
     * @param  array $data
     * @return void
     */
    public function ilj_individual_delete_index($data)
    {
        Linkindex::delete_link_from($data['id'], $data['type']);
    }
    /**
     * Triggers all actions for automatic index building mode.
     *
     * @since  1.1.0
     * @return void
     */
    protected function addPostIndexTrigger()
    {
        add_action('post_updated', function ($post_id) {
            Transient_Cache::delete_cache_for_content($post_id, 'post');
        }, 20, 1);
        add_action('delete_post', function ($post_id) {
            Transient_Cache::delete_cache_for_content($post_id, 'post');
        }, 10, 1);
        add_action(Editor::ILJ_ACTION_AFTER_KEYWORDS_UPDATE, function ($id, $type, $status) {
            Statistic::count_all_configured_keywords();
            if ('publish' == $status) {
                $batch_build_info = new HelperBatchInfo();
                if (false === as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $id, "type" => $type, "link_type" => LinkType::INCOMING)), HelperBatchInfo::ILJ_ASYNC_GROUP)) {
                    $batch_build_info->incrementBatchCounter();
                    $batch_build_info->updateBatchBuildInfo();
                    as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $id, "type" => $type, "link_type" => LinkType::INCOMING)), HelperBatchInfo::ILJ_ASYNC_GROUP);
                }
            }
        }, 100, 3);
        add_action('post_updated', function ($post_id, $post_after, $post_before) {
            if ($post_after->post_content != $post_before->post_content) {
                $whitelisted_post_types = \ILJ\Core\Options::getOption(Whitelist::getKey());
                if (!empty($whitelisted_post_types)) {
                    if (!in_array(get_post_type($post_id), $whitelisted_post_types)) {
                        return;
                    }
                    if (Blacklist::checkIfBlacklisted("post", $post_id)) {
                        return;
                    }
                    $batch_build_info = new HelperBatchInfo();
                    if (false === as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post_id, "type" => "post", "link_type" => LinkType::OUTGOING)), HelperBatchInfo::ILJ_ASYNC_GROUP)) {
                        $batch_build_info->incrementBatchCounter();
                        as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post_id, "type" => "post", "link_type" => LinkType::OUTGOING)), HelperBatchInfo::ILJ_ASYNC_GROUP);
                    }
                    $batch_build_info->updateBatchBuildInfo();
                }
            }
        }, 20, 3);
        // rebuild index after keyword meta got updated on gutenberg editor:
        add_action('updated_post_meta', function ($meta_id, $post_id, $meta_key) {
            if (!is_admin() || !function_exists('get_current_screen')) {
                return;
            }
            $batch_build_info = new HelperBatchInfo();
            $incoming_metas = array(Editor::ILJ_META_KEY_LIMITINCOMINGLINKS, Editor::ILJ_META_KEY_MAXINCOMINGLINKS);
            $outgoing_metas = array(Editor::ILJ_META_KEY_BLACKLISTDEFINITION, Editor::ILJ_META_KEY_LIMITLINKSPERPARAGRAPH, Editor::ILJ_META_KEY_LINKSPERPARAGRAPH, Editor::ILJ_META_KEY_LIMITOUTGOINGLINKS, Editor::ILJ_META_KEY_MAXOUTGOINGLINKS);
            $whitelisted_post_types = \ILJ\Core\Options::getOption(Whitelist::getKey());
            if (!empty($whitelisted_post_types)) {
                if (!in_array($meta_key, $outgoing_metas) && !in_array($meta_key, $incoming_metas)) {
                    return;
                }
                if (!in_array(get_post_type($post_id), $whitelisted_post_types)) {
                    return;
                }
                if (!in_array($meta_key, $incoming_metas)) {
                    if (in_array($meta_key, $outgoing_metas)) {
                        if (Blacklist::checkIfBlacklisted("post", $post_id)) {
                            return;
                        }
                        if (false === as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post_id, "type" => "post", "link_type" => LinkType::OUTGOING)), HelperBatchInfo::ILJ_ASYNC_GROUP)) {
                            $batch_build_info->incrementBatchCounter();
                            as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post_id, "type" => "post", "link_type" => LinkType::OUTGOING)), HelperBatchInfo::ILJ_ASYNC_GROUP);
                        }
                        $batch_build_info->updateBatchBuildInfo();
                        return;
                    } else {
                        return;
                    }
                }
                if (!$this->postAffectsIndex($post_id)) {
                    return;
                }
                if (in_array($meta_key, $incoming_metas)) {
                    if (false === as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post_id, "type" => "post", "link_type" => LinkType::INCOMING)), HelperBatchInfo::ILJ_ASYNC_GROUP)) {
                        $batch_build_info->incrementBatchCounter();
                        $batch_build_info->updateBatchBuildInfo();
                        as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post_id, "type" => "post", "link_type" => LinkType::INCOMING)), HelperBatchInfo::ILJ_ASYNC_GROUP);
                    }
                }
            }
        }, 30, 3);
        add_action(Linkindex::ILJ_ACTION_AFTER_DELETE_LINKINDEX, function () {
            Statistic::updateStatisticsInfo();
        }, 10);
        add_action('transition_post_status', function ($new_status, $old_status, $post) {
            if ('publish' == $old_status && 'publish' == $new_status || 'new' == $old_status && 'publish' == $new_status || 'draft' == $old_status && 'draft' == $new_status) {
                return;
            }
            $whitelisted_post_types = \ILJ\Core\Options::getOption(Whitelist::getKey());
            if (!is_array($whitelisted_post_types)) {
                $whitelisted_post_types = array();
            }
            if (!empty($whitelisted_post_types)) {
                $batch_build_info = new HelperBatchInfo();
                if (!in_array(get_post_type($post->ID), $whitelisted_post_types)) {
                    return;
                }
                $type = "post";
                if ('trash' == $new_status) {
                    $batch_build_info->incrementBatchCounter();
                    $updated = $batch_build_info->updateBatchBuildInfo();
                    if ($updated) {
                        if (false === as_has_scheduled_action(IndexBuilder::ILJ_DELETE_INDEX_BY_ID, array(array("id" => $post->ID, "type" => $type)), HelperBatchInfo::ILJ_ASYNC_GROUP)) {
                            as_enqueue_async_action(IndexBuilder::ILJ_DELETE_INDEX_BY_ID, array(array("id" => $post->ID, "type" => $type)), HelperBatchInfo::ILJ_ASYNC_GROUP);
                        }
                    }
                    return;
                }
                if ('publish' == $old_status && 'draft' == $new_status || 'trash' == $old_status && 'draft' == $new_status) {
                    if (Blacklist::checkIfBlacklisted("post", $post->ID)) {
                        return;
                    }
                    if ('post' == $type) {
                        if (false === as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post->ID, "type" => "post", "link_type" => LinkType::OUTGOING)), HelperBatchInfo::ILJ_ASYNC_GROUP)) {
                            $batch_build_info->incrementBatchCounter();
                            as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post->ID, "type" => "post", "link_type" => LinkType::OUTGOING)), HelperBatchInfo::ILJ_ASYNC_GROUP);
                        }
                        $batch_build_info->updateBatchBuildInfo();
                    }
                    return;
                }
                if ('trash' == $old_status && 'publish' == $new_status) {
                    if (Blacklist::checkIfBlacklisted("post", $post->ID)) {
                        return;
                    }
                    if ('post' == $type) {
                        if (false === as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post->ID, "type" => "post", "link_type" => LinkType::OUTGOING)), HelperBatchInfo::ILJ_ASYNC_GROUP)) {
                            $batch_build_info->incrementBatchCounter();
                            as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post->ID, "type" => "post", "link_type" => LinkType::OUTGOING)), HelperBatchInfo::ILJ_ASYNC_GROUP);
                        }
                        $batch_build_info->updateBatchBuildInfo();
                    }
                }
                if ('draft' == $new_status) {
                    return;
                }
                if (false === as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post->ID, "type" => "post", "link_type" => LinkType::INCOMING)), HelperBatchInfo::ILJ_ASYNC_GROUP)) {
                    $batch_build_info->incrementBatchCounter();
                    $batch_build_info->updateBatchBuildInfo();
                    as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array("id" => $post->ID, "type" => "post", "link_type" => LinkType::INCOMING)), HelperBatchInfo::ILJ_ASYNC_GROUP);
                }
            }
        }, 40, 3);
    }
    /**
     * Registers plugin relevant filters
     *
     * @since 1.0.0
     * @return void
     */
    public function registerFilter()
    {
        add_filter('the_content', array(new LinkBuilding(), 'linkContent'), PHP_INT_MAX);
        add_filter('ilj_get_the_content', array(new LinkBuilding(), 'linkContent'), 99, 1);
        add_filter('ilj_get_the_content_term', array(new LinkBuilding(), 'link_term'), 99, 2);
        $tag_exclusions = Options::getOption(\ILJ\Core\Options\NoLinkTags::getKey());
        if (is_array($tag_exclusions) && count($tag_exclusions)) {
            add_filter(Replacement::ILJ_FILTER_EXCLUDE_TEXT_PARTS, function ($search_parts) use ($tag_exclusions) {
                foreach ($tag_exclusions as $tag_exclusion) {
                    $regex = TagExclusion::getRegex($tag_exclusion);
                    if ($regex) {
                        $search_parts[] = $regex;
                    }
                }
                return $search_parts;
            });
        }
        \ILJ\ilj_fs()->add_filter('reshow_trial_after_every_n_sec', function () {
            // 40 days in sec.
            return 60 * 24 * 60 * 60;
        });
        \ILJ\ilj_fs()->add_filter('show_first_trial_after_n_sec', function () {
            // 3 days in sec.
            return 3 * 24 * 60 * 60;
        });
        \ILJ\ilj_fs()->add_filter('show_affiliate_program_notice', function () {
            return false;
        });
    }
    /**
     * Adds a link to the plugins settings page on plugins overview
     *
     * @since 1.0.0
     *
     * @param  array $links All links that get displayed
     * @return array
     */
    public function addSettingsLink($links)
    {
        $settings_link = '<a href="admin.php?page=' . AdminMenu::ILJ_MENUPAGE_SLUG . '-' . Settings::ILJ_MENUPAGE_SETTINGS_SLUG . '">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    /**
     * Checks if a (changed) post affects the index creation
     *
     * @since  1.2.0
     * @param  int $post_id The ID of the post
     * @return bool
     */
    protected function postAffectsIndex($post_id)
    {
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_status, array('publish', 'trash'))) {
            return false;
        }
        return true;
    }
}