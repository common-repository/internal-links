<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Global setting for maximum incoming links to a post
 *
 * @package ILJ\Core\Options
 * @since   2.23.5
 */
class Max_Incoming_Links extends AbstractOption
{
    /**
     * {@inheritDoc}
     */
    public static function isPro()
    {
        return true;
    }
    public function getDescription()
    {
        return __('The maximum number of links each post/page/term can have from other posts/pages/terms.', 'internal-links');
    }
    /**
     * {@inheritDoc}
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'max_incoming_links';
    }
    /**
     * {@inheritDoc}
     */
    public static function getDefault()
    {
        return 1;
    }
    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return __('Maximum incoming links', 'internal-links');
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
			   id="<?php 
        echo esc_attr($key);
        ?>"
			   name="<?php 
        echo esc_attr($key);
        ?>"
			   value="<?php 
        echo esc_attr($value);
        ?>"
			   min="1" required <?php 
        OptionsHelper::render_disabler($this);
        ?>>
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
        return is_numeric($value) && $value > 0;
    }
}