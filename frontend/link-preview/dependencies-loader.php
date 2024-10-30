<?php

namespace ILJ\Frontend\Link_Preview;

use ILJ\Core\Options;
/**
 * Dependencies_Loader
 *
 * Responsible for loading depedencies to render summary card in frontend.
 *
 * @package ILJ\Backend
 *
 * @since 2.23.5
 */
class Dependencies_Loader
{
    public static function wp_footer()
    {
        if (is_admin() || !is_single() && !is_archive()) {
            return;
        }
        // The script should only be loaded if its detects the links with link-preview attribute.
        ?>
		<!-- ILJ link preview dependencies start -->
		<template id="ilj-link-preview-template"><?php 
        echo wp_kses_post(Options::getOption(Options\Link_Preview_Template::getKey()));
        ?></template>
		<script>
			document.addEventListener("DOMContentLoaded", function () {
				if (document.querySelectorAll("a[data-ilj-link-preview]").length) {
					var script = document.createElement('script');
					script.async = true
					script.src = "<?php 
        echo esc_js(ILJ_URL . 'frontend/assets/js/ilj-link-preview.js');
        ?>"
					document.body.appendChild(script)
				}
			})
		</script>
		<!-- ILJ link preview dependencies end -->
		<?php 
    }
}