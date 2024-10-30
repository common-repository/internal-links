<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Help;
use ILJ\Core\Options as Options;
use ILJ\Core\Options\MultipleKeywords;
/**
 * Option: Links per page
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class LinksPerPage extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'links_per_page';
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
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Maximum amount of links per post', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('For an unlimited number of links, set this value to <code>0</code> .', 'internal-links');
    }
    /**
     * Outputs the options form element for backend administration
     *
     * @param  mixed $value
     * @return mixed
     */
    public function renderField($value)
    {
        $multiple_keywords = Options::getOption(MultipleKeywords::getKey());
        $key = self::getKey();
        ?>
		<input type="number" name="<?php 
        echo esc_attr($key);
        ?>" id="<?php 
        echo esc_attr($key);
        ?>" value="<?php 
        echo esc_attr($value);
        ?>" min=0 <?php 
        disabled($multiple_keywords);
        ?> />
		<?php 
        Help::render_options_link('link-countings/', 'links-per-post-amount', 'links per post');
    }
    /**
     * Checks if a value is a valid value for option
     *
     * @param  mixed $value The value that gets validated
     * @return bool
     */
    public function isValidValue($value)
    {
        return is_numeric($value);
    }
}