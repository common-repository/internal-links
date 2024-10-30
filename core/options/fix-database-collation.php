<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Help;
/**
 * Class for rendering an action button for fixing database collation
 *
 * @package ILJ\Core\Options
 * @since   2.24.4
 */
class Fix_Database_Collation
{
    const ILJ_ACTION_PREFIX = 'ilj_action_button_';
    /**
     * Get the unique identifier for the action
     *
     * @return string
     */
    public static function get_key()
    {
        return self::ILJ_ACTION_PREFIX . 'fix_database_collation';
    }
    /**
     * Get the frontend label for the action
     *
     * @return string
     */
    public static function get_title()
    {
        return __('Fix statistics table collation', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public static function get_description()
    {
        $description = __("In some cases, the statistics tables are showing empty and database columns don't match with each other.", 'internal-links');
        $description .= __("This tool can fix the issue.", 'internal-links');
        return $description;
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
			name="ilj-fix-database-collation"
			style="margin-right: 10px"
			class="button button-secondary ilj-fix-database-collation"
			value="<?php 
        esc_attr_e('Fix collations', 'internal-links');
        ?>" />
		<p class="description"><?php 
        echo esc_html(self::get_description());
        ?></p>
		<?php 
    }
}