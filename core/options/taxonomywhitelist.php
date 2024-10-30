<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Whitelist for taxonomies
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class TaxonomyWhitelist extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'taxonomy_whitelist';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return array('category', 'post_tag');
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
        return __('Whitelist of taxonomies, that should be used for linking', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('All terms within the allowed taxonomies can link to other posts or terms automatically.', 'internal-links');
    }
    /**
     * Gets all taxonomy types that can be used with the plugin
     *
     * @since  1.2.0
     * @return array
     */
    public static function getTaxonomyTypes()
    {
        $taxonomy_types_default = get_taxonomies(array('public' => true, 'show_ui' => true), 'objects', 'and');
        $taxonomy_types_public = get_taxonomies(array('public' => true, '_builtin' => false), 'objects', 'and');
        $taxonomies = array_merge($taxonomy_types_default, $taxonomy_types_public);
        return array_values($taxonomies);
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
        $taxonomies = $this->getTaxonomyTypes();
        if (count($taxonomies)) {
            $key = self::getKey();
            ?>
			<select name="<?php 
            echo esc_attr($key);
            ?>[]" id="<?php 
            echo esc_attr($key);
            ?>" multiple="multiple" <?php 
            OptionsHelper::render_disabler($this);
            ?> >
				<?php 
            foreach ($taxonomies as $taxonomy) {
                ?>
					<option value="<?php 
                echo esc_attr($taxonomy->name);
                ?>" <?php 
                selected(in_array($taxonomy->name, $value));
                ?> ><?php 
                echo esc_html($taxonomy->label);
                ?></option>
				<?php 
            }
            ?>
			</select>
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
        return false;
    }
}