<?php

namespace ILJ\Core\Options;

use ILJ\Core\IndexBuilder;
use ILJ\Helper\Options as OptionsHelper;
/**
 * Option: Link Summary Card Template
 *
 * @package ILJ\Core\Options
 * @since   2.24.4
 */
class Link_Preview_Template extends AbstractOption
{
    /**
     * Get the unique identifier for the option
     *
     * @return string
     */
    public static function getKey()
    {
        return self::ILJ_OPTIONS_PREFIX . 'link_preview_template';
    }
    /**
     * Get the default value of the option
     *
     * @return mixed
     */
    public static function getDefault()
    {
        return '<div class="ilj-link-preview">
				{% if featuredImage and featuredImage != "" %}
					<img class="ilj-link-preview-featured-image" src="{{ featuredImage }}">
				{% endif %}
				{% if excerpt %}
					<p class="ilj-link-preview-excerpt">{{ excerpt | truncatewords: 55 }}</p>
				{%  endif %}
			</div>';
    }
    /**
     * Get the frontend label for the option
     *
     * @return string
     */
    public function getTitle()
    {
        return __('Template for the Link Preview', 'internal-links');
    }
    /**
     * Get the frontend description for the option
     *
     * @return string
     */
    public function getDescription()
    {
        return __('Customize the template for rendering the link preview content.', 'internal-links');
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
     * Outputs the options form element for backend administration
     *
     * @param  mixed $value
     * @return mixed
     */
    public function renderField($value)
    {
        $key = self::getKey();
        ?>
		<div id="ilj-link-preview-template-container">
			<textarea name="<?php 
        echo esc_attr($key);
        ?>" id="<?php 
        echo esc_attr($key);
        ?>"><?php 
        echo esc_html($value);
        ?></textarea>
			<button type="button" class="button" onclick="ilj_reset_link_template_to_default()" id="ilj_settings_field_link_preview_template_reset_to_default"><?php 
        esc_html_e('Reset to Default', 'internal-links');
        ?></button>
		</div>
		<template id="ilj_link_preview_default_template">
			<?php 
        echo esc_html(self::getDefault());
        ?>
		</template>
		<script>
			function ilj_html_decode(input) {
				var doc = new DOMParser().parseFromString(input, "text/html");
				return doc.documentElement.textContent;
			}
			function ilj_reset_link_template_to_default() {
				var template = document.getElementById('ilj_link_preview_default_template')
				document.getElementById('<?php 
        echo esc_js($key);
        ?>').value = ilj_html_decode(template.innerHTML.trim());
			}
		</script>
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
        return is_string($value) && $value;
    }
}