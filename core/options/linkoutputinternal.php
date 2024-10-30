<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Help;
/**
 * Option: Link template for internal links
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class LinkOutputInternal extends AbstractOption
{
    /**
     * Adds the option to an option group
     *
     * @param  string $option_group The option group to which the option gets connected
     * @return void
     */
    public function register($option_group)
    {
        register_setting($option_group, static::getKey(), array('type' => 'string', 'sanitize_callback' => 'esc_html'));
    }
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'link_output_internal';
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
        return __('Template for the link output (keyword links)', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Markup for the output of generated internal links.', 'internal-links');
    }
    /**
     * Outputs the options form element for backend administration
     *
     * @param  mixed $value
     * @return mixed
     */
    public function renderField($value)
    {
        $key = self::getKey();
        ?>
		<input type="text" name="<?php 
        echo esc_attr($key);
        ?>" id="<?php 
        echo esc_attr($key);
        ?>" value="<?php 
        echo esc_attr($value);
        ?>"
		/>
		<?php 
        Help::render_options_link('link-templates/', '', 'link templates');
    }
    /**
     * Returns a hint text for the option, if given
     *
     * @return string
     */
    public function getHint()
    {
        $output = '<p>' . __('You can use the placeholders <code>{{url}}</code> for the target and <code>{{anchor}}</code> for the generated anchor text.', 'internal-links') . '</p>';
        $output .= '<p><small><strong>' . __('With the Pro version you will also have the <code>{{excerpt}}</code> placeholder available for outputting the excerpt, and <code>{{title}}</code> for outputting post/tax title.') . '</strong></small></p>';
        return $output;
    }
    /**
     * Checks if a value is a valid value for option
     *
     * @param  mixed $value The value that gets validated
     * @return bool
     */
    public function isValidValue($value)
    {
        return is_string($value);
    }
}