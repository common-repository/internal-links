<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Options as OptionsHelper;
use ILJ\Core\Options as Options;
use ILJ\Helper\IndexAsset;
/**
 * Option: List of Term Custom Fields used for limiting links
 *
 * @package ILJ\Core\Options
 * @since   2.1.0
 */
class CustomFieldsToLinkTerm extends Custom_Fields_Option
{
    const ILJ_ACF_HINT_FILTER_TERM = 'ilj_hint_for_acf_term';
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'custom_fields_to_link_term';
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
        return __('Custom fields of terms that get used for linking', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('This is a list of term custom fields that should be used for automatic linking.<br>Leave empty to not link any custom fields.', 'internal-links');
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
        $custom_fields = array();
        $key = self::getKey();
        ?>
		<select name="<?php 
        echo esc_attr($key);
        ?>[]"
			id="<?php 
        echo esc_attr($key);
        ?>"
			multiple="multiple"
			<?php 
        OptionsHelper::render_disabler($this);
        ?>
		>
		<?php 
        if (count($custom_fields)) {
            ?>
			<?php 
            foreach ($custom_fields as $custom_field) {
                ?>
				<option
					value="<?php 
                echo esc_attr($custom_field->meta_key);
                ?>"
					<?php 
                selected(in_array($custom_field->meta_key, $value));
                ?>
				>
					<?php 
                echo esc_html($custom_field->meta_key);
                ?>
				</option>
			<?php 
            }
            ?>
		<?php 
        }
        ?>
		<?php 
        $this->render_saved_regex_options($value);
        ?>
		</select>
		<?php 
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
    /**
     * Returns a hint text for the option, if given
     *
     * @return string
     */
    public function getHint()
    {
        $hint = '';
        $hint = apply_filters(self::ILJ_ACF_HINT_FILTER_TERM, $hint);
        return $hint;
    }
}