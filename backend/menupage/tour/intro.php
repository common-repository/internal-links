<?php

namespace ILJ\Backend\MenuPage\Tour;

use ILJ\Backend\MenuPage\Tour\Step;
/**
 * Step: Intro
 *
 * Gives a brief description of the tour
 *
 * @package ILJ\Backend\Tour
 * @since   1.1.0
 */
class Intro extends Step
{
    /**
     * renderContent
     *
     * @return void
     */
    public function renderContent()
    {
        ?>
		<div class="intro">
			<div class="banner">
				<img src="<?php 
        echo esc_url(ILJ_URL . '/admin/img/character-onboarding.png');
        ?>" />
			</div>
			<div class="content">
				<h2><?php 
        esc_html_e('Start the tour through the plugin', 'internal-links');
        ?></h2>
				<p>
					<?php 
        esc_html_e('We show you the most important functions of the Internal Link Juicer in a few minutes.', 'internal-links');
        ?>
					<?php 
        esc_html_e('With that you are able to start immediately and get the maximum out of your internal links.', 'internal-links');
        ?>
				</p>
			</div>
		</div>
		<?php 
    }
}