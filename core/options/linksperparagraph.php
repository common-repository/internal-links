<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Help;
use ILJ\Core\Options as Options;
use ILJ\Core\Options\MultipleKeywords;
use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Links per Paragraph
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class LinksPerParagraph extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'links_per_paragraph';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return (int) 0;
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
        return __('Maximum amount of links per paragraph', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Set the maximum links per paragraph.', 'internal-links');
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
		<input type="number"
			min="1"
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
        ?>
		/>
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
        return 0;
    }
}