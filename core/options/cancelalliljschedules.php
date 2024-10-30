<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Help;
/**
 * Class for rendering an action button for canceling all ILJ schedules
 *
 * @package ILJ\Core\Options
 * @since   2.23.4
 */
class CancelAllILJSchedules
{
    const ILJ_ACTION_PREFIX = 'ilj_action_button_';
    /**
     * Get the unique identifier for the action
     *
     * @return string
     */
    public static function get_key()
    {
        return self::ILJ_ACTION_PREFIX . 'cancel_all_schedules';
    }
    /**
     * Get the frontend label for the action
     *
     * @return string
     */
    public static function get_title()
    {
        return __('Cancel all internal link juicer schedules', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public static function get_description()
    {
        return __('Cancels all pending ILJ actions.', 'internal-links');
    }
    /**
     * Renders the action button with description
     *
     * @return void
     */
    public static function render_action()
    {
        ?>
		<input
			type="submit"
			name="ilj-cancel-schedules"
			style="margin-right: 10px"
			class="button button-secondary ilj-cancel-schedules"
			value="<?php 
        esc_attr_e('Cancel schedules', 'internal-links');
        ?>" />
		<p class="description"><?php 
        echo esc_html(self::get_description());
        ?></p>
		<?php 
    }
}