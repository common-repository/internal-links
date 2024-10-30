<?php

namespace ILJ\Backend;

use ILJ\Core\Options;
use ILJ\Helper\Help;
/**
 * Rating notifier
 *
 * Responsible for the rating notification on the backend
 *
 * @package ILJ\Backend
 *
 * @since 1.2.0
 */
class RatingNotifier
{
    /**
     * Initializes the rating notifier
     *
     * @return void
     * @since  1.2.0
     */
    public static function init()
    {
        $rating_notification_base = User::getRatingNotificationBaseDate();
        if ($rating_notification_base > new \DateTime('now') || !User::canShowRatingNotification()) {
            return;
        }
        add_action('admin_notices', array('\ILJ\Backend\RatingNotifier', 'registerNotifier'));
        add_action('admin_enqueue_scripts', array('\ILJ\Backend\RatingNotifier', 'registerAssets'));
    }
    /**
     * Registers all assets for the frontend rating notification
     *
     * @return void
     * @since  1.2.0
     */
    public static function registerAssets()
    {
        \ILJ\Helper\Loader::enqueue_script('ilj_index_rating_notification', ILJ_URL . 'admin/js/ilj_rating_notification.js', array(), ILJ_VERSION);
    }
    /**
     * Responsible for the notifier screen in the admin dashboard
     *
     * @since 1.2.0
     *
     * @return void
     */
    public static function registerNotifier()
    {
        ?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php 
        esc_attr_e('Internal Link Juicer', 'internal-links');
        ?>:</strong>
			</p>
			<p>
				<?php 
        esc_html_e('Hey', 'internal-links');
        ?>&nbsp;<?php 
        esc_html_e(User::getName());
        ?>,
				<?php 
        esc_html_e('you have been using the Internal Link Juicer for a while now - that\'s great!', 'internal-links');
        ?>
			</p>
			<p>
				<?php 
        echo wp_kses(__('Could you do us a big favor and <strong>give us your review on WordPress.org</strong>?', 'internal-links') . ' ' . __('This will help us to increase our visibility and to develop even <strong>more features for you</strong>.', 'internal-links'), array('strong' => array()));
        ?>
			</p>
			<p>
				<?php 
        esc_html_e('Thanks.', 'internal-links');
        ?>
			</p>
			<div style="margin-bottom: 15px;">
				<a class="button button-primary"
				   style="margin-right: 15px;"
				   href="<?php 
        echo esc_url('https://wordpress.org/support/plugin/internal-links/reviews/#new-post');
        ?>" target="_blank" rel="noopener">
					<span class="dashicons dashicons-thumbs-up" style="line-height:28px;"></span> <?php 
        esc_html_e('Of course, you deserve it', 'internal-links');
        ?>
				</a>
				<a class="ilj-rating-notification-add button"
				   style="background:none;margin-right: 15px;"
				   href="#"
				   data-add="5">
					<span class="dashicons dashicons-backup" style="line-height:28px;"></span>
					<?php 
        esc_html_e('Please remind me later', 'internal-links');
        ?>
				</a>
				<a class="ilj-rating-notification-add button"
				   style="background:none;"
				   href="#"
				   data-add="-1">
					<?php 
        esc_html_e('Hide this information forever', 'internal-links');
        ?>
				</a>
			</div>
		</div>
		<?php 
    }
}