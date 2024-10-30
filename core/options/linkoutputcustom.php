<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Link template for custom links
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class LinkOutputCustom extends AbstractOption
{
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
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'link_output_custom';
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
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return esc_html('<a href="{{url}}">{{anchor}}</a>');
    }
    /**
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Template for the link output (custom links)', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('You can use the placeholders <code>{{url}}</code> for the target, <code>{{excerpt}}</code> for the excerpt and <code>{{anchor}}</code> for the generated anchor text and <code>{{title}}</code> for outputting link title.', 'internal-links');
    }
    /**
     * Outputs the options form element for backend administration
     *
     * @param  mixed $value
     * @return mixed
     */
    public function renderField($value)
    {
        $value = esc_html(self::getDefault());
        $key = self::getKey();
        ?>
		<input type="text"
			   name="<?php 
        echo esc_attr($key);
        ?>"
			   id="<?php 
        echo esc_attr($key);
        ?>"
			   value="<?php 
        echo esc_attr($value);
        ?>"
			<?php 
        OptionsHelper::render_disabler($this);
        ?> />
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
}