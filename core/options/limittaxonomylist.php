<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: List of taxonomies used for limiting links
 *
 * @package ILJ\Core\Options
 * @since   1.2.14
 */
class LimitTaxonomyList extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'limit_taxonomy_list';
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
        return __('Taxonomies, that limit linking within their terms', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Articles within these hierarchical taxonomies link only to articles who have the same category term.', 'internal-links');
    }
    /**
     * Gets all taxonomy types that can be used with the plugin
     *
     * @since  1.2.14
     * @return array
     */
    public static function getTaxonomyTypes()
    {
        $taxonomy_types_default = get_taxonomies(array('hierarchical' => true, 'public' => true, 'show_ui' => true), 'objects', 'and');
        $taxonomy_types_public = get_taxonomies(array('hierarchical' => true, 'public' => true, '_builtin' => false), 'objects', 'and');
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
        $key = self::getKey();
        if (count($taxonomies)) {
            ?>
			<select
				name="<?php 
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
            foreach ($taxonomies as $taxonomy) {
                ?>
					<option value="<?php 
                echo esc_attr($taxonomy->name);
                ?>" <?php 
                selected(in_array($taxonomy->name, $value));
                ?>>
						<?php 
                echo esc_html($taxonomy->label);
                ?>
					</option>
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