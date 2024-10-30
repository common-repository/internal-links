<?php

namespace ILJ\Core\Options;

use ILJ\Helper\Help;
use ILJ\Enumeration\IndexMode;
use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Index generation mode
 *
 * @package ILJ\Core\Options
 * @since   1.1.3
 */
class IndexGeneration extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'index_generation';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return IndexMode::AUTOMATIC;
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
        return __('Index generation mode', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Choose your preferred approach for generating the index.', 'internal-links');
    }
    /**
     * Returns a hint text for the option, if given
     *
     * @return string
     */
    public function getHint()
    {
        return '<ul class="description">' . '<li><p class="description"><code>' . __('None', 'internal-links') . '</code>: ' . __('The index is not created by the plugin (you should set up a cronjob). Read more in our', 'internal-links') . ' <a href="' . Help::getLinkUrl('index-generation-mode/', 'mode-none', 'index generation mode', 'settings') . '" target="_blank" rel="noopener">' . __('manual', 'internal-links') . '</a>.</p></li>' . '<li><p class="description"><code>' . __('Automatic', 'internal-links') . '</code>: ' . __('Any change affecting the index automatically updates the index.', 'internal-links') . '</p></li>' . '</ul>';
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
        if (IndexMode::NONE != $value && IndexMode::AUTOMATIC != $value) {
            $value = self::getDefault();
        }
        $key = self::getKey();
        ?>
		<select name="<?php 
        echo esc_attr($key);
        ?>" id="<?php 
        echo esc_attr($key);
        ?>" <?php 
        OptionsHelper::render_disabler($this);
        ?>>
			<option value="<?php 
        echo esc_attr(IndexMode::NONE);
        ?>" <?php 
        selected($value, IndexMode::NONE);
        ?>><?php 
        esc_html_e('None', 'internal-links');
        ?></option>
			<option value="<?php 
        echo esc_attr(IndexMode::AUTOMATIC);
        ?>" <?php 
        selected($value, IndexMode::AUTOMATIC);
        ?>><?php 
        esc_html_e('Automatic', 'internal-links');
        ?></option>
		</select>
		<?php 
        Help::render_options_link('index-generation-mode/', '', 'index generation mode');
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