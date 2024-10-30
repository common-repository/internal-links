<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Blacklist for terms
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class TermBlacklist extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'term_blacklist';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return array();
    }
    /**
     * Identifies if the current option is pro only
     *
     * @return bool
     */
    public static function isPro()
    {
        return true;
    }
    /**
     * Adds the option to an option group
     *
     * @param  string $option_group The option group to which the option gets connected
     * @return void
     */
    public function register($option_group)
    {
    }
    /**
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Blacklist of terms that should not be used for linking', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Terms that get configured here do not link to others automatically.', 'internal-links');
    }
    /**
     * Outputs the options form element for backend administration
     *
     * @param  mixed $value
     * @return mixed
     */
    public function renderField($value)
    {
        if ('' == $value) {
            $value = array();
        }
        /**
         * This filter is used to customize the title character limit on
         * `Blacklist of terms that should not be used for linking` field in Settings > Content.
         *
         * @since 2.23.5
         */
        $limit = apply_filters('ilj_term_blacklist_title_character_limit', 20);
        ?>
		<select name="<?php 
        echo esc_attr(self::getKey());
        ?>[]"
				id="<?php 
        echo esc_attr(self::getKey());
        ?>"
				multiple="multiple"
				data-ilj-title-character-limit="<?php 
        echo esc_attr($limit);
        ?>"
				<?php 
        OptionsHelper::render_disabler($this);
        ?>
		>
		<?php 
        foreach ($value as $val) {
            $term = get_term($val);
            $term_name = sprintf('%s [%s]', $term->name, ucfirst($term->taxonomy));
            ?>
			<option value="<?php 
            echo esc_attr($term->term_id);
            ?>" selected="selected"><?php 
            echo esc_attr($term_name);
            ?></option>
			<?php 
        }
        echo '</select>';
    }
    /**
     * Checks if a value is a valid value for option
     *
     * @param  mixed $value The value that gets validated
     * @return bool
     */
    public function isValidValue($value)
    {
        return false;
    }
}