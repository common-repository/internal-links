<?php

namespace ILJ\Backend\MenuPage\Tour;

/**
 * Abstract tour step
 *
 * Abstract class for all steps within the onboarding tour
 *
 * @package ILJ\Backend\Tour
 * @since   1.1.0
 */
abstract class Step
{
    /**
     * Feature row counter
     *
     * @var   int
     * @since 1.1.0
     */
    protected $feature_row_counter = 1;
    /**
     * Renders the content frame of the step
     *
     * @since  1.1.0
     * @return type
     */
    public function renderContent()
    {
    }
    /**
     * Block for a feature row
     *
     * @since  1.1.0
     * @param  array $data
     * @return void
     */
    protected function renderFeatureRow($data)
    {
        ?>
		<div class="ilj-row substep">
			<div class="counter"><?php 
        echo esc_html($this->feature_row_counter);
        ?></div>
			<div class="content">
				<h2><?php 
        echo wp_kses_post($data['title']);
        ?></h2>
				<p><?php 
        echo wp_kses_post($data['description']);
        ?></p>
			</div>
			<div class="video">
				<iframe width="100%"
						height="250"
						src="<?php 
        echo esc_url('https://www.youtube-nocookie.com/embed/' . $data['video'] . '?rel=0&color=white&showinfo=1&cc_load_policy=1');
        ?>"
						frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen>
				</iframe>
			</div>
			<div class="clear"></div>
		</div>
		<?php 
        $this->feature_row_counter++;
    }
}