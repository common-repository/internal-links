<?php

namespace ILJ\Backend\MenuPage;

use ILJ\Core\Options;
use ILJ\Core\IndexBuilder;
use ILJ\Backend\AdminMenu;
use ILJ\Backend\MenuPage\AbstractMenuPage;
use ILJ\Backend\MenuPage\Includes\SidebarTrait;
use ILJ\Backend\MenuPage\Includes\HeadlineTrait;
use ILJ\Helper\Regex_Custom_Field;
use ILJ\Helper\ScriptHandler;
/**
 * The settings menu page
 *
 * Responsible for displaying the settings section
 *
 * @package ILJ\Backend\Menupage
 *
 * @since 1.0.0
 */
class Settings extends AbstractMenuPage
{
    use HeadlineTrait;
    use SidebarTrait;
    const ILJ_MENUPAGE_SETTINGS_SLUG = 'settings';
    const ILJ_MENUPAGE_SETTINGS_FILTER_TABS = 'ilj_menupage_settings_filter_tabs';
    /**
     * Tabs
     *
     * @var   array
     * @since 1.0.0
     */
    protected $tabs;
    public function __construct()
    {
        $this->page_slug = self::ILJ_MENUPAGE_SETTINGS_SLUG;
        $this->page_title = __('Settings', 'internal-links');
        $this->tabs = array(array('slug' => Options::ILJ_OPTION_SECTION_GENERAL, 'title' => __('General', 'internal-links'), 'callback' => function () {
            settings_fields(Options::ILJ_OPTION_PREFIX_PAGE . Options::ILJ_OPTION_SECTION_GENERAL);
            do_settings_sections(Options::ILJ_OPTION_PREFIX_PAGE . Options::ILJ_OPTION_SECTION_GENERAL);
        }), array('slug' => Options::ILJ_OPTION_SECTION_CONTENT, 'title' => __('Content', 'internal-links'), 'callback' => function () {
            settings_fields(Options::ILJ_OPTION_PREFIX_PAGE . Options::ILJ_OPTION_SECTION_CONTENT);
            do_settings_sections(Options::ILJ_OPTION_PREFIX_PAGE . Options::ILJ_OPTION_SECTION_CONTENT);
        }), array('slug' => Options::ILJ_OPTION_SECTION_LINKS, 'title' => __('Links', 'internal-links'), 'callback' => function () {
            settings_fields(Options::ILJ_OPTION_PREFIX_PAGE . Options::ILJ_OPTION_SECTION_LINKS);
            do_settings_sections(Options::ILJ_OPTION_PREFIX_PAGE . Options::ILJ_OPTION_SECTION_LINKS);
        }), array('slug' => Options::ILJ_OPTION_SECTION_ACTIONS, 'title' => __('Actions', 'internal-links'), 'callback' => function () {
            settings_fields(Options::ILJ_OPTION_PREFIX_PAGE . Options::ILJ_OPTION_SECTION_ACTIONS);
            do_settings_sections(Options::ILJ_OPTION_PREFIX_PAGE . Options::ILJ_OPTION_SECTION_ACTIONS);
        }));
    }
    /**
     * register
     *
     * @return void
     */
    public function register()
    {
        $this->addSubMenuPage();
        \ILJ\Helper\Loader::register_script('ilj_select2', ILJ_URL . 'admin/js/select2.js', array(), ILJ_VERSION);
        wp_localize_script('ilj_select2', 'ilj_select2_translation', array('error_loading' => __('The results could not be loaded.', 'internal-links'), 'input_too_short' => __('Minimum characters to start search', 'internal-links'), 'loading_more' => __('Loading more results…', 'internal-links'), 'no_results' => __('No results found', 'internal-links'), 'searching' => __('Searching…', 'internal-links'), 'custom_field_starts_with' => Regex_Custom_Field::get_starts_with_label_template(), 'custom_field_ends_with' => Regex_Custom_Field::get_ends_with_label_template(), 'custom_field_has' => Regex_Custom_Field::get_contains_label_template(), 'success_message' => __('Success', 'internal-links')));
        \ILJ\Helper\Loader::register_script('ilj_menu_settings', ILJ_URL . 'admin/js/ilj_menu_settings.js', array(), ILJ_VERSION);
        wp_localize_script('ilj_menu_settings', 'ilj_menu_settings_translation', array('success_message' => __('Success', 'internal-links'), 'confirm_cancel_message' => __('This will cancel all pending link building schedules - are you sure you want to do this?', 'internal-links'), 'confirm_database_collation_fix_message' => __('It will update the database collation.', 'internal-links') . ' ' . __('Are you sure you want to do this?', 'internal-links'), 'upgrade_to_pro_link' => get_admin_url(null, 'admin.php?billing_cycle=annual&trial=true&page=' . AdminMenu::ILJ_MENUPAGE_SLUG . '-pricing'), 'pro_feature_title' => __('This is a PRO Feature.', 'internal-links'), 'negative_not_allowed_message' => __('Negative numbers are not allowed.', 'internal-links')));
        wp_localize_script('ilj_menu_settings', 'ilj_menu_settings_obj', array('nonce' => wp_create_nonce('ilj-cancel-all-schedule-action'), 'nonce_ilj_fix_collation' => wp_create_nonce('ilj-fix-database-collation-action'), 'nonce_ilj_search_posts' => wp_create_nonce('ilj-search-posts-action'), 'nonce_ilj_search_terms' => wp_create_nonce('ilj-search-terms-action')));
        $this->addAssets(array('tipso' => ILJ_URL . 'admin/js/tipso.js', 'ilj_menu_settings' => ILJ_URL . 'admin/js/ilj_menu_settings.js', 'ilj_select2' => ILJ_URL . 'admin/js/select2.js', 'ilj_promo' => ILJ_URL . 'admin/js/ilj_promo.js'), array('tipso' => ILJ_URL . 'admin/css/tipso.css', 'ilj_menu_settings' => ILJ_URL . 'admin/css/ilj_menu_settings.css', 'ilj_ui' => ILJ_URL . 'admin/css/ilj_ui.css', 'ilj_grid' => ILJ_URL . 'admin/css/ilj_grid.css', 'ilj_select2' => ILJ_URL . 'admin/css/select2.css'));
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
        $active_tab = 'general';
        if (isset($_GET['tab']) && in_array($_GET['tab'], array('general', 'content', 'links', 'actions'))) {
            $active_tab = $_GET['tab'];
        }
        $tabs = apply_filters(self::ILJ_MENUPAGE_SETTINGS_FILTER_TABS, $this->tabs);
        ?>
		<div class="wrap ilj-menu-settings">
			<?php 
        $this->renderHeadline(__('Settings', 'internal-links'));
        ?>
			<div class="ilj-row">
				<div class="col-9">
					<h2 class="nav-tab-wrapper">
		<?php 
        foreach ($tabs as $tab) {
            ?>
			<a href="<?php 
            echo esc_url(sprintf('?page=%s-%s&tab=%s', AdminMenu::ILJ_MENUPAGE_SLUG, $this->getSlug(), strtolower($tab['slug'])));
            ?>"
			   class="nav-tab <?php 
            echo esc_attr(($active_tab == $tab['slug']) ? ' nav-tab-active' : '');
            ?>">
			   <?php 
            echo esc_html($tab['title']);
            ?>
			</a>
			<?php 
        }
        ?>
			<a href="<?php 
        echo esc_url(get_admin_url(null, 'admin.php?billing_cycle=annual&trial=true&page=' . AdminMenu::ILJ_MENUPAGE_SLUG . '-pricing'));
        ?>"
			   class="ilj-upgrade">
			   <?php 
        esc_html_e('Upgrade to Pro now - unlock all features', 'internal-links');
        ?><span class="dashicons dashicons-unlock"></span>
			</a>
			<?php 
        echo '</h2>';
        echo '<form action="options.php" method="post">';
        echo '<section class="section">';
        settings_errors('internal-links');
        foreach ($tabs as $tab) {
            if ($tab['slug'] == $active_tab) {
                $tab['callback']();
            }
        }
        echo '<footer class="actions">';
        echo '<div class="action">';
        submit_button(__('Save changes', 'internal-links'));
        echo '</div>';
        echo '</form>';
        echo '<form class="action" action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
        wp_nonce_field(Options::KEY);
        echo '<input type="hidden" name="action" value="' . esc_attr(Options::KEY) . '">';
        echo '<input type="hidden" name="section" value="' . esc_attr($active_tab) . '" >';
        echo '<div>';
        echo '<p class="submit">';
        ?>
		<input type="submit"
			   name="ilj-reset-keywords"
			   style="margin-right: 10px"
			   class="button button-secondary"
			   value="<?php 
        echo esc_attr(__('Reset all keywords', 'internal-links'));
        ?>"
			   onclick="return confirm('<?php 
        echo esc_js(__('This will delete all existing keywords in your site - are you sure you want to do this?', 'internal-links'));
        ?>')"
		/>
		<input type="submit"
			   name="ilj-reset-options"
			   class="button button-secondary"
			   value="<?php 
        echo esc_attr(__('Reset options', 'internal-links'));
        ?>"
			   onclick="return confirm('<?php 
        echo esc_js(__('You are going to overwrite the existing settings in this section with the defaults.', 'internal-links'));
        ?>')"
			   />
		<?php 
        echo '</p>';
        echo '</div>';
        echo '</footer>';
        echo '</section>';
        echo '</form>';
        echo '</div>';
        echo '<div class="col-3">';
        $this->renderSidebar();
        echo '</div>';
        echo '</div>';
    }
    /**
     * Handles the index rebuilding process after settings got updated
     *
     * @since 1.0.0
     *
     * @return void
     */
    private static function processIndexRebuilding()
    {
        if (!isset($_GET['settings-updated']) || 'true' !== $_GET['settings-updated']) {
            return;
        }
        do_action(IndexBuilder::ILJ_INITIATE_BATCH_REBUILD);
    }
}