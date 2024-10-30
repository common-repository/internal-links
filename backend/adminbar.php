<?php

namespace ILJ\Backend;

use ILJ\Core\Options;
use ILJ\Core\Options\CacheToggleBtnSwitch;
use ILJ\Helper\BatchInfo as HelperBatchInfo;
/**
 * Admin bar
 *
 * Manages everything related to the admin bar section
 *
 * @package ILJ\Backend
 * @since   2.0.0
 */
class AdminBar
{
    /**
     * Add a link to the admin toolbar
     *
     * @param \WP_Admin_Bar $admin_bar
     *
     * @return void
     * @since  2.0.0
     */
    public static function addLink($admin_bar)
    {
        $batch_build_info = new HelperBatchInfo();
        $batch_percentage = $batch_build_info->getBatchPercentage();
        $status = $batch_build_info->getBatchStatus();
        $args = array('id' => 'ilj', 'title' => '<span class="ilj_icon" aria-hidden="true"></span>', 'href' => add_query_arg(array('page' => AdminMenu::ILJ_MENUPAGE_SLUG), admin_url('admin.php')), 'meta' => array('html' => '
                <a class="ilj_admin_bar_link" style="height: 0px;" href = "' . add_query_arg(array('page' => AdminMenu::ILJ_MENUPAGE_SLUG), admin_url('admin.php')) . '">
                    <div class="ilj_admin_bar_container">
                        <div class="ilj_bar_title_container"> Linkindex: <span id="ilj_batch_progress">' . $batch_percentage . '%</span></div>
                        <div id="progressbar" class="ilj_progress_bar">
                            <div style="width:' . $batch_percentage . '%"></div>
                        </div>
                    </div>
                </a>'));
        $admin_bar->add_node($args);
        $cache_option = Options::getOption(CacheToggleBtnSwitch::getKey());
        if ($cache_option) {
            // Disable in version 2.23.5 due to conflicts with other plugins
            self::add_cache_menu_items($admin_bar);
        }
        $args = array('parent' => 'ilj', 'id' => 'ilj-status', 'title' => '<div class="ilj-build-title"><strong>Status:</strong> <span  id="ilj_batch_status">' . HelperBatchInfo::translateBatchStatus($status) . '</span></div>', 'meta' => array('html' => '
				<hr class="ilj-build-seperate" />
                <div class="ilj-build-info">
                	<p>
                		<span class="dashicons ilj_info_icon"></span>
                		' . __('We build your internal links in the background.', 'internal-links') . ' ' . __('As soon as 100% is reached, your new links will be visible in the frontend.', 'internal-links') . '
                	</p>
                </div>
                '));
        $admin_bar->add_node($args);
    }
    /**
     * Add admin bar menu items for deleting transient
     *
     * @param \WP_Admin_Bar $admin_bar
     *
     * @return void
     */
    private static function add_cache_menu_items($admin_bar)
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $args = array('parent' => 'ilj', 'id' => 'ilj-clear-all-transients', 'title' => __('Delete all caches', 'internal-links'), 'href' => wp_nonce_url(admin_url('admin-ajax.php?action=ilj_clear_all_transient'), 'ilj_clear_all_transient'));
        $admin_bar->add_node($args);
        // On single post/ category pages this option should be displayed.
        if (is_category() || is_tag() || is_tax() || is_singular()) {
            $id = get_queried_object_id();
            $type = is_singular() ? 'post' : 'term';
            $args = array('parent' => 'ilj', 'id' => 'ilj-clear-single-transient', 'title' => ('post' === $type) ? sprintf(__('Delete this %s cache', 'internal-links'), get_post_type($id)) : __('Delete this term cache', 'internal-links'), 'href' => admin_url(sprintf('admin-ajax.php?action=%s&_wpnonce=%s&ilj_transient_id=%d&ilj_transient_type=%s', 'ilj_clear_single_transient', wp_create_nonce('ilj_clear_single_transient'), $id, $type)));
            $admin_bar->add_node($args);
        }
    }
}