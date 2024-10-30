<?php

namespace ILJ\Core\IndexStrategy;

use ILJ\Core\Options;
use ILJ\Database\Postmeta;
use ILJ\Database\WPML\Translations;
use ILJ\Enumeration\IndexMode;
use ILJ\Enumeration\LinkType;
use ILJ\Helper\Encoding;
use ILJ\Helper\IndexAsset;
use ILJ\Helper\Regex;
use ILJ\Helper\Blacklist;
use ILJ\Helper\Statistic;
use ILJ\Type\Ruleset;
/**
 * WPML compatible indexbuilder
 *
 * Takes care of interlinking only pages from the same language domain
 *
 * @package ILJ\Core\Indexbuilder
 *
 * @since 1.2.0
 */
class WPMLStrategy extends DefaultStrategy
{
    /**
     * Link Rules
     *
     * @var   array
     * @since 1.2.0
     */
    public $link_rules = array();
    /**
     * List of Languages
     *
     * @var   array
     * @since 1.2.0
     */
    public $languages = array();
    /**
     * List of blacklisted posts
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
    /**
     * Translations posts class cache
     *
     * @var   array
     */
    public $translation_posts = array();
    /**
     * Taxonomies class cache
     *
     * @var   array
     */
    public $translation_taxonomies = array();
    public function __construct()
    {
        $this->languages = self::getLanguages();
        $this->blacklisted_posts = Blacklist::getBlacklistedList('post');
    }
    /**
     * Get all active WPML languages
     *
     * @static
     * @since  1.2.0
     *
     * @return array
     */
    public static function getLanguages()
    {
        $languages = array();
        $languagesData = function_exists('icl_get_languages') ? icl_get_languages('skip_missing=0&orderby=code') : array();
        if (!count($languagesData)) {
            return $languages;
        }
        foreach ($languagesData as $language) {
            $languages[] = $language['code'];
        }
        return array_unique($languages);
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
     * Responsible for building the index and writing possible internal links to it by batch
     *
     * @param  mixed  $data
     * @param  string $data_type
     * @param  int    $keyword_offset
     * @param  string $keyword_type
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
     * Picks up all meta definitions for configured keywords by language and adds them to internal ruleset
     *
     * @since 1.2.0
     *
     * @return void
     */
    protected function loadLinkConfigurations()
    {
        $post_definitions = Postmeta::getAllLinkDefinitions();
        foreach ($this->languages as $language) {
            $this->link_rules[$language] = new Ruleset();
            foreach ($post_definitions as $definition) {
                if ($this->getDataLanguage($definition->post_id, 'post') != $language) {
                    continue;
                }
                $type = 'post';
                $anchors = unserialize($definition->meta_value);
                if (!$anchors || !is_array($anchors)) {
                    continue;
                }
                $anchors = $this->applyKeywordOrder($anchors);
                $this->addAnchorsToLinkRules($anchors, array('id' => $definition->post_id, 'type' => $type, 'language' => $language));
            }
        }
    }
    /**
     * Picks up all meta definitions for configured keywords by language and adds them to internal ruleset
     *
     * @since 1.2.0
     *
     * @param  int    $offset
     * @param  string $keyword_type
     *
     * @return void
     */
    public function loadLinkConfigurationsBatched($offset, $keyword_type)
    {
        if ('post' == $keyword_type) {
            $post_definitions = Postmeta::getAllLinkDefinitionsByBatch($offset);
            foreach ($this->languages as $language) {
                $this->link_rules[$language] = new Ruleset();
                foreach ($post_definitions as $definition) {
                    if ($this->getDataLanguage($definition->post_id, 'post') != $language) {
                        continue;
                    }
                    $type = 'post';
                    $anchors = unserialize($definition->meta_value);
                    if (!$anchors || !is_array($anchors)) {
                        continue;
                    }
                    $anchors = $this->applyKeywordOrder($anchors);
                    $this->addAnchorsToLinkRules($anchors, array('id' => $definition->post_id, 'type' => $type, 'language' => $language));
                }
            }
            return;
        }
    }
    /**
     * Picks up all meta definitions for configured keywords and adds them to internal ruleset for Individual index builds
     *
     * @param  mixed $id
     * @param  mixed $type
     * @return void
     */
    protected function loadLinkIndividualConfigurations($id, $type)
    {
        if ('post' == $type) {
            $post_definitions = Postmeta::getLinkDefinitionsById($id);
            foreach ($this->languages as $language) {
                $this->link_rules[$language] = new Ruleset();
                foreach ($post_definitions as $definition) {
                    if ($this->getDataLanguage($definition->post_id, 'post') != $language) {
                        continue;
                    }
                    $post_type = 'post';
                    $anchors = unserialize($definition->meta_value);
                    if (!$anchors || !is_array($anchors)) {
                        continue;
                    }
                    $anchors = $this->applyKeywordOrder($anchors);
                    $this->addAnchorsToLinkRules($anchors, array('id' => $definition->post_id, 'type' => $post_type, 'language' => $language));
                }
            }
        } elseif ('term' == $type) {
        }
    }
    /**
     * Get all relational translation meta data for posts
     *
     * @since 1.2.0
     *
     * @return array
     */
    protected function getTranslationsPosts()
    {
        if (empty($this->translation_posts)) {
            $this->translation_posts = Translations::getByElementType('post');
        }
        return $this->translation_posts;
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
        foreach ($this->languages as $language) {
            $IndexStrategy->setLinkRules($this->link_rules[$language]);
            $data_filtered = $this->filterDataByLanguage($data, $language, $data_type);
            $IndexStrategy->buildLinksPerData($data_filtered, $data_type, $scope, $link_type);
        }
        $counter = $IndexStrategy->getCounter();
    }
    /**
     * Adds anchors to link_rules
     *
     * @param  array $anchors
     * @param  array $params
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
            $this->link_rules[$params['language']]->addRule($pattern, $params['id'], $params['type']);
        }
    }
    /**
     * Get the language of any asset data (post, tax)
     *
     * @since 1.2.0
     * @param int    $data_id   The id of the asset
     * @param string $data_type The type of the asset (post, tax)
     *
     * @return string
     */
    protected function getDataLanguage($data_id, $data_type)
    {
        $translations_data = isset($translations_data) ? $translations_data : $this->getTranslationsPosts();
        foreach ($translations_data as $translation_data_single) {
            if ((int) $translation_data_single->element_id != $data_id) {
                continue;
            }
            return $translation_data_single->language_code;
        }
        return '';
    }
    /**
     * Filters a collection of data (posts, taxes) by a given language
     *
     * @since 1.2.0
     * @param array  $data      The data collection
     * @param string $language  The language code
     * @param string $data_type The type of the collection items
     *
     * @return array
     */
    protected function filterDataByLanguage($data, $language, $data_type)
    {
        $data_filtered = array();
        foreach ($data as $id) {
            if ('post' == $data_type || 'post_meta' == $data_type) {
                $current = get_post($id);
            }
            $data_id = isset($data_id) ? $data_id : $current->ID;
            $data_language = $this->getDataLanguage($data_id, $data_type);
            if ($data_language == $language) {
                $data_filtered[] = $data_id;
            }
            unset($data_id);
        }
        return $data_filtered;
    }
}