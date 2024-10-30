<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Regex_Custom_Field;
/**
 * Abstract class for the classes using custom fields
 *
 * @package ILJ\Core\Options
 */
abstract class Custom_Fields_Option extends AbstractOption
{
    /**
     * The custom fields posts with support for regex meta keys needs to be rendered on frontend upon saving.
     *
     * @param array $value
     *
     * @return void
     */
    protected function render_saved_regex_options($value)
    {
        if (!is_array($value)) {
            return;
        }
        foreach ($value as $item) {
            if (is_string($item) && Regex_Custom_Field::is_valid_rule($item)) {
                $field = Regex_Custom_Field::from($item);
                ?>
				<option value="<?php 
                echo esc_attr($item);
                ?>" selected><?php 
                echo esc_html($field->get_label());
                ?></option>
				<?php 
            }
        }
    }
}