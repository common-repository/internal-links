<?php

namespace ILJ\Core\Options;

use ILJ\Enumeration\KeywordOrder as KeywordOrderEnum;
/**
 * Option: Order of keywords
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class KeywordOrder extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'keyword_order';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return KeywordOrderEnum::FIFO;
    }
    /**
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Order for configured keywords while linking', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Set the order of how your set keywords get used for building links.', 'internal-links');
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
        $order_types = KeywordOrderEnum::getValues();
        ?>
		<select name="<?php 
        echo esc_attr($key);
        ?>" id="<?php 
        echo esc_attr($key);
        ?>">
			<?php 
        foreach ($order_types as $order_type) {
            ?>
				<option value="<?php 
            echo esc_attr($order_type);
            ?>" <?php 
            selected($order_type, $value);
            ?>> <?php 
            echo esc_html(KeywordOrderEnum::translate($order_type));
            ?> </option>
			<?php 
        }
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
        return in_array($value, KeywordOrderEnum::getValues());
    }
}