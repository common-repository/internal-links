<?php

namespace ILJ\Core\IndexStrategy;

use ILJ\Core\Options;
use ILJ\Database\Postmeta;
use ILJ\Enumeration\IndexMode;
use ILJ\Enumeration\KeywordOrder;
use ILJ\Enumeration\LinkType;
use ILJ\Helper\Encoding;
use ILJ\Helper\IndexAsset;
use ILJ\Helper\Keyword;
use ILJ\Helper\Regex;
use ILJ\Helper\Blacklist;
use ILJ\Helper\Statistic;
use ILJ\Type\Ruleset;
/**
 * Default indexbuilder
 *
 * The default index builder strategy
 *
 * @package ILJ\Core\Indexbuilder
 *
 * @since 1.2.0
 */
class DefaultStrategy implements StrategyInterface
{
    /**
     * Link Rules
     *
     * @var   Ruleset
     * @since 1.0.0
     */
    public $link_rules;
    /**
     * Link Options
     *
     * @var   array
     * @since 1.0.1
     */
    public $link_options = array();
    /**
     * List of blacklisted Posts
     *
     * @var   array
     * @since 1.2.15
     */
    public $blacklisted_posts = array();
    /**
     * List of blacklisted terms
     *
     * @var   array
     * @since 1.2.15
     */
    public $blacklisted_terms = array();
    public function __construct()
    {
        $this->link_rules = new Ruleset();
        $this->blacklisted_posts = Blacklist::getBlacklistedList('post');
    }
    /**
     * Responsible for building the index and writing possible internal links to it
     *
     * @return void
     */
    public function setIndices()
    {
        $index_count = 0;
        $this->loadLinkConfigurations();
        $posts = IndexAsset::getPosts();
        if (is_array($posts) && !empty($posts)) {
            $this->writeToIndex($posts, 'post', array('id' => 'ID', 'content' => 'post_content'), $index_count, IndexAsset::ILJ_FULL_BUILD, IndexMode::NONE);
        }
        return $index_count;
    }
    /**
     * Set Batched Index Builds
     *
     * @param  mixed $data
     * @param  mixed $data_type
     * @param  mixed $keyword_offset
     * @param  mixed $keyword_type
     * @return void
     */
    public function setBatchedIndices($data, $data_type, $keyword_offset, $keyword_type)
    {
        $index_count = 0;
        $this->loadLinkConfigurationsBatched($keyword_offset, $keyword_type);
        if ('post' == $data_type) {
            $posts = $data;
            if (is_array($posts) && !empty($posts)) {
                $this->writeToIndex($posts, 'post', array('id' => 'ID', 'content' => 'post_content'), $index_count, IndexAsset::ILJ_FULL_BUILD, IndexMode::AUTOMATIC);
            }
        }
        return $index_count;
    }
    /**
     * Set individual index builds for post/term/metas in automatic mode
     *
     * @param  mixed $id
     * @param  mixed $type
     * @param  mixed $link_type
     * @param  mixed $keyword_offset
     * @param  mixed $keyword_type
     * @param  mixed $batched_data
     * @param  mixed $batched_data_type
     * @return int
     */
    public function setIndividualIndices($id, $type, $link_type, $keyword_offset, $keyword_type, $batched_data, $batched_data_type)
    {
        $index_count = Statistic::getLinkIndexCount();
        $posts = array();
        if (LinkType::OUTGOING == $link_type) {
            $this->loadLinkConfigurationsBatched($keyword_offset, $keyword_type);
            if ('post' == $batched_data_type) {
                array_push($posts, $id);
            }
            if ('post' == $batched_data_type) {
                $this->writeToIndex($posts, 'post', array('id' => 'ID', 'content' => 'post_content'), $index_count, IndexAsset::ILJ_INDIVIDUAL_BUILD, IndexMode::AUTOMATIC, $link_type);
            }
        } elseif (LinkType::INCOMING == $link_type) {
            $this->loadLinkIndividualConfigurations($id, $type);
            $data = $batched_data;
            if ('post' == $batched_data_type) {
                $this->writeToIndex($data, 'post', array('id' => 'ID', 'content' => 'post_content'), $index_count, IndexAsset::ILJ_INDIVIDUAL_BUILD, IndexMode::AUTOMATIC, $link_type);
            }
        }
        return $index_count;
    }
    /**
     * Set the Link options value
     *
     * @param  array $link_options
     * @return void
     */
    public function setLinkOptions(array $link_options)
    {
        $this->link_options = $link_options;
    }
    /**
     * Picks up all meta definitions for configured keywords and adds them to internal ruleset
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function loadLinkConfigurations()
    {
        $post_definitions = Postmeta::getAllLinkDefinitions();
        foreach ($post_definitions as $definition) {
            $type = 'post';
            $anchors = unserialize($definition->meta_value);
            if (!$anchors || !is_array($anchors)) {
                continue;
            }
            $anchors = $this->applyKeywordOrder($anchors);
            $this->addAnchorsToLinkRules($anchors, array('id' => $definition->post_id, 'type' => $type));
        }
    }
    /**
     * Picks up all meta definitions for configured keywords and adds them to internal ruleset
     *
     * @since 1.0.0
     *
     * @param  mixed $offset
     * @param  mixed $keyword_type
     *
     * @return void
     */
    public function loadLinkConfigurationsBatched($offset, $keyword_type)
    {
        if ('post' == $keyword_type) {
            $post_definitions = Postmeta::getAllLinkDefinitionsByBatch($offset);
            foreach ($post_definitions as $definition) {
                $post_type = 'post';
                $anchors = unserialize($definition->meta_value);
                if (!$anchors || !is_array($anchors)) {
                    continue;
                }
                $anchors = $this->applyKeywordOrder($anchors);
                $this->addAnchorsToLinkRules($anchors, array('id' => $definition->post_id, 'type' => $post_type));
            }
            return;
        }
    }
    /**
     * Picks up all meta definitions for configured keywords and adds them to internal ruleset for Individual index builds
     *
     * @param  int    $id
     * @param  string $type
     * @return void
     */
    protected function loadLinkIndividualConfigurations($id, $type)
    {
        if ('post' == $type) {
            $post_definitions = Postmeta::getLinkDefinitionsById($id);
            foreach ($post_definitions as $definition) {
                $post_type = 'post';
                $anchors = unserialize($definition->meta_value);
                if (!$anchors || !is_array($anchors)) {
                    continue;
                }
                $anchors = $this->applyKeywordOrder($anchors);
                $this->addAnchorsToLinkRules($anchors, array('id' => $definition->post_id, 'type' => $post_type));
            }
            return;
        }
    }
    /**
     * Adds anchors to link_rules
     *
     * @since 1.2.0
     * @param array $anchors The bag of anchor texts
     * @param array $params  The params
     *
     * @return void
     */
    protected function addAnchorsToLinkRules(array $anchors, array $params)
    {
        foreach ($anchors as $anchor) {
            $anchor = Encoding::unmaskSlashes($anchor);
            if (!Regex::isValid($anchor)) {
                continue;
            }
            $pattern = Regex::escapeDot($anchor);
            $this->link_rules->addRule($pattern, $params['id'], $params['type']);
        }
    }
    /**
     * Reorders the configured anchors depending on the plugin settings
     *
     * @since 1.2.0
     * @param array $anchors The bag of anchor texts
     *
     * @return array
     */
    protected function applyKeywordOrder(array $anchors)
    {
        $keyword_order = Options::getOption(\ILJ\Core\Options\KeywordOrder::getKey());
        switch ($keyword_order) {
            case KeywordOrder::HIGH_WORDCOUNT_FIRST:
                usort($anchors, function ($a, $b) {
                    return Keyword::gapWordCount($b) - Keyword::gapWordCount($a);
                });
                break;
            case KeywordOrder::LOW_WORDCOUNT_FIRST:
                usort($anchors, function ($a, $b) {
                    return Keyword::gapWordCount($a) - Keyword::gapWordCount($b);
                });
                break;
            case KeywordOrder::HIGH_CHARCOUNT_FIRST:
                usort($anchors, function ($a, $b) {
                    return Keyword::gapCharacterCount($b) - Keyword::gapCharacterCount($a);
                });
                break;
            case KeywordOrder::LOW_CHARCOUNT_FIRST:
                usort($anchors, function ($a, $b) {
                    return Keyword::gapCharacterCount($a) - Keyword::gapCharacterCount($b);
                });
                break;
        }
        return $anchors;
    }
    /**
     * Writes a set of data to the linkindex
     *
     * @since 1.0.1
     *
     * @param  array  $data       The data container
     * @param  string $data_type  Type of the data inside the container
     * @param  array  $fields     Field settings for the container objects
     * @param  int    $counter    Counts the written operations
     * @param  mixed  $scope
     * @param  mixed  $build_mode
     * @param  mixed  $link_type
     * @return void
     */
    protected function writeToIndex($data, $data_type, array $fields, &$counter, $scope, $build_mode, $link_type = null)
    {
        if (!is_array($data) || !count($data)) {
            return;
        }
        $fields = wp_parse_args($fields, array('id' => '', 'content' => ''));
        $IndexStrategy = new IndexStrategy($data_type, $fields, $this->link_options, $build_mode);
        $IndexStrategy->setCounter($counter);
        $IndexStrategy->setLinkRules($this->link_rules);
        $IndexStrategy->buildLinksPerData($data, $data_type, $scope, $link_type);
        $counter = $IndexStrategy->getCounter();
    }
}