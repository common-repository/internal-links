<?php

namespace ILJ\Backend\MenuPage\Tour;

use ILJ\Backend\AdminMenu;
use ILJ\Backend\MenuPage\Tour\Step;
/**
 * Step: Pro
 *
 * Shows an overview of pro functions
 *
 * @package ILJ\Backend\Tour
 * @since   1.1.0
 */
class Pro extends Step
{
    /**
     * renderContent
     *
     * @return void
     */
    public function renderContent()
    {
        ?>
		<h1><?php 
        esc_html_e('Reach even more with Pro.', 'internal-links');
        ?></h1>
		<?php 
        $data_container = array(array('title' => __('Enjoy maximum freedom with custom links', 'internal-links'), 'description' => '<p>' . __('With the custom links feature, you assign <strong>keywords to any URLs</strong> for automatic linking.', 'internal-links') . ' ' . __('In this way, you can create <strong>affiliate links</strong> or add <strong>content from foreign domains</strong>.', 'internal-links') . '</p><p>' . __('Settings for this feature can be found in the Internal Link Juicer menu under "Custom Links”, where you can list current links and create new individual links in no time.', 'internal-links') . '</p><p>' . __('Like ordinary posts, these links are equipped with the Keyword Editor.', 'internal-links') . ' ' . __('To create a new custom link, you can add the <strong>destination URL</strong>, as well as any <strong>related keywords</strong>.', 'internal-links') . ' ' . __('The configuration takes effect immediately.', 'internal-links') . '</p><p>' . __('In the video, you see one example of linking to an external Wikipedia article.', 'internal-links') . '</p>', 'video' => '--7J8br4CuM'), array('title' => __('Automatically link categories and tags for even wider coverage', 'internal-links'), 'description' => '<p>' . __('In our Pro version, you will not only set links for posts and pages, but also <strong>taxonomies such as categories and tags</strong>. This gives you even more flexibility when creating internal links.', 'internal-links') . '</p><p>' . __('For taxonomies, you’ll find your own blacklist and whitelist.', 'internal-links') . ' ' . __('After activating the Pro version, the Keyword Editor includes taxonomies and allows you to assign keywords for them.', 'internal-links') . ' ' . __('From then on, even <strong>taxonmy descriptions</strong> are used as linkable content.', 'internal-links') . '</p><p>' . __('The video shows you how to link a post from a category description.', 'internal-links') . ' ' . __('In the same example, the article links from its own content back to the category page.', 'internal-links') . '</p>', 'video' => 'ga0vvsD5h0Y'), array('title' => __('Optimize links to the max with extensive statistical insight', 'internal-links'), 'description' => '<p>' . __('With the <strong>extended Statistics Dashboard</strong>, you can <strong>analyze</strong> your automated internal links to the maximum.', 'internal-links') . ' ' . __('This feature identifies pages that have too many or few inbound/outbound links.', 'internal-links') . ' ' . __('You can use this information to <strong>proactively improve internal linking</strong> optimization.', 'internal-links') . '</p><p>' . __('You can find the advanced statistics directly in the Statistics Dashboard for the plugin.', 'internal-links') . ' ' . __('Here you can <strong>sort and filter</strong> as you like.', 'internal-links') . ' ' . __('You can also get information about the links when clicking on the corresponding content or anchor text.', 'internal-links') . '</p><p>' . __('The video shows you everything you need to know about the Statistics Dashboard.', 'internal-links') . '</p>', 'video' => 'j2c0C88zrSk'));
        foreach ($data_container as $data) {
            $this->renderFeatureRow($data);
        }
        ?>
			<div class="ilj-row substep promo">
				<a href="<?php 
        echo esc_url(get_admin_url(null, 'admin.php?page=' . AdminMenu::ILJ_MENUPAGE_SLUG . '-pricing'));
        ?>">
					&#10140; <?php 
        echo wp_kses(__('Get these features (and many more) by <strong>unlocking</strong> our <strong>PRO version here</strong>', 'internal-links'), array('strong' => array()));
        ?>
				</a>
			</div>
			<?php 
    }
}