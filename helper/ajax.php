<?php

namespace ILJ\Helper;

use ILJ\Backend\AdminMenu;
use ILJ\Backend\BatchInfo;
use ILJ\Backend\Environment;
use ILJ\Backend\MenuPage\Tools;
use ILJ\Backend\User;
use ILJ\Cache\Transient_Cache;
use ILJ\Core\IndexBuilder;
use ILJ\Core\Options as CoreOptions;
use ILJ\Data\Content;
use ILJ\Database\DatabaseCollation;
use ILJ\Database\Postmeta;
use ILJ\Enumeration\Content_Type;
use ILJ\Helper\IndexAsset;
use ILJ\Database\Linkindex;
use ILJ\Helper\BatchInfo as HelperBatchInfo;
use ILJ\Statistics\Link;
use ILJ\Type\KeywordList;
/**
 * Ajax toolset
 *
 * Methods for handling AJAX requests
 *
 * @package ILJ\Helper
 *
 * @since 1.0.0
 */
class Ajax
{
    const ILJ_FILTER_AJAX_SEARCH_POSTS = 'ilj_ajax_search_posts';
    const ILJ_FILTER_AJAX_SEARCH_TERMS = 'ilj_ajax_search_terms';
    private static $cached_html = null;
    /**
     * Searches the posts for a given phrase
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function searchPostsAction()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-search-posts-action')) {
            die;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!isset($_POST['search']) && !isset($_POST['per_page']) && !isset($_POST['page'])) {
            wp_die();
        }
        $search = sanitize_text_field($_POST['search']);
        $per_page = (int) $_POST['per_page'];
        $page = (int) $_POST['page'];
        $args = array('s' => $search, 'posts_per_page' => $per_page, 'paged' => $page);
        $query = new \WP_Query($args);
        $data = array();
        foreach ($query->posts as $post) {
            $data[] = array('id' => $post->ID, 'text' => $post->post_title);
        }
        /**
         * Filters the output of ajax post search
         *
         * @since 1.1.6
         *
         * @param object $data The return data (found posts)
         * @param array  $args The arguments for the post query
         */
        $data = apply_filters(self::ILJ_FILTER_AJAX_SEARCH_POSTS, $data, $args);
        wp_send_json($data);
        wp_die();
    }
    /**
     * Rebuilds the link index
     *
     * @since 2.0.0
     *
     * @return void
     */
    public static function indexRebuildAction()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-dashboard')) {
            die;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        try {
            do_action(IndexBuilder::ILJ_INITIATE_BATCH_REBUILD);
            $response = array('status' => 'success', 'message' => sprintf('<p class="message">' . __('Index rebuild successfully scheduled.', 'internal-links') . '</p>'));
        } catch (\Exception $e) {
            $response = array('status' => 'error', 'message' => sprintf('<p class="message">' . __('There has been an error in initiating the index rebuild.', 'internal-links') . '</p>'));
        }
        wp_send_json($response);
        wp_die();
    }
    /**
     * Renders the statistics for the links
     *
     * @since 1.2.5
     *
     * @param int $start_count Determine what index to start counting
     * @param int $chunk_size  The size of the batch to loop into
     * @return String
     */
    public static function render_links_statistic_action($start_count, $chunk_size)
    {
        $statistics = Statistic::getLinkStatistics();
        if (!count($statistics) && 0 == $start_count) {
            return;
        } elseif (!count($statistics) && 0 < $start_count) {
            return null;
        }
        for ($i = $start_count; $i < min($start_count + $chunk_size, count($statistics)); $i++) {
            $statistic = $statistics[$i];
            $asset_data = IndexAsset::getMeta($statistic->asset_id, $statistic->asset_type);
            if (!$asset_data) {
                continue;
            }
            $edit_link = sprintf('<a href="%s" class="tip" title="' . __('Edit', 'internal-links') . '">%s</a>', $asset_data->url_edit, '<span class="dashicons dashicons-edit"></span>');
            $asset_link = sprintf('<a href="%s" class="tip" title="' . __('Open', 'internal-links') . '" target="_blank" rel="noopener">%s</a>', $asset_data->url, '<span class="dashicons dashicons-external"></span>');
            $elements_to = $statistic->elements_to ? '<a title="' . __('Show incoming links', 'internal-links') . '" class="tip ilj-statistic-detail" data-id="' . $statistic->asset_id . '" data-type="' . $statistic->asset_type . '" data-direction="to">' . $statistic->elements_to . '</a>' : '-';
            $elements_from = $statistic->elements_from ? '<a title="' . __('Show outgoing links', 'internal-links') . '" class="tip ilj-statistic-detail" data-id="' . $statistic->asset_id . '" data-type="' . $statistic->asset_type . '" data-direction="from">' . $statistic->elements_from . '</a>' : '-';
            $type = IndexAsset::getDetailedType($statistic->asset_id, $statistic->asset_type);
            self::$cached_html .= '<tr>';
            self::$cached_html .= '<td class="asset-title">' . $asset_data->title . '</td>';
            self::$cached_html .= '<td>' . Statistic::getConfiguredKeywordsCountForAsset($statistic->asset_id, $statistic->asset_type) . '</td>';
            self::$cached_html .= '<td class="type" data-search="' . $statistic->asset_type . ';' . $type . '"><span data-type="' . $statistic->asset_type . '">' . $type . '</span></td>';
            self::$cached_html .= '<td>' . $elements_to . '</td>';
            self::$cached_html .= '<td>' . $elements_from . '</td>';
            self::$cached_html .= '<td>' . $edit_link . ' ' . $asset_link . '</td>';
            self::$cached_html .= '</tr>';
        }
        echo self::$cached_html;
    }
    /**
     * Renders the statistics for the anchor texts
     *
     * @since 1.2.5
     *
     * @param  array $request Data of the datatable form send to server for populating datagrid
     * @return array
     */
    public static function render_anchors_statistic($request)
    {
        $record_data = array();
        $statistics = Statistic::get_anchor_statistics($request);
        foreach ($statistics['data'] as $statistic) {
            $record_data[] = array(esc_html($statistic->anchor), intval(strlen($statistic->anchor)), intval(count(explode(' ', $statistic->anchor))), '<a title="' . __('Show usage', 'internal-links') . '" class="tip ilj-statistic-detail" data-anchor="' . esc_attr($statistic->anchor) . '">' . esc_html($statistic->frequency) . '</a>');
        }
        $statistics['data'] = $record_data;
        return $statistics;
    }
    /**
     * Retrieves link data for a specific asset by a given direction (in- or outgoing)
     *
     * @since 1.1.0
     *
     * @return void
     */
    public static function renderLinkDetailStatisticAction()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-dashboard') || !current_user_can('manage_options')) {
            die;
        }
        if (!isset($_POST['id']) || !isset($_POST['type']) || !isset($_POST['direction'])) {
            wp_die();
        }
        $id = (int) $_POST['id'];
        $type = $_POST['type'];
        $direction = $_POST['direction'];
        $directive_links = Linkindex::getDirectiveLinks($id, $type, $direction);
        if (!count($directive_links)) {
            wp_die();
        }
        $direction_header = '';
        if ('from' == $direction) {
            $direction_header = __('Target', 'internal-links');
        } elseif ('to' == $direction) {
            $direction_header = __('Source', 'internal-links');
        }
        $data = '<table class="ilj-statistic-detail-table display"><thead><tr><th>' . $direction_header . '</th><th>' . __('Type', 'internal-links') . '</th><th>' . __('Anchor text', 'internal-links') . '</th></tr></thead>';
        $data .= '<tbody>';
        $row_counter = 0;
        for ($i = 0; $i < count($directive_links); $i++) {
            $directive_link = $directive_links[$i];
            if ($i >= 3) {
                break;
            }
            if (!property_exists($directive_link, 'link_' . $direction) || !property_exists($directive_link, 'type_' . $direction) || !property_exists($directive_link, 'anchor')) {
                continue;
            }
            if ('from' == $direction) {
                $reverse_direction = 'to';
            } elseif ('to' == $direction) {
                $reverse_direction = 'from';
            }
            $type = IndexAsset::getDetailedType($directive_link->{'link_' . $reverse_direction}, $directive_link->{'type_' . $reverse_direction});
            $asset_data = IndexAsset::getMeta($directive_link->{'link_' . $reverse_direction}, $directive_link->{'type_' . $reverse_direction});
            if (!$asset_data) {
                continue;
            }
            $data .= '<tr class="' . ((0 === $row_counter % 2) ? 'even' : 'odd') . '"><td><a href="' . $asset_data->url . '" rel="noopener" target="_blank">' . $asset_data->title . '</a></td><td class="type"><span data-type="' . $directive_link->{'type_' . $reverse_direction} . '">' . $type . '</span></td><td>' . $directive_link->anchor . '</td></tr>';
            $row_counter++;
        }
        $data .= '</tbody>';
        $data .= '</table>';
        if (count($directive_links) > 3) {
            $data .= '<div class="ilj-statistic-detail-hidden spacer">';
            $data .= '	<div class="more"><span class="dashicons dashicons-lock"></span> and ' . (count($directive_links) - 3) . ' more</div>';
            $data .= '  <div class="upgrade">';
            $data .= '      <p>' . __('With the free basic version you can view the first 3 links in the detail view.', 'internal-links') . '</p>';
            $data .= '      <a href="' . get_admin_url(null, 'admin.php?page=' . AdminMenu::ILJ_MENUPAGE_SLUG . '-pricing') . '"><span class="dashicons dashicons-unlock"></span> ' . __('Upgrade to Pro and view all', 'internal-links') . '</a>';
            $data .= '  </div>';
            $data .= '</div>';
        }
        echo $data;
        wp_die();
    }
    /**
     * Renders link details for a specific anchor text
     *
     * @since  1.1.0
     * @return void
     */
    public static function renderAnchorDetailStatisticAction()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-dashboard') || !current_user_can('manage_options')) {
            die;
        }
        if (!isset($_POST['anchor'])) {
            wp_die();
        }
        $data = '';
        $data = '<div class="ilj-statistic-detail-hidden">';
        $data .= '  <div class="upgrade">';
        $data .= '      <p>' . __('The detail view for anchor texts is part of the Pro version.', 'internal-links') . '</p>';
        $data .= '      <a href="' . get_admin_url(null, 'admin.php?page=' . AdminMenu::ILJ_MENUPAGE_SLUG . '-pricing') . '"><span class="dashicons dashicons-unlock"></span> ' . __('Upgrade to Pro and view all', 'internal-links') . '</a>';
        $data .= '  </div>';
        $data .= '</div>';
        echo $data;
        wp_die();
    }
    /**
     * Handles interactions with the rating notification
     *
     * @since  1.2.0
     * @return void
     */
    public static function ratingNotificationAdd()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-general-nonce')) {
            die;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!isset($_POST['days'])) {
            wp_die();
        }
        $days = (int) $_POST['days'];
        if (-1 === $days) {
            User::unsetRatingNotification();
            wp_die();
        }
        $days_string = sprintf('+%d days', $days);
        $notification_base_date = new \DateTime('now');
        $notification_base_date->modify($days_string);
        User::setRatingNotificationBaseDate($notification_base_date);
        wp_die();
    }
    /**
     * Hides the promo box in the sidebar
     *
     * @since  1.1.2
     * @return void
     */
    public static function hidePromo()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-general-nonce')) {
            die;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        User::update('hide_promo', true);
        wp_die();
    }
    /**
     * Handles upload of import files
     *
     * @since  1.2.0
     * @return void
     */
    public static function uploadImport()
    {
        if (!isset($_POST['nonce']) || !isset($_POST['file_type'])) {
            wp_send_json_error(null, 400);
        }
        $nonce = $_POST['nonce'];
        $file_type = $_POST['file_type'];
        if (!in_array($file_type, array('settings', 'keywords'))) {
            wp_send_json_error(null, 400);
        }
        if (!wp_verify_nonce($nonce, 'ilj-tools') || !current_user_can('manage_options')) {
            wp_send_json_error(null, 400);
        }
        $uploaded_file = $_FILES['file_data'];
        $upload_overrides = array('test_form' => false, 'test_type' => false);
        if ('keywords' == $file_type) {
            $uploaded_file['name'] = uniqid(rand(), true) . '.csv';
        }
        $file_upload = wp_handle_upload($uploaded_file, $upload_overrides);
        if (!$file_upload || isset($file_upload['error'])) {
            wp_send_json_error(__('Your web host does not allow file uploads.', 'internal-links') . ' ' . __('Please fix the problem and try again.', 'internal-links'), 400);
        }
        switch ($file_type) {
            case 'settings':
                $file_content = file_get_contents($file_upload['file']);
                unlink($file_upload['file']);
                $file_json = Encoding::jsonToArray($file_content);
                if (false === $file_json) {
                    wp_send_json_error(null, 400);
                }
                set_transient('ilj_upload_settings', $file_json, HOUR_IN_SECONDS * 12);
                break;
            case 'keywords':
                set_transient('ilj_upload_keywords', $file_upload, HOUR_IN_SECONDS * 12);
                break;
        }
        wp_send_json_success(null, 200);
    }
    /**
     * Initiates the import of already uploaded and prepared files
     *
     * @since  1.2.0
     * @return void
     */
    public static function startImport()
    {
        if (!isset($_POST['nonce']) || !isset($_POST['file_type'])) {
            wp_send_json_error(null, 400);
        }
        $nonce = $_POST['nonce'];
        $file_type = $_POST['file_type'];
        if (!in_array($file_type, array('settings', 'keywords'))) {
            wp_send_json_error(null, 400);
        }
        if (!wp_verify_nonce($nonce, 'ilj-tools') || !current_user_can('manage_options')) {
            wp_send_json_error(null, 400);
        }
        $upload_transient = get_transient('ilj_upload_' . $file_type);
        if (!$upload_transient) {
            wp_send_json_error(__('Timeout.', 'internal-links') . ' ' . __('Please try to upload again', 'internal-links'), 400);
        }
        switch ($file_type) {
            case 'settings':
                $import_count = CoreOptions::importOptions($upload_transient);
                break;
            case 'keywords':
                if (!isset($upload_transient['file']) || !file_exists($upload_transient['file'])) {
                    wp_send_json_error(null, 400);
                }
                $import_count = Keyword::importKeywordsFromFile($upload_transient['file']);
                unlink($upload_transient['file']);
                break;
        }
        if (0 === $import_count) {
            wp_send_json_error(__('Nothing to import or no data for import found.', 'internal-links'), 400);
        }
        do_action(IndexBuilder::ILJ_INITIATE_BATCH_REBUILD);
        delete_transient('ilj_upload_' . $file_type);
        wp_send_json_success(null, 200);
    }
    /**
     * Renders the Status and info of the Batch Build
     *
     * @since 1.3.10
     *
     * @return void
     */
    public static function renderBatchInfo()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-general-nonce')) {
            die;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        $batch_build_info = new HelperBatchInfo();
        $info = $batch_build_info->getBatchInfo();
        wp_send_json($info);
        wp_die();
    }
    /**
     * Handles clear all transient ajax action
     *
     * @return void
     */
    public static function clear_all_transient()
    {
        if (!check_admin_referer('ilj_clear_all_transient') || !current_user_can('manage_options')) {
            return;
        }
        Transient_Cache::delete_all();
        \ILJ\ilj_fs()->add_sticky_admin_message(__('The Caches were cleared.', 'internal-links'), 'ilj_clear_all_transient_notice');
        wp_safe_redirect(wp_get_referer());
        die;
    }
    /**
     * Loads chunks of links statistics data to table
     *
     * @since 2.23.4
     *
     * @return array
     */
    public static function load_link_statistics()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-dashboard') || !current_user_can('manage_options')) {
            die;
        }
        $table_columns = array('title', 'keywords_count', 'type', 'incoming_links', 'outgoing_links');
        $sort_by = 'title';
        if (isset($_REQUEST['order'][0]['column'])) {
            $sort_column_index = intval($_REQUEST['order'][0]['column']);
            if ($sort_column_index < count($table_columns)) {
                $sort_by = $table_columns[$sort_column_index];
            }
        }
        $sort_direction = 'ASC';
        if (isset($_REQUEST['order'][0]['dir']) && 'desc' === $_REQUEST['order'][0]['dir']) {
            $sort_direction = 'DESC';
        }
        $link_statistics = new Link(array('sort_by' => $sort_by, 'sort_direction' => $sort_direction, 'limit' => intval($_REQUEST['length']), 'offset' => intval($_REQUEST['start']), 'search' => (isset($_REQUEST['search']['value']) && is_string($_REQUEST['search']['value'])) ? $_REQUEST['search']['value'] : '', 'main_types' => (isset($_REQUEST['main_types']) && !empty($_REQUEST['main_types']) && is_string($_REQUEST['main_types'])) ? explode(',', $_REQUEST['main_types']) : array(), 'sub_types' => (isset($_REQUEST['sub_types']) && !empty($_REQUEST['sub_types']) && is_string($_REQUEST['sub_types'])) ? explode(',', $_REQUEST['sub_types']) : array()));
        $total = $link_statistics->get_total();
        /**
         * `recordsTotal`, `recordsFiltered` are standard property names for pagination in data tables
         *
         * @see https://datatables.net/examples/data_sources/server_side
         */
        echo json_encode(array('recordsTotal' => $total, 'recordsFiltered' => $link_statistics->get_filtered_results_count(), 'data' => $link_statistics->get_statistics(), 'draw' => isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 0));
        die;
    }
    /**
     * Handles clear single transient ajax action
     *
     * @return void
     */
    public static function clear_single_transient()
    {
        if (!check_admin_referer('ilj_clear_single_transient') || !current_user_can('manage_options')) {
            return;
        }
        $id = isset($_REQUEST['ilj_transient_id']) ? sanitize_text_field($_REQUEST['ilj_transient_id']) : '';
        $type = isset($_REQUEST['ilj_transient_type']) ? sanitize_text_field($_REQUEST['ilj_transient_type']) : '';
        if (!$id || !in_array($type, array('post', 'term'), true)) {
            return;
        }
        Transient_Cache::delete_cache_for_content(intval($id), $type);
        /* Dont print notice because the ui using this flag will have inbuilt feedback instead of printing the notice */
        if (!isset($_REQUEST['ilj_skip_notice'])) {
            $message = ('post' === $type) ? sprintf(__('The cache for the %s has been cleared.', 'internal-links'), get_post_type($id)) : __('The cache for the term has been cleared.', 'internal-links');
            \ILJ\ilj_fs()->add_sticky_admin_message($message, 'ilj_clear_single_transient_notice');
            wp_safe_redirect(wp_get_referer());
        }
        die;
    }
    /**
     * Loads chunks of anchor statistics data to table
     *
     * @since 2.23.4
     *
     * @return void
     */
    public static function load_anchor_statistics_chunk_callback()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-dashboard')) {
            die;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        $request = $_POST;
        $html_chunk = Ajax::render_anchors_statistic($request);
        echo json_encode($html_chunk);
        die;
    }
    /**
     * Cancels the index rebuild schedules
     *
     * @since 2.23.5
     *
     * @return void
     */
    public static function cancel_all_schedules()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-cancel-all-schedule-action')) {
            die;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        Cleanup::clean_scheduled_actions();
        HelperBatchInfo::reset_batch_info();
        wp_send_json_success(null, 200);
        wp_die();
    }
    /**
     * Fix database collation issues
     *
     * @since 2.24.4
     *
     * @return void
     */
    public static function fix_database_collation()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-fix-database-collation-action')) {
            die;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        DatabaseCollation::modify_collation();
        wp_send_json_success(null, 200);
        wp_die();
    }
}