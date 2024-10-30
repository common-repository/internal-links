<?php

namespace ILJ\Core;

use ILJ\Core\Links\Text_To_Link_Converter_Interface;
use ILJ\Core\Links\Timeout_Monitor_Layer;
use ILJ\Core\Options;
use ILJ\Helper\Encoding;
use ILJ\Type\Ruleset;
use ILJ\Database\Linkindex;
use ILJ\Helper\Replacement;
use ILJ\Backend\Editor;
use ILJ\Database\LinkindexIndividualTemp;
use ILJ\Database\LinkindexTemp;
use ILJ\Helper\IndexAsset;
use ILJ\Helper\LinkBuilding;
/**
 * The below line sets the `ticks` directive  to prevent timeout occurring on the frontend.
 * This is set for the complete file, @see https://www.php.net/manual/en/control-structures.declare.php
 * This is needed for {@link Timeout_Monitor_Layer} to work, You might wonder why I didn't set this
 * on {@link Timeout_Monitor_Layer}. The reason for this is that the ticks won't be propagated to code in other files.
 * For example,
 *
 * `declare(ticks=1000) {
 *   $a = 20;
 *   $foo = new Foo()
 *   $foo->bar();
 * }`
 *
 * You might expect all statements inside bar() method would be tickable, but it won't behave like that.
 * You would need to set the ticks directive on the top of Foo.php in order to get
 * the same behavior.
 */
declare (ticks=1000);
/**
 * The main LinkBuilder class
 *
 * Is responsible for editing a piece of content and setting links within by a given Ruleset
 *
 * @package ILJ\Core
 * @since   1.0.0
 */
class LinkBuilder implements Text_To_Link_Converter_Interface
{
    /**
     * ID (post id / term id)
     *
     * @var   int
     * @since 1.0.0
     */
    private $id = null;
    /**
     * Type (post/term)
     *
     * @var   string
     * @since 1.0.1
     */
    private $type = null;
    /**
     * Link Ruleset
     *
     * @var   Ruleset
     * @since 1.0.0
     */
    private $link_ruleset = null;
    /**
     * Replace Ruleset
     *
     * @var   Ruleset
     * @since 1.0.0
     */
    private $replace_ruleset = null;
    /**
     * Content
     *
     * @var   string
     * @since 1.0.0
     */
    private $content = '';
    /**
     * Link page count
     *
     * @var int
     */
    private $link_page_count = 0;
    /**
     * Link target count
     *
     * @var array
     */
    private $link_target_count = array();
    /**
     * Used pattern
     *
     * @var array
     */
    private $used_pattern = array();
    /**
     * Link count per paragraph
     *
     * @var int
     */
    private $linkcount_per_paragraph = 0;
    /**
     * Meta Option. Determines if greedy mode is on/off. Enables linking as often as possible.
     *
     * @var bool
     */
    private $multi_keyword_mode = false;
    /**
     * Meta Option. Maximum number of outgoing links to be created in the current post/page. Zero means no limit
     *
     * @var int
     */
    private $links_per_page = 0;
    /**
     * Meta option. Maximum number of links to be created in the current target. Zero means no limit
     *
     * @var int
     */
    private $links_per_target = 0;
    /**
     * Meta option. Determine if it should go deeper into the paragraph or just continue
     *
     * @var int
     */
    private $links_per_paragraph_switch = 0;
    /**
     * Maximum number of links to be created in the current paragraph. Zero means no limit
     *
     * @var int
     */
    private $links_per_paragraph = 0;
    /**
     * The content type in which link replacements take place, defaults to html.
     *
     * @var boolean
     */
    private $content_type;
    /**
     * Constructor of ILJ_LinkBuilder
     *
     * @param int     $id           The ID of the current subject
     * @param string  $type         The type of the current subject
     * @param string  $build_type
     * @param boolean $content_type The content type in which link replacements take place, defaults to html.
     *
     * @return void
     * @since  1.0.1
     */
    public function __construct($id, $type, $build_type = null, $content_type = 'html')
    {
        $this->id = $id;
        $this->type = $type;
        $this->replace_ruleset = new Ruleset();
        $this->multi_keyword_mode = (bool) Options::getOption(\ILJ\Core\Options\MultipleKeywords::getKey());
        $this->links_per_page = (false === $this->multi_keyword_mode) ? Options::getOption(\ILJ\Core\Options\LinksPerPage::getKey()) : 0;
        $this->links_per_target = (false === $this->multi_keyword_mode) ? Options::getOption(\ILJ\Core\Options\LinksPerTarget::getKey()) : 0;
        $this->content_type = $content_type;
        $this->setupInLinks($build_type);
    }
    /**
     * Loads all ingoing links to current content from linkindex table and sets a new Ruleset type with it
     *
     * @since  1.0.0
     * @param  string $build_type
     * @return void
     */
    private function setupInLinks($build_type = null)
    {
        $this->link_ruleset = new Ruleset();
        $this->link_page_count = 0;
        $this->link_target_count = array();
        $this->used_pattern = array();
        if (is_null($build_type)) {
            $post_rules = Linkindex::getRules($this->id, $this->type);
        } elseif (IndexAsset::ILJ_FULL_BUILD == $build_type) {
            $post_rules = LinkindexTemp::getRules($this->id, $this->type);
        } elseif (IndexAsset::ILJ_INDIVIDUAL_BUILD == $build_type) {
            $post_rules = LinkindexIndividualTemp::getRules($this->id, $this->type);
        }
        foreach ($post_rules as $post_rule) {
            $this->link_ruleset->addRule($post_rule->anchor, $post_rule->link_to, $post_rule->type_to);
        }
    }
    /**
     * Return the linked content.
     *
     * @param mixed $content The content value passed to cache link value into
     * @return mixed 		 Mixed return value to cater different content compatibilities
     */
    public function link_content($content)
    {
        return $this->linkContent($content);
    }
    /**
     * Applies the link rules to a given piece of content
     *
     * @deprecated
     * @since  1.0.0
     * @param  string $content The content of the post, where the rules get applied
     * @return string
     */
    public function linkContent($content)
    {
        if (!LinkBuilding::is_filter_needed($this->id, $this->type)) {
            return $content;
        }
        $this->content = $content;
        $this->replace_ruleset = Replacement::mask($this->content);
        if ('' != $this->content) {
            $this->maskLinkRules();
            $this->applyReplaceRules();
        }
        return $this->content;
    }
    /**
     * Applies the given LinkRuleset for the document through masking within the content.
     *
     * @since  1.0.0
     * @return void
     */
    private function maskLinkRules()
    {
        $link_page_count = 0;
        $link_target_count = array();
        $outgoing_links_limit = 0;
        if ($this->links_per_page > 0) {
            $outgoing_links_limit = $this->links_per_page;
        }
        $temp_values = $this->createLinkIndex($this->content, 0, $link_page_count, $link_target_count, 0, false, $outgoing_links_limit);
        $full_content = $temp_values['content'];
        $this->link_ruleset->reset();
        $this->content = $full_content;
    }
    /**
     * Runs Logical Checks For Linking Rules and settings
     *
     * @since  1.2.15
     * @param  mixed $content
     * @param  mixed $index
     * @param  mixed $link_page_count
     * @param  mixed $link_target_count
     * @param  mixed $linkcount_per_paragraph
     * @param  mixed $loop_thru_per_paragraph
     * @param  mixed $outgoing_links_limit
     * @return array
     */
    protected function createLinkIndex($content, $index, $link_page_count, $link_target_count, $linkcount_per_paragraph, $loop_thru_per_paragraph, $outgoing_links_limit)
    {
        while ($this->link_ruleset->hasRule()) {
            $link_rule = $this->link_ruleset->getRule();
            if (0 != $outgoing_links_limit && 0 < $outgoing_links_limit) {
                if ($link_page_count == $outgoing_links_limit) {
                    break;
                }
            }
            if ($this->links_per_target > 0 && array_key_exists($link_rule->value, $link_target_count) && $link_target_count[$link_rule->value] >= $this->links_per_target || !$this->multi_keyword_mode && in_array($link_rule->pattern, $this->used_pattern)) {
                $this->link_ruleset->nextRule();
                continue;
            }
            $pattern = wptexturize($link_rule->pattern);
            $pattern = Encoding::escape_ascii($pattern);
            if (Options::getOption(Options\Case_Sensitive_Mode_Switch::getKey())) {
                preg_match_all('/' . Encoding::mask_pattern($pattern) . '/u', $content, $rule_match);
            } else {
                preg_match_all('/' . Encoding::mask_pattern($pattern) . '/ui', $content, $rule_match);
            }
            if (!isset($rule_match['phrase']) || !count($rule_match['phrase'])) {
                $this->link_ruleset->nextRule();
                continue;
            }
            $phrases = $rule_match['phrase'];
            foreach ($phrases as $rule) {
                if ($this->links_per_target > 0 && array_key_exists($link_rule->value, $link_target_count) && $link_target_count[$link_rule->value] == $this->links_per_target || !$this->multi_keyword_mode && in_array($link_rule->pattern, $this->used_pattern)) {
                    $this->link_ruleset->nextRule();
                    continue 2;
                }
                $rule_id = 'ilj_' . uniqid('', true);
                $link = $this->generateLink($link_rule, esc_html($rule));
                if (!$link) {
                    $this->link_ruleset->nextRule();
                    continue;
                }
                $rule = Encoding::escape_ascii($rule);
                if (Options::getOption(Options\Case_Sensitive_Mode_Switch::getKey())) {
                    $content = preg_replace('/' . Encoding::mask_pattern($rule) . '/u', $rule_id, $content, 1);
                } else {
                    $content = preg_replace('/' . Encoding::mask_pattern($rule) . '/ui', $rule_id, $content, 1);
                }
                $this->replace_ruleset->addRule($rule_id, $link);
                if (!array_key_exists($link_rule->value, $this->link_target_count)) {
                    $link_target_count[$link_rule->value] = 0;
                }
                $this->used_pattern[] = $link_rule->pattern;
                $link_target_count[$link_rule->value]++;
                $link_page_count++;
            }
            $this->link_ruleset->nextRule();
        }
        $obj = array('content' => $content, 'link_target_count' => $link_target_count, 'link_page_count' => $link_page_count);
        return $obj;
    }
    /**
     * Applies the configured masks and replaces the placeholders with the generated links.
     *
     * @since  1.0.0
     * @return void
     */
    private function applyReplaceRules()
    {
        while ($this->replace_ruleset->hasRule()) {
            $replace_rule = $this->replace_ruleset->getRule();
            $this->content = str_replace($replace_rule->pattern, $replace_rule->value, $this->content);
            $this->replace_ruleset->nextRule();
        }
        if (preg_match('/ilj\_[a-z0-9]{14}\.[0-9]{8}/', $this->content)) {
            $this->replace_ruleset->reset();
            $this->applyReplaceRules();
        }
    }
    /**
     * Returns the template for link output
     *
     * @since  1.0.0
     * @return string
     */
    private function getLinkTemplate()
    {
        $default_template = \ILJ\Core\Options\LinkOutputInternal::getDefault();
        $template = Options::getOption(\ILJ\Core\Options\LinkOutputInternal::getKey());
        if ('' == $template) {
            return $default_template;
        }
        return wp_specialchars_decode($template, \ENT_QUOTES);
    }
    /**
     * Generates the link markup
     *
     * @since  1.0.0
     * @param  string $link_rule The post where the link should point to
     * @param  string $anchor    The anchortext for the link
     * @return bool|string
     */
    private function generateLink($link_rule, $anchor)
    {
        $template = $this->getLinkTemplate();
        $nofollow = (bool) Options::getOption(\ILJ\Core\Options\InternalNofollow::getKey());
        $link_attrs = array();
        if ('post' == $link_rule->type) {
            if (get_post_status($link_rule->value) != 'publish') {
                return false;
            }
            $url = get_the_permalink($link_rule->value);
        }
        $link = str_replace('{{url}}', isset($url) ? $url : '#', $template);
        $link = str_replace('{{anchor}}', $anchor, $link);
        $check_nofollow = true;
        if ($check_nofollow && $nofollow) {
            $link = str_replace('<a ', '<a rel="nofollow" ', $link);
        }
        if ('json' === $this->content_type) {
            // If the content is json, the link should be escaped before replacement.
            $link = trim(json_encode($link), '"');
        }
        return $link;
    }
}