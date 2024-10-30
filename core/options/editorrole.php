<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Capabilities;
use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Editor role
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class EditorRole extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'editor_role';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return 'administrator';
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
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Minimum required user role for editing keywords', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('The minimum required capability to edit keywords.', 'internal-links');
    }
    /**
     * Outputs the options form element for backend administration
     *
     * @param  mixed $value
     * @return mixed
     */
    public function renderField($value)
    {
        $value = self::getDefault();
        $key = self::getKey();
        ?>
		<select name="<?php 
        echo esc_attr($key);
        ?>" id="<?php 
        echo esc_attr($key);
        ?>" <?php 
        OptionsHelper::render_disabler($this);
        ?>>
			<?php 
        Capabilities::rolesDropdown($value);
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
}