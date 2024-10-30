<?php

namespace ILJ\Backend\MenuPage;

use ActionScheduler_Store;
use ILJ\Backend\MenuPage\Includes\PostboxTrait;
use ILJ\Core\Options;
use ILJ\Enumeration\IndexMode;
use ILJ\Helper\Help;
use ILJ\Helper\Statistic;
use ILJ\Backend\AdminMenu;
use ILJ\Backend\BatchInfo;
use ILJ\Helper\IndexAsset;
use ILJ\Backend\Environment;
use ILJ\Backend\MenuPage\Includes\SidebarTrait;
use ILJ\Backend\MenuPage\Includes\HeadlineTrait;
use ILJ\Core\IndexBuilder;
use ILJ\Helper\BatchInfo as HelperBatchInfo;
use ILJ\Statistics\Link;
use ILJ\Helper\ScriptHandler;
use RankMath\Helper;
use ILJ\Helper\Stopwatch;
/**
 * The dashboard page
 *
 * Responsible for displaying the dashboard
 *
 * @package ILJ\Backend\Menupage
 * @since   1.0.0
 */
class Dashboard extends AbstractMenuPage
{
    const ILJ_STATISTIC_HANDLE = 'ilj_statistic_pro';
    public function __construct()
    {
        $this->page_title = __('Dashboard', 'internal-links');
    }
    /**
     * register
     *
     * @return void
     */
    public function register()
    {
        $this->addSubMenuPage(true);
        $assets_css = array('tipso' => ILJ_URL . 'admin/js/tipso.js', 'jquery.dataTables' => ILJ_URL . 'admin/js/jquery.dataTables.js');
        $assets_css['ilj_promo'] = ILJ_URL . 'admin/js/ilj_promo.js';
        $this->addAssets($assets_css, array('tipso' => ILJ_URL . 'admin/css/tipso.css', 'ilj_ui' => ILJ_URL . 'admin/css/ilj_ui.css', 'ilj_grid' => ILJ_URL . 'admin/css/ilj_grid.css', 'jquery.dataTables' => ILJ_URL . 'admin/css/jquery.dataTables.css', self::ILJ_STATISTIC_HANDLE => ILJ_URL . 'admin/css/ilj_statistic.css'));
        add_action('admin_enqueue_scripts', function () {
            $screen = get_current_screen();
            if ($screen->base === $this->page_hook) {
                \ILJ\Helper\Loader::register_script(self::ILJ_STATISTIC_HANDLE, ILJ_URL . 'admin/js/ilj_statistic.js', array('wp-polyfill'), ILJ_VERSION);
                wp_localize_script(self::ILJ_STATISTIC_HANDLE, 'ilj_statistic_translation', Dashboard::getTranslation());
                wp_localize_script(self::ILJ_STATISTIC_HANDLE, 'ilj_link_statistic_filter_types', Link::get_types());
                wp_enqueue_script(self::ILJ_STATISTIC_HANDLE);
                wp_localize_script(self::ILJ_STATISTIC_HANDLE, 'headerLabels', array(__('Anchor text', 'internal-links'), __('Character count', 'internal-links'), __('Word count', 'internal-links'), __('Frequency', 'internal-links')));
                wp_localize_script(self::ILJ_STATISTIC_HANDLE, 'header_titles', array(__('Title', 'internal-links'), __('Configured keywords', 'internal-links'), __('Type', 'internal-links'), __('Incoming links', 'internal-links'), __('Outgoing links', 'internal-links'), __('Action', 'internal-links')));
                wp_localize_script(self::ILJ_STATISTIC_HANDLE, 'ilj_dashboard', array('nonce' => wp_create_nonce('ilj-dashboard'), 'loadingText' => __('Loading...', 'internal-links')));
            }
        });
    }
    /**
     * render
     *
     * @return void
     */
    public function render()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap ilj-menu-dashboard">';
        $this->renderHeadline(__('Dashboard', 'internal-links'));
        echo '<div class="ilj-row">';
        echo '<div class="col-9">';
        $related = '<p><strong>' . __('Installed version', 'internal-links') . ':</strong> ' . $this->getVersion() . '</p>';
        $related .= $this->getHelpRessources();
        $this->renderPostbox(array('title' => __('Plugin related', 'internal-links'), 'content' => $related));
        $this->renderPostbox(array('title' => __('Linkindex info', 'internal-links'), 'content' => $this->getIndexMeta(), 'class' => 'ilj-indexinfo'));
        $this->renderPostbox(array('title' => __('Statistics', 'internal-links'), 'content' => $this->getStatistics(), 'class' => 'ilj-statistic-wrap', 'help' => Help::getLinkUrl('statistics/', null, 'statistics', 'dashboard')));
        echo '</div>';
        echo '<div class="col-3">';
        $this->renderSidebar();
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Generates the help links
     *
     * @since  1.2.0
     * @return string
     */
    protected function getHelpRessources()
    {
        $output = '';
        $url_manual = Help::getLinkUrl(null, null, 'docs', 'dashboard');
        $url_tour = add_query_arg(array('page' => AdminMenu::ILJ_MENUPAGE_SLUG . '-' . Tour::ILJ_MENUPAGE_TOUR_SLUG), admin_url('admin.php'));
        $url_plugins_forum = 'https://wordpress.org/support/plugin/internal-links/';
        $output .= '<ul class="ilj-ressources divide">';
        $output .= '<li>';
        $output .= '<span class="dashicons dashicons-book-alt"></span>';
        $output .= '<a href="' . $url_manual . '" target="_blank" rel="noopener"><strong>' . __('Docs & how to', 'internal-links') . '</strong><span>' . __('Learn how to use the plugin', 'internal-links') . '</span></a>';
        $output .= '</li>';
        $output .= '<li>';
        $output .= '<span class="dashicons dashicons-welcome-learn-more"></span>';
        $output .= '<a href="' . $url_tour . '"><strong>' . __('Interactive tour', 'internal-links') . '</strong><span>' . __('A quick guided tutorial', 'internal-links') . '</span></a>';
        $output .= '</li>';
        $output .= '<li>';
        $output .= '<span class="dashicons dashicons-testimonial"></span>';
        $output .= '<a href="' . $url_plugins_forum . '" target="_blank" rel="noopener"><strong>' . __('Request support', 'internal-links') . '</strong><span>' . __('Get help through our forum', 'internal-links') . '</span></a>';
        $output .= '</li>';
        $output .= '</ul>';
        return $output;
    }
    /**
     * Generates the statistic section
     *
     * @since  1.2.0
     * @return string
     */
    public function getStatistics()
    {
        $output = '';
        $output .= '<div class="ilj-statistic-cover"><div class="spinner is-active"></div></div>';
        $output .= '<div class="ilj-row ilj-statistic">';
        $output .= '<div class="col-12 no-top-padding">';
        $output .= '<h2 class="nav-tab-wrapper">';
        $output .= '<a data-target="statistic-links" class="nav-tab nav-tab-active">' . __('Link statistics', 'internal-links') . '</a>';
        $output .= '<a data-target="statistic-anchors" class="nav-tab">' . __('Anchor text statistics', 'internal-links') . '</a>';
        $output .= '</h2>';
        $output .= '<div id="statistic-links" class="tab-content active">';
        $output .= '<div class="ilj-overlay" id="link-statistics-loader"><div class="spinner is-active" id="link-statistics-spinner"></div></div>';
        $output .= '</div>';
        $output .= '<div id="statistic-anchors" class="tab-content">';
        $output .= '<div class="spinner is-active"></div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '<div class="ilj-row"></div>';
        $output .= '</div>';
        return $output;
    }
    /**
     * Generates all index related meta data
     *
     * @since  1.2.0
     * @return string
     */
    private function getIndexMeta()
    {
        $output = '';
        $first_build_message = '';
        $build_button_text = __('Rebuild index', 'internal-links');
        $linkindex_info = Environment::get('linkindex');
        if ('' == $linkindex_info['last_update']['entries']) {
            $help_url = Help::getLinkUrl('editor/', null, 'editor onboarding', 'dashboard');
            $output .= '<p>' . __('Index has no entries yet', 'internal-links') . '.</p>';
            $output .= '<p class="divide" style="display:flex"><span class="dashicons dashicons-arrow-right-alt"></span> <strong>' . __('Start to set some keywords to your posts', 'internal-links') . ' - <a href="' . $help_url . '" target="_blank" rel="noopener">' . __('learn how it works', 'internal-links') . '</a></strong></p>';
            $first_build_message = '<span class="dashicons dashicons-arrow-right-alt"></span> <strong>' . __('Or hit then `Build index` button to start right now') . '</strong>';
            $build_button_text = __('Build index', 'internal-links');
        }
        $output .= '<ul>';
        if (isset($linkindex_info['last_update']) && '' != $linkindex_info['last_update']['entries']) {
            $date = $linkindex_info['last_update']['date']->setTimezone(Stopwatch::timezone());
            $output .= '<li class="ilj-row"><div class="col-4"><strong>' . __('Amount of links in the index', 'internal-links') . '</strong>:</div><div class="col-6" id="ilj-linkindex-count">' . number_format($linkindex_info['last_update']['entries']) . '</div><div class="clear"></div></li>';
            $output .= '<li class="ilj-row"><div class="col-4"><strong>' . __('Amount of configured keywords', 'internal-links') . '</strong>:</div><div class="col-6" id="ilj-configured-keywords-count">' . number_format(Statistic::get_all_configured_keywords_count()) . '</div><div class="clear"></div></li>';
            $output .= '<li class="ilj-row"><div class="col-4"><strong>' . __('Last built', 'internal-links') . '</strong>:</div><div class="col-6" id="ilj-last-built">' . $date->format(get_option('date_format')) . ' ' . __('at', 'internal-links') . ' ' . $date->format(get_option('time_format')) . '</div><div class="clear"></div></li>';
            $output .= '<li class="ilj-row"><div class="col-4"><strong>' . __('Current index status', 'internal-links') . '</strong>:</div><div class="col-6" id="ilj-index-status">' . __($this->getCurrentIndexStatus(), 'internal-links') . '</div><div class="clear"></div></li>';
        }
        $output .= '<li class="ilj-row"><div class="col-4" style="display:flex">' . (('' != $first_build_message) ? $first_build_message : '') . '</div><div class="col-6">' . '<a class="button ilj-index-rebuild ' . $this->disableRebuildButton() . ' " title="' . $this->rebuildButtonTooltip() . '" href="#" >' . $build_button_text . '</a> ' . ' <a class="button ilj-index-restart-rebuild ' . $this->disableRebuildButton('restart') . ' " title="' . $this->rebuildButtonTooltip('restart') . '" href="#" >' . __('Restart index build', 'internal-links') . '</a>' . ' <br> <div class="ilj-index-rebuild-message"><p id="ilj-index-rebuild-message"><p><div class="clear"></div></p></div></div><div class="clear"></div></li>';
        $output .= '</ul>';
        return $output;
    }
    /**
     * Get the current index building status
     *
     * @return String
     */
    protected function getCurrentIndexStatus()
    {
        $batch_build_info = new HelperBatchInfo();
        $status = $batch_build_info->getBatchStatus();
        return HelperBatchInfo::translateBatchStatus($status);
    }
    /**
     * Returns the version including the subscription type
     *
     * @since  1.1.0
     * @return string
     */
    protected function getVersion()
    {
        return ILJ_VERSION . ' <span class="badge basic">Basic</span>';
    }
    /**
     * Returns the frontend translation
     *
     * @since 1.2.5
     *
     * @return array
     */
    public static function getTranslation()
    {
        $translation = array('incoming_links' => __('Incoming links to', 'internal-links'), 'outgoing_links' => __('Outgoing links from', 'internal-links'), 'anchor_text' => __('Anchor text', 'internal-links'), 'datatables_aria_sortAscending' => __(': activate to sort column ascending', 'internal-links'), 'datatables_aria_sortDescending' => __(': activate to sort column descending', 'internal-links'), 'datatables_paginate_first' => __('First', 'internal-links'), 'datatables_paginate_last' => __('Last', 'internal-links'), 'datatables_paginate_next' => __('Next', 'internal-links'), 'datatables_paginate_previous' => __('Previous', 'internal-links'), 'datatables_empty_table' => __('No data available in table', 'internal-links'), 'datatables_info' => __('Showing _START_ to _END_ of _TOTAL_ entries', 'internal-links'), 'datatables_info_empty' => __('Showing 0 to 0 of 0 entries', 'internal-links'), 'datatables_info_filtered' => __('(filtered from _MAX_ total entries)', 'internal-links'), 'datatables_length_menu' => __('Show _MENU_ entries', 'internal-links'), 'datatables_loading_records' => __('Loading...', 'internal-links'), 'datatables_processing' => __('Processing...', 'internal-links'), 'datatables_search' => __('Search:', 'internal-links'), 'datatables_zero_records' => __('No matching records found', 'internal-links'), 'filter_type' => __('Filter type', 'internal-links'), 'filter_section_posts_pages' => __('Posts and Pages', 'internal-links'), 'filter_section_taxonomies' => __('Taxonomies', 'internal-links'), 'filter_section_custom_links' => __('Custom Links', 'internal-links'), 'show_incoming_links' => __('Show incoming links', 'internal-links'), 'show_outgoing_links' => __('Show outgoing links', 'internal-links'), 'edit' => __('Edit', 'internal-links'), 'open' => __('Open', 'internal-links'));
        return $translation;
    }
    /**
     * Generates a list of post ids as post links
     *
     * @deprecated
     * @since      1.2.0
     * @param      array $data          Bag of objects
     * @param      int   $asset_id_node The name of the post id property in single object
     * @return     string
     */
    private function getLinkList(array $data, $asset_id_node)
    {
        $render_header = array(__('Page', 'internal-links'), __('Count', 'internal-links'), __('Action', 'internal-links'));
        $render_data = array();
        if (!isset($data[0]) || !property_exists($data[0], $asset_id_node)) {
            return '';
        }
        foreach ($data as $row) {
            $asset_id = (int) $row->{$asset_id_node};
            if ($asset_id < 1 || 'post' != $row->type) {
                continue;
            }
            $asset_data = IndexAsset::getMeta($asset_id, 'post');
            if (!$asset_data) {
                continue;
            }
            $edit_link = sprintf('<a href="%s" title="' . __('Edit', 'internal-links') . '" class="tip">%s</a>', $asset_data->url_edit, '<span class="dashicons dashicons-edit"></span>');
            $post_link = sprintf('<a href="%s" title="' . __('Open', 'internal-links') . '" class="tip" target="_blank" rel="noopener">%s</a>', $asset_data->url, '<span class="dashicons dashicons-external"></span>');
            $render_data[] = array($asset_data->title, $row->elements, $post_link . $edit_link);
        }
        return $this->getList($render_header, $render_data);
    }
    /**
     * Generates a list of keywords
     *
     * @deprecated
     * @since      1.2.0
     * @param      array  $data         Bag of objects
     * @param      string $keyword_node The name of the keyword property in single object
     * @return     string
     */
    private function getKeywordList(array $data, $keyword_node)
    {
        $render_header = array(__('Keyword', 'internal-links'), __('Count', 'internal-links'));
        $render_data = array();
        if (!isset($data[0]) || !property_exists($data[0], $keyword_node)) {
            return '';
        }
        foreach ($data as $row) {
            $keyword = $row->{$keyword_node};
            $render_data[] = array($keyword, $row->elements);
        }
        return $this->getList($render_header, $render_data);
    }
    /**
     * Generic method for generating a list
     *
     * @deprecated
     * @since      1.2.0
     * @param      array $header
     * @param      array $data
     * @return     string
     */
    private function getList(array $header, array $data)
    {
        $output = '';
        $output .= '<table class="wp-list-table widefat striped ilj-statistic-table">';
        $output .= '<thead>';
        $output .= '<tr>';
        foreach ($header as $title) {
            $output .= '<th scope="col">' . $title . '</th>';
        }
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';
        foreach ($data as $row) {
            $output .= '<tr>';
            foreach ($row as $col) {
                $output .= '<td>' . $col . '</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';
        return $output;
    }
    /**
     * Check if Rebuild button should be disabled or not
     *
     * @param  mixed $type
     * @return string
     */
    private function disableRebuildButton($type = '')
    {
        $pending_actions = 0;
        $args = array('hook' => IndexBuilder::ILJ_SET_BATCHED_INDEX_REBUILD, 'status' => ActionScheduler_Store::STATUS_PENDING);
        $pending_actions += count(as_get_scheduled_actions($args));
        $args = array('hook' => IndexBuilder::ILJ_SET_BATCHED_INDEX_REBUILD, 'status' => ActionScheduler_Store::STATUS_RUNNING);
        $pending_actions += count(as_get_scheduled_actions($args));
        $args = array('hook' => IndexBuilder::ILJ_BUILD_BATCHED_INDEX, 'status' => ActionScheduler_Store::STATUS_PENDING);
        $pending_actions += count(as_get_scheduled_actions($args));
        if ('restart' == $type) {
            if (0 == $pending_actions) {
                return 'hidden';
            } elseif ($pending_actions) {
                return '';
            }
        }
        if (0 == $pending_actions) {
            return '';
        }
        return 'hidden';
    }
    /**
     * Returns Rebuild button tooltip
     *
     * @param  mixed $type
     * @return void
     */
    private function rebuildButtonTooltip($type = '')
    {
        if ('restart' == $type) {
            return __('Note: For the case your index build gets stuck, you can restart the index build here', 'internal-links');
        } else {
            return __('Note: This will override all pending index builds', 'internal-links');
        }
    }
}