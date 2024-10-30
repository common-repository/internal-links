<?php

namespace ILJ\Helper;

use ILJ\Backend\User;
use ILJ\Cache\Transient_Cache;
use ILJ\Core\IndexBuilder;
use ILJ\Core\Options as CoreOptions;
use ILJ\Core\Options\CacheToggleBtnSwitch;
use ILJ\Core\Options\OptionInterface;
use ILJ\Core\ThemeCompat;
use ILJ\Database\Linkindex;
use ILJ\Enumeration\LinkType;
/**
 * Options toolset
 *
 * Methods for rendering of options administration form stuff
 *
 * @package ILJ\Helper
 */
class Options
{
    /**
     * Generates the title
     *
     * @since 1.1.3
     * @param OptionInterface $option The option
     *
     * @return string
     */
    public static function getTitle(OptionInterface $option)
    {
        if (!$option->isPro()) {
            return $option->getTitle();
        }
        return sprintf('<div class="pro-title"><span class="dashicons dashicons-lock tip" title="' . __('This feature is part of the Pro version', 'internal-links') . '"></span> %s</div>', $option->getTitle());
    }
    /**
     * Decides whether a form field is visually disabled
     *
     * @deprecated, use {@link self::render_disabler}
     * @since 1.1.3
     * @param OptionInterface $option The option
     *
     * @return string
     */
    public static function getDisabler(OptionInterface $option)
    {
        if (!$option->isPro()) {
            return '';
        }
        return ' class="pro-setting" disabled ';
    }
    /**
     * Disables a field if its not a premium installation
     *
     * @since 2.23.6
     * @param OptionInterface $option The option
     *
     * @return void
     */
    public static function render_disabler(OptionInterface $option)
    {
        $result = $option->isPro() ? ' class="pro-setting" disabled ' : '';
        echo $result;
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Below output doesn't need to be escaped.
    }
    /**
     * Renders the form field
     *
     * @since 1.1.3
     * @param OptionInterface $option The option
     * @param mixed           $value  The value
     *
     * @return void
     */
    public static function renderFieldComplete(OptionInterface $option, $value)
    {
        if ($option->isPro()) {
            echo '<div class="pro-setting">';
        }
        $option->renderField($value);
        echo ($option->getDescription() != '') ? '<p class="description">' . $option->getDescription() . '</p>' : '';
        echo ($option->getHint() != '') ? $option->getHint() : '';
        if ($option->isPro()) {
            echo '</div>';
        }
    }
    /**
     * Renders a fancy toggler for checkboxes that follow OptionInterface
     *
     * @since 1.1.3
     * @param OptionInterface $option  The Option
     * @param bool            $checked Active state of the toggler
     *
     * @return void
     */
    public static function renderToggle(OptionInterface $option, $checked)
    {
        $disabled = self::getDisabler($option) ? 'disabled' : '';
        self::render_toggle_field($option::getKey(), $checked, '' !== $disabled);
    }
    /**
     * Fundamental method for rendering a toggle output
     *
     * @since 1.2.0
     * @deprecated   Deprecated, use {@link self::render_toggle_field}
     * @param int    $id       The id/name of the checkbox input field
     * @param bool   $checked  Active state of the toggler
     * @param string $disabled HTML-Part for disabling form field
     *
     * @return void
     */
    public static function getToggleField($id, $checked, $disabled = '')
    {
        $output = '';
        $output .= '<div class="ilj-toggler-wrap">';
        $output .= sprintf('<input class="ilj-toggler-input" type="checkbox" id="%1$s" name="%1$s" value="1" %2$s %3$s />', $id, $checked, $disabled);
        $output .= sprintf('<label class="ilj-toggler-label" for="%s">', $id);
        $output .= '<div class="ilj-toggler-switch" aria-hidden="true">';
        $output .= '<div class="ilj-toggler-option-l" aria-hidden="true">';
        $output .= '<svg class="ilj-toggler-svg" xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0" width="548.9" height="548.9" viewBox="0 0 548.9 548.9" xml:space="preserve"><polygon points="449.3 48 195.5 301.8 99.5 205.9 0 305.4 95.9 401.4 195.5 500.9 295 401.4 548.9 147.5 "/></svg>';
        $output .= '</div>';
        $output .= '<div class="ilj-toggler-option-r" aria-hidden="true">';
        $output .= '<svg class="ilj-toggler-svg" xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0" viewBox="0 0 28 28" xml:space="preserve"><polygon points="28 22.4 19.6 14 28 5.6 22.4 0 14 8.4 5.6 0 0 5.6 8.4 14 0 22.4 5.6 28 14 19.6 22.4 28 " fill="#030104"/></svg>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</label>';
        $output .= '</div>';
        return $output;
    }
    /**
     * Fundamental method for printing toggle output
     *
     * @since 2.23.5
     * @param string $id       The id/name of the checkbox input field
     * @param bool   $checked  Active state of the toggler
     * @param bool   $disabled Disabled state of the toggler
     *
     * @return void
     */
    public static function render_toggle_field($id, $checked, $disabled)
    {
        ?>
		<div class="ilj-toggler-wrap">
			<input class="ilj-toggler-input" type="checkbox"
				   id="<?php 
        echo esc_attr($id);
        ?>"
				   name="<?php 
        echo esc_attr($id);
        ?>"
				   value="1"
				<?php 
        checked(1, $checked);
        ?>
				<?php 
        disabled($disabled);
        ?> />
			<label class="ilj-toggler-label" for="<?php 
        echo esc_attr($id);
        ?>">
				<div class="ilj-toggler-switch" aria-hidden="true">
					<div class="ilj-toggler-option-l" aria-hidden="true">
						<svg class="ilj-toggler-svg" xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0"
							 width="548.9" height="548.9" viewBox="0 0 548.9 548.9" xml:space="preserve"><polygon
								points="449.3 48 195.5 301.8 99.5 205.9 0 305.4 95.9 401.4 195.5 500.9 295 401.4 548.9 147.5 "/></svg>
					</div>
					<div class="ilj-toggler-option-r" aria-hidden="true">
						<svg class="ilj-toggler-svg" xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0"
							 viewBox="0 0 28 28" xml:space="preserve"><polygon
								points="28 22.4 19.6 14 28 5.6 22.4 0 14 8.4 5.6 0 0 5.6 8.4 14 0 22.4 5.6 28 14 19.6 22.4 28 "
								fill="#030104"/></svg>
					</div>
				</div>
			</label>
		</div>
		<?php 
    }
    /**
     * Triggers an individual index rebuild
     *
     * @param  int    $id
     * @param  string $type
     * @param  string $option
     * @return void
     */
    public static function triggerIndividualRebuildIndex($id, $type, $option)
    {
        User::update('index', array('last_trigger' => new \DateTime()));
        if (!defined('ILJ_THEME_COMPAT')) {
            ThemeCompat::init();
        }
        $index_builder = new IndexBuilder();
        $index_builder->buildIndividualIndex($id, $type, $option);
    }
    /**
     * Determines if full index rebuild or partial rebuild is needed after option is updated
     *
     * @param  String $option_name
     * @param  mixed  $old_value
     * @param  mixed  $value
     * @return void
     */
    public static function updateOptionIndexRebuild($option_name, $old_value, $value)
    {
        $partial_rebuild = array('ilj_settings_field_blacklist', 'ilj_settings_field_term_blacklist');
        $full_rebuild = array('ilj_settings_field_whitelist', 'ilj_settings_field_taxonomy_whitelist', 'ilj_settings_field_blacklist_child_pages', 'ilj_settings_field_keyword_order', 'ilj_settings_field_links_per_page', 'ilj_settings_field_links_per_paragraph_switch', 'ilj_settings_field_links_per_paragraph', 'ilj_settings_field_links_per_target', 'ilj_settings_field_multiple_keywords', 'ilj_settings_field_no_link_tags', 'ilj_settings_field_link_output_respect_existing_links', 'ilj_settings_field_limit_taxonomy_list', 'ilj_settings_field_custom_fields_to_link_post', 'ilj_settings_field_custom_fields_to_link_term', 'ilj_settings_field_case_sensitive_mode_switch', 'ilj_settings_field_limit_incoming_links', 'ilj_settings_field_max_incoming_links');
        $no_rebuild = array('ilj_settings_field_keep_settings', 'ilj_settings_field_editor_role', 'ilj_settings_field_index_generation', 'ilj_settings_field_link_output_internal', 'ilj_settings_field_internal_nofollow', 'ilj_settings_field_link_output_custom', 'ilj_settings_field_cache_toggle_btn_switch');
        if (in_array($option_name, $no_rebuild)) {
            if (CacheToggleBtnSwitch::getKey() == $option_name) {
                if ($old_value != $value) {
                    Transient_Cache::delete_all();
                }
            }
            return;
        } elseif (in_array($option_name, $partial_rebuild)) {
            if (!is_array($old_value)) {
                $old_value = array();
            }
            if (!is_array($value)) {
                $value = array();
            }
            if ($old_value == $value) {
                return;
            }
            $type = '';
            if ('ilj_settings_field_blacklist' == $option_name) {
                $type = 'post';
            } elseif ('ilj_settings_field_term_blacklist' == $option_name) {
                $type = 'term';
            }
            $removed = array_diff($old_value, $value);
            $new_added = array_diff($value, $old_value);
            $batch_build_info = new BatchInfo();
            if (is_array($new_added)) {
                foreach ($new_added as $id) {
                    $batch_build_info->incrementBatchCounter();
                    as_enqueue_async_action(IndexBuilder::ILJ_INDIVIDUAL_DELETE_INDEX, array(array('id' => $id, 'type' => $type)), BatchInfo::ILJ_ASYNC_GROUP);
                    $batch_build_info->incrementBatchCounter();
                    as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array('id' => $id, 'type' => $type, 'link_type' => LinkType::INCOMING)), BatchInfo::ILJ_ASYNC_GROUP);
                }
                $batch_build_info->updateBatchBuildInfo();
            }
            if (is_array($removed)) {
                foreach ($removed as $id) {
                    $batch_build_info->incrementBatchCounter();
                    as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array('id' => $id, 'type' => $type, 'link_type' => LinkType::OUTGOING)), BatchInfo::ILJ_ASYNC_GROUP);
                    $batch_build_info->incrementBatchCounter();
                    as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array('id' => $id, 'type' => $type, 'link_type' => LinkType::OUTGOING, 'type' => $type . '_meta')), BatchInfo::ILJ_ASYNC_GROUP);
                    $batch_build_info->incrementBatchCounter();
                    as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD, array(array('id' => $id, 'type' => $type, 'link_type' => LinkType::INCOMING)), BatchInfo::ILJ_ASYNC_GROUP);
                }
                $batch_build_info->updateBatchBuildInfo();
            }
        } elseif (in_array($option_name, $full_rebuild)) {
            if ($old_value != $value) {
                do_action(IndexBuilder::ILJ_INITIATE_BATCH_REBUILD, BatchInfo::ILJ_ASYNC_GROUP);
            }
        }
    }
}