<?php

namespace ILJ\Backend\MenuPage\Includes;

use ILJ\Backend\User;
use ILJ\Backend\AdminMenu;
use ILJ\Backend\Menupage\Settings;
use ILJ\Core\Options\CustomFieldsToLinkPost;
/**
 * Backend Sidebar
 *
 * Responsible for displaying the sidebar
 *
 * @package ILJ\Backend\Menupage
 * @since   1.0.1
 */
trait SidebarTrait
{
    /**
     * Renders the sidebar
     *
     * @since  1.0.1
     * @return void
     */
    protected function renderSidebar()
    {
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        if (\ILJ\ilj_fs()->is_free_plan() && !User::get('hide_promo') && AdminMenu::ILJ_MENUPAGE_SLUG . '-' . Settings::ILJ_MENUPAGE_SETTINGS_SLUG != $current_page) {
            $this->renderPromo();
        }
        ?>
		<div class="postbox ilj-postbox info">
			<h3 class="title"><?php 
        esc_html_e('Support us', 'internal-links');
        ?></h3>
			<div class="inside">
				<p>
					<?php 
        echo wp_kses(__('Do you like the plugin?', 'internal-links'), array('strong' => array()));
        echo ' ' . wp_kses(__('<strong>Please rate us</strong> or <strong>tell your friends</strong> about the Internal Link Juicer.', 'internal-links'), array('strong' => array()));
        ?>
				</p>
				<p>
					<a href="https://wordpress.org/support/plugin/internal-links/reviews/" class="button button-primary"
					   target="_blank" rel="noopener">&raquo;
						<?php 
        esc_html_e('Give us your review', 'internal-links');
        ?>
					</a>
				</p>
				<p class="divide">
					<?php 
        echo wp_kses(__('Are you looking for a <strong>new feature</strong> or have <strong>suggestions for improvement</strong>?', 'internal-links'), array('strong' => array()));
        echo ' ' . wp_kses(__('Have you <strong>found a bug</strong>?', 'internal-links'), array('strong' => array()));
        echo ' ' . wp_kses(__('Please tell us about it.', 'internal-links'), array('strong' => array()));
        ?>
				</p>
				<p>
					<a href="<?php 
        echo esc_url(get_admin_url(null, 'admin.php?page=' . AdminMenu::ILJ_MENUPAGE_SLUG . '-contact'));
        ?>"
					   class="button">&raquo;
						<?php 
        esc_html_e('Get in touch with us', 'internal-links');
        ?>
					</a>
				</p>
				<p class="divide">
					<strong><?php 
        esc_html_e('Thank you for using the Internal Link Juicer', 'internal-links');
        ?></strong>
				</p>
				<p class="character">
					<img src="<?php 
        echo esc_url(ILJ_URL . '/admin/img/character.png');
        ?>" alt="The Internal Link Juicer"/>
				</p>
			</div>
		</div>
		<?php 
    }
    /**
     * Renders informational promotion for pro version of ILJ
     *
     * @since  1.1.1
     * @return void
     */
    protected function renderPromo()
    {
        $kses = array('strong' => array());
        ?>
		<div class="postbox ilj-postbox promo">
			<button type="button" class="handlediv" aria-expanded="true">
				<span class="close"
					  title="<?php 
        esc_attr_e('Hide this information forever', 'internal-links');
        ?>"
					  aria-hidden="true"></span></button>
			<h3 class="title"><?php 
        esc_html_e('Activate pro version', 'internal-links');
        ?></h3>
			<div class="inside">
				<h4><?php 
        esc_html_e('Achieve even more with Internal Link Juicer Pro:', 'internal-links');
        ?></h4>
				<ul>
					<li>
						<span><?php 
        esc_html_e('Individual Links', 'internal-links');
        ?></span>: <?php 
        echo wp_kses(__('More flexible linking with your own <strong>individual URLs</strong> as link targets.', 'internal-links'), $kses);
        ?>
					</li>
					<li>
						<span><?php 
        esc_html_e('Maximum control', 'internal-links');
        ?></span>: <?php 
        echo wp_kses(__('<strong>Shortcode</strong> and <strong>settings</strong> to <strong>exclude</strong> specific areas of content <strong>from link creation</strong>.', 'internal-links'), $kses);
        ?>
					</li>
					<li>
						<span><?php 
        esc_html_e('Focused optimization', 'internal-links');
        ?></span>: <?php 
        echo wp_kses(__('Maximize your results with the comprehensive <strong>Statistics Dashboard</strong>.', 'internal-links'), $kses);
        ?>
					</li>
					<li>
						<span><?php 
        esc_html_e('Activate taxonomies', 'internal-links');
        ?></span>: <?php 
        echo wp_kses(__('Unlimited possibilities by linking even from and to <strong>categories and tags</strong>.', 'internal-links'), $kses);
        ?>
					</li>

					<?php 
        /**
         * Adds Additional Pro Features
         *
         * @since 2.1.0
         */
        do_action(CustomFieldsToLinkPost::ILJ_ACTION_ADD_PRO_FEATURES);
        ?>
				</ul>
				<p>
					<a href="<?php 
        echo esc_url(get_admin_url(null, 'admin.php?page=' . AdminMenu::ILJ_MENUPAGE_SLUG . '-pricing'));
        ?>"
					   class="button button-primary">&raquo; <?php 
        esc_html_e(__('Upgrade now', 'internal-links'));
        ?></a>
				</p>
			</div>
		</div>
		<?php 
    }
}