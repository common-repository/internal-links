<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Help;
/**
 * Option: Blacklist
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class Blacklist extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'blacklist';
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
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Blacklist of posts that should not be used for linking', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Posts that get configured here do not link to others automatically.', 'internal-links');
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
         * `Blacklist of posts that should not be used for linking` field in Settings > Content.
         *
         * @since 2.23.5
         */
        $limit = apply_filters('ilj_post_blacklist_title_character_limit', 20);
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
        ?>">
				<?php 
        foreach ($value as $val) {
            ?>
					<option value="<?php 
            echo esc_attr($val);
            ?>" selected="selected"><?php 
            echo esc_html(get_the_title($val));
            ?></option>
				<?php 
        }
        ?>
		</select>
		<?php 
        Help::render_options_link('whitelist-blacklist/', 'blacklist', 'blacklist');
    }
    /**
     * Checks if a value is a valid value for option
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValidValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        foreach ($value as $val) {
            if (!is_numeric($val)) {
                return false;
            }
        }
        return true;
    }
}