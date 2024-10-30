<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Help;
/**
 * Option: Whitelist
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class Whitelist extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'whitelist';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return array('page', 'post');
    }
    /**
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Whitelist of post types, that should be used for linking', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('All posts within the allowed post types can link to other posts automatically.', 'internal-links');
    }
    /**
     * Gets all post types that can be used with the plugin
     *
     * @since  1.2.0
     * @return array
     */
    public static function getEditorPostTypes()
    {
        $editor_post_types = array();
        $post_types_public = get_post_types(array('public' => true, '_builtin' => false), 'objects', 'or');
        $post_types_with_editor = get_post_types_by_support(array('editor'));
        if (!count($post_types_public)) {
            return $editor_post_types;
        }
        foreach ($post_types_public as $post_type) {
            if (in_array($post_type->name, $post_types_with_editor)) {
                $editor_post_types[] = $post_type;
            }
        }
        return $editor_post_types;
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
        $editor_post_types = self::getEditorPosttypes();
        $key = self::getKey();
        if (count($editor_post_types)) {
            ?>
			<select name="<?php 
            echo esc_attr($key);
            ?>[]" id="<?php 
            echo esc_attr($key);
            ?>" multiple="multiple">
			<?php 
            foreach ($editor_post_types as $post_type) {
                ?>
				<option
					value="<?php 
                echo esc_attr($post_type->name);
                ?>"
					<?php 
                selected(in_array($post_type->name, $value));
                ?>
				>
					<?php 
                echo esc_html($post_type->label);
                ?>
				</option>
			<?php 
            }
            ?>
			</select>
			<?php 
            Help::render_options_link('whitelist-blacklist/', 'whitelist', 'whitelist');
            ?>
		<?php 
        }
    }
    /**
     * Checks if a value is a valid value for option
     *
     * @param  mixed $value The value that gets validated
     * @return bool
     */
    public function isValidValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        $editor_post_types = self::getEditorPostTypes();
        $editor_post_types_names = array_map(function ($post_type) {
            return $post_type->name;
        }, $editor_post_types);
        foreach ($value as $val) {
            if (!in_array($val, $editor_post_types_names)) {
                return false;
            }
        }
        return true;
    }
}