<?php

namespace ILJ\Backend;

use ILJ\Core\Options;
/**
 * Admin Notices
 *
 * Manages everything related to notices
 *
 * @package ILJ\Backend
 * @since   2.23.5
 */
class Notices
{
    const ILJ_DISMISS_ADMIN_WARNING_LITESPEED = "ilj_dismiss_admin_warning_litespeed";
    /**
     * show_admin_warning_litespeed
     *
     * @return void
     */
    public static function show_admin_warning_litespeed()
    {
        ?>
		<div class="iljmessage updated admin-warning-litespeed notice is-dismissible">
			<p><strong><?php 
        esc_html_e('Warning', 'internal-links');
        ?></strong>
			<?php 
        esc_html_e('Your website is hosted using the LiteSpeed web server.', 'internal-links');
        ?>
				<a href="https://www.internallinkjuicer.com/faqs/" target="_blank">
					<?php 
        esc_html_e('Please consult this FAQ if you have problems building links.', 'internal-links');
        ?>
				</a>
			</p>
		</div>
		<?php 
    }
    /**
     * This function will add ilj_dismiss_admin_warning_litespeed option to hide litespeed admin warning after dismissed
     *
     * @return void
     */
    public static function dismiss_admin_warning_litespeed()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ilj-general-nonce')) {
            die;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        Options::setOption(self::ILJ_DISMISS_ADMIN_WARNING_LITESPEED, true);
        wp_die();
    }
}