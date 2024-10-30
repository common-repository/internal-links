<?php

namespace ILJ\Backend\MenuPage;

use ILJ\Backend\AdminMenu;
/**
 * Interactive Tour
 *
 * Builds a guided tour for a comfortable plugin onboarding
 *
 * @package ILJ\Backend\MenuPage
 *
 * @since 1.1.0
 */
class Tour extends AbstractMenuPage
{
    const ILJ_MENUPAGE_TOUR_SLUG = 'tour';
    const ILJ_TOUR_HANDLE = 'ilj_tour';
    /**
     * Steps array
     *
     * @var   array
     * @since 1.1.0
     */
    private $steps = array();
    /**
     * Action
     *
     * @var   string
     * @since 1.1.0
     */
    private $action = '';
    /**
     * Current Step
     *
     * @var   Step
     * @since 1.1.0
     */
    private $current_step = null;
    public function __construct()
    {
        $this->steps = array(array('slug' => 'intro', 'src' => '\ILJ\Backend\MenuPage\Tour\Intro'), array('slug' => 'editor', 'src' => '\ILJ\Backend\MenuPage\Tour\Editor'), array('slug' => 'links', 'cta' => __('Adjust the link behavior', 'internal-links'), 'src' => '\ILJ\Backend\MenuPage\Tour\Links'), array('slug' => 'settings', 'cta' => __('Discover the most important settings', 'internal-links'), 'src' => '\ILJ\Backend\MenuPage\Tour\Settings'), array('slug' => 'pro', 'cta' => __('Advanced linking with pro version', 'internal-links'), 'src' => '\ILJ\Backend\MenuPage\Tour\Pro'));
        if (isset($_GET['action'])) {
            $this->action = htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8');
        }
        $this->page_slug = self::ILJ_MENUPAGE_TOUR_SLUG;
        $this->page_title = __('Interactive Tour', 'internal-links');
    }
    /**
     * register
     *
     * @return void
     */
    public function register()
    {
        if (!$this->isTourPage()) {
            return;
        }
        $this->addSubmenuPage();
        \ILJ\Helper\Loader::enqueue_style(self::ILJ_TOUR_HANDLE, ILJ_URL . 'admin/css/ilj_tour.css', array('wp-admin', 'buttons'), ILJ_VERSION);
        add_action('admin_init', array($this, 'render'), 30);
    }
    /**
     * render
     *
     * @return void
     */
    public function render()
    {
        if (!$this->isTourPage()) {
            return;
        }
        $this->setCurrentStep();
        if (ob_get_length()) {
            ob_end_clean();
        }
        ob_start();
        $this->renderHeader();
        $this->current_step->renderContent();
        $this->renderFooter();
        exit;
    }
    /**
     * Checks if the current requested page is a tour page
     *
     * @since  1.1.0
     * @return boolean
     */
    protected function isTourPage()
    {
        if (isset($_GET['page']) && AdminMenu::ILJ_MENUPAGE_SLUG . '-' . self::ILJ_MENUPAGE_TOUR_SLUG == $_GET['page']) {
            return true;
        }
        return false;
    }
    /**
     * Sets the current step
     *
     * @since  1.1.0
     * @return void
     */
    protected function setCurrentStep()
    {
        if (isset($_GET['step'])) {
            for ($i = 0; $i < count($this->steps); $i++) {
                if ($this->steps[$i]['slug'] == $_GET['step']) {
                    $this->current_step = new $this->steps[$i]['src']();
                    return;
                }
            }
        }
        $this->current_step = new $this->steps[0]['src']();
    }
    /**
     * Renders the header for a tour page
     *
     * @since  1.1.0
     * @return void
     */
    protected function renderHeader()
    {
        ?>
		<!DOCTYPE html>
		<html <?php 
        language_attributes();
        ?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<title><?php 
        echo esc_html($this->getTitle() . ' - Internal Link Juicer');
        ?></title>
			<?php 
        wp_print_head_scripts();
        wp_print_styles(self::ILJ_TOUR_HANDLE);
        ?>
		</head>
		<body class="<?php 
        echo esc_attr('ilj-' . self::ILJ_MENUPAGE_TOUR_SLUG . ' wp-core-ui');
        ?>">
		<div id="wrap">
		<header>
			<div class="box">
				<img src="<?php 
        echo esc_attr(ILJ_URL . 'admin/img/ilj-icon-inverted.png');
        ?>" alt="<?php 
        esc_attr_e('Internal Link Juicer Logo', 'internal-links');
        ?>">
			</div>
			<div class="box">
				<h1><?php 
        esc_html_e('Internal Link Juicer', 'internal-links');
        ?></h1>
				<p class="subline"><?php 
        esc_html_e('Interactive tutorial', 'internal-links');
        ?></p>
			</div>
			<div class="clear"></div>
		</header>
		<main>
		<?php 
    }
    /**
     * Renders the footer for a tour page
     *
     * @since  1.1.0
     * @return void
     */
    protected function renderFooter()
    {
        $previous_step = $this->getStepNavigation('prev');
        $next_step = $this->getStepNavigation('next');
        $next_label = isset($next_step['cta']) ? $next_step['cta'] : ((null === $previous_step) ? __('Start the tutorial now', 'internal-links') : ((null === $next_step) ? __('Finish tutorial', 'internal-links') : __('Next page', 'internal-links')));
        $previous_label = ('after-activation' == $this->action) ? __('Skip interactive tour', 'internal-links') : __('Back to dashboard', 'internal-links');
        $dashboard_url = add_query_arg(array('page' => AdminMenu::ILJ_MENUPAGE_SLUG), admin_url('admin.php'));
        if (null === $next_step) {
            $next_step = array('url' => $dashboard_url);
            $dashboard_url = null;
        }
        ?>
		</main>
		<footer>
			<div class="left">
				<?php 
        if ($previous_step) {
            ?>
					<a href="<?php 
            echo esc_url($previous_step['url']);
            ?>" class="button">&lsaquo; <?php 
            esc_html_e('Previous page', 'internal-links');
            ?></a>
				<?php 
        }
        ?>
			</div>
			<div class="<?php 
        echo esc_attr($previous_step ? 'right' : 'only');
        ?>">
				<a href="<?php 
        echo esc_url($next_step['url']);
        ?>" class="button button-primary"><?php 
        echo esc_html($next_label);
        ?> &rsaquo;</a>
			</div>
			<div class="clear"></div>
		</footer>
		</div>
		<?php 
        if (null !== $dashboard_url) {
            ?>
			<div class="leave">
				<a class="button" href="<?php 
            echo esc_url($dashboard_url);
            ?>">
					&lsaquo; <?php 
            echo esc_html($previous_label);
            ?>
				</a>
			</div>
		<?php 
        }
        ?>
		</body>
		<?php 
        wp_print_footer_scripts();
        ?>
		</html>
		<?php 
    }
    /**
     * Get a step based navigation for the current tour page
     *
     * @since  1.1.0
     * @param  string $direction
     * @return array|null
     */
    protected function getStepNavigation($direction)
    {
        $navigation = null;
        if (!in_array($direction, array('next', 'prev'))) {
            return $navigation;
        }
        for ($i = 0; $i < count($this->steps); $i++) {
            if ('\\' . get_class($this->current_step) == $this->steps[$i]['src']) {
                if ('next' == $direction && count($this->steps) - 1 === $i || 'prev' == $direction && 0 === $i) {
                    return $navigation;
                }
                $index = ('next' == $direction) ? $i + 1 : ($i - 1);
                $navigation = $this->steps[$index];
                break;
            }
        }
        $navigation['url'] = add_query_arg(array('page' => AdminMenu::ILJ_MENUPAGE_SLUG . '-' . self::ILJ_MENUPAGE_TOUR_SLUG, 'step' => $navigation['slug']), admin_url('admin.php'));
        return $navigation;
    }
}