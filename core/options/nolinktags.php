<?php

namespace ILJ\Core\Options;

use ILJ\Enumeration\TagExclusion;
/**
 * Option: Html tags that don't get linked
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class NoLinkTags extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'no_link_tags';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return array(TagExclusion::HEADLINE);
    }
    /**
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Exclude HTML areas from linking', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Content within the HTML tags that are configured here do not get used for linking.', 'internal-links');
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
        $key = self::getKey();
        ?>
		<select name="<?php 
        echo esc_attr($key);
        ?>[]" id="<?php 
        echo esc_attr($key);
        ?>" multiple="multiple">
		<?php 
        foreach (TagExclusion::getValues() as $tag_exclusion) {
            $is_pro = (bool) !TagExclusion::getRegex($tag_exclusion);
            ?>
			<option value="<?php 
            echo esc_attr($tag_exclusion);
            ?>" <?php 
            selected(!$is_pro && in_array($tag_exclusion, $value));
            ?> <?php 
            disabled($is_pro);
            ?>>
				<?php 
            echo esc_html(TagExclusion::translate($tag_exclusion));
            echo esc_html($is_pro ? ' - ' . __('Pro feature', 'internal-links') : '');
            ?>
			</option>
		<?php 
        }
        ?>
		</select>
		<?php 
    }
    /**
     * Returns a hint text for the option, if given
     *
     * @return string
     */
    public function getHint()
    {
        return '';
    }
    /**
     * Checks if a value is a valid value for option
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValidValue($value)
    {
        $values = $value;
        $validValues = array(TagExclusion::HEADLINE, TagExclusion::STRONG);
        foreach ($values as $value) {
            if (!in_array($value, $validValues)) {
                return false;
            }
        }
        return true;
    }
}