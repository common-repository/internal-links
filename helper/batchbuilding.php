<?php

namespace ILJ\Helper;

use ActionScheduler;
use ActionScheduler_Store;
use ILJ\Backend\User;
use ILJ\Cache\Transient_Cache;
use ILJ\Core\IndexBuilder;
use ILJ\Core\ActionSchedulerConfig;
use ILJ\Core\Options;
use ILJ\Core\ThemeCompat;
use ILJ\Database\LinkindexIndividualTemp;
use ILJ\Database\LinkindexTemp;
use ILJ\Database\Postmeta;
use ILJ\Database\Termmeta;
use ILJ\Enumeration\LinkType;
use ILJ\Core\Options\SchedulerBatchSize as SchedulerBatchSize;
use ILJ\Database\Linkindex;
use ILJ\Helper\Stopwatch;
/**
 * Batch Building helper
 *
 * Methods for handling batching of index builds
 *
 * @package ILJ\Helper
 * @since   2.0.3
 */
class BatchBuilding
{
    const ILJ_FILTER_BUILDING_BATCH_SIZE = 'ilj_building_batch_size';
    const ILJ_FILTER_FETCH_BATCH_KEYWORD_SIZE = 'ilj_fetch_batch_keyword_size';
    const ILJ_FILTER_BATCH_SIZE = 'ilj_batch_size';
    const ILJ_FILTER_BATCH_KEYWORD_SIZE = 'ilj_batch_keyword_size';
    /**
     * Batch size
     *
     * @var   int
     * @since 2.0.3
     */
    public $building_batch_size;
    /**
     * Fetch batch keyword size
     *
     * @var   int
     * @since 2.0.3
     */
    public $fetch_batch_keyword_size;
    /**
     * Batch size
     *
     * @var   int
     * @since 2.0.3
     */
    public $batch_size;
    /**
     * Batch keyword size
     *
     * @var   int
     * @since 2.0.3
     */
    public $batch_keyword_size;
    public $post_keyword_batch = 0;
    public $term_keyword_batch = 0;
    public $individual_data;
    public function __construct()
    {
        $this->building_batch_size = 500;
        /**
         * Filter and change the building batch size
         *
         * @since 2.0.3
         *
         * @param int $building_batch_size
         */
        $this->building_batch_size = apply_filters(self::ILJ_FILTER_BUILDING_BATCH_SIZE, $this->building_batch_size);
        $this->fetch_batch_keyword_size = 100;
        /**
         * Filter and change the size of the fetched keywords size
         *
         * @since 2.0.3
         *
         * @param int $batch_keyword_size
         */
        $this->fetch_batch_keyword_size = apply_filters(self::ILJ_FILTER_FETCH_BATCH_KEYWORD_SIZE, $this->fetch_batch_keyword_size);
        $this->batch_size = 100;
        /**
         * Filter and change the batch size
         *
         * @since 2.0.3
         *
         * @param int $batch_size
         */
        $this->batch_size = apply_filters(self::ILJ_FILTER_BATCH_SIZE, $this->batch_size);
    }
    /**
     * Returns the batch keyword size
     *
     * @return void
     */
    public function get_fetch_batch_keyword_size()
    {
        return $this->fetch_batch_keyword_size;
    }
    /**
     * Set Scheduled Batch index builds
     *
     * @since 2.0.3
     * @return void
     */
    public function ilj_run_setting_batched_index_rebuild()
    {
        $stopwatch = new Stopwatch();
        LinkindexTemp::install_temp_db();
        $batch_build_info = new BatchInfo();
        $batch_build_info->resetBatchedFinished();
        // checking if whitelist are empty don't build links or if links are present delete links
        $whitelist_post = Options::getOption(\ILJ\Core\Options\Whitelist::getKey());
        $whitelisted_post_types = is_array($whitelist_post) ? $whitelist_post : array();
        $whitelisted_term_types = array();
        if (empty($whitelisted_post_types)) {
            Linkindex::delete_links_by_type("post");
            // This is a safety call to delete all links when license has expired from PRO
            Linkindex::delete_links_by_type("term");
        }
        if (empty($whitelisted_post_types) && empty($whitelisted_term_types)) {
            Statistic::updateStatisticsInfo();
            return;
        }
        $this->set_keyword_batching();
        $starting_type = $this->getStartingBuildType();
        self::ilj_set_batched_index_rebuild(array('offset' => 0, 'start_time' => $stopwatch->get_start_time(), 'type' => $starting_type, 'keyword_offset' => 0));
    }
    /**
     * Rebuilds the Index Individually
     *
     * @param  array $data
     * @return void
     */
    public function ilj_set_individual_index_rebuild($data)
    {
        $stopwatch = new Stopwatch();
        LinkindexIndividualTemp::install_temp_db();
        if (LinkType::OUTGOING == $data['link_type']) {
            $this->set_keyword_batching();
            $batch_build_info = new BatchInfo();
            $keyword_offset = 0;
            $meta = $data['type'];
            if (isset($data['meta'])) {
                $meta = $data['meta'];
            }
            // Log individual link build for correct execution of delete_for_individual_builds and importIndexFromTemp
            if (!LinkindexIndividualTemp::check_exists($data['id'], 0, '', $meta, '', $data['link_type'])) {
                LinkindexIndividualTemp::addRule($data['id'], 0, '', $meta, '', $data['link_type']);
            }
            for ($x = 0; $x < $this->post_keyword_batch; $x++) {
                as_enqueue_async_action(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_OUTGOING, array(array('id' => $data['id'], 'type' => $data['type'], 'batched_data_type' => $meta, 'start_time' => $stopwatch->get_start_time(), 'link_type' => $data['link_type'], 'keyword_offset' => $keyword_offset, 'keyword_type' => 'post')), BatchInfo::ILJ_ASYNC_GROUP);
                $keyword_offset += $this->fetch_batch_keyword_size;
                $batch_build_info->incrementBatchCounter();
                $batch_build_info->updateBatchBuildInfo();
            }
            if (!has_action('action_scheduler_completed_action', array($this, 'ilj_after_scheduler_completed_action_individual'))) {
                add_action('action_scheduler_completed_action', array($this, 'ilj_after_scheduler_completed_action_individual'), 25);
            }
        } elseif (LinkType::INCOMING == $data['link_type']) {
            // Log individual link build for correct execution of delete_for_individual_builds and importIndexFromTemp
            if (!LinkindexIndividualTemp::check_exists(0, $data['id'], '', '', $data['type'], $data['link_type'])) {
                LinkindexIndividualTemp::addRule(0, $data['id'], '', '', $data['type'], $data['link_type']);
            }
            $starting_type = $this->getStartingBuildType();
            $this->ilj_set_individual_index_rebuild_incoming(array('id' => $data['id'], 'offset' => 0, 'start_time' => $stopwatch->get_start_time(), 'type' => $data['type'], 'build_type' => $starting_type, 'link_type' => $data['link_type']));
            // Delete all transient after building the link index from link type incoming, as links could be generated everywhere, thus needing to flush all the transient. Unlike building the outgoing links which is clear that we only need to clear that specific post/page.
            Transient_Cache::delete_all();
        }
    }
    /**
     * Check whether to set build schedule for posts or term
     *
     * @param  mixed $data
     * @return void
     */
    public function ilj_set_individual_index_rebuild_incoming($data)
    {
        $this->set_keyword_batching();
        if ('post' == $data['build_type']) {
            $this->ilj_set_individual_post_index_rebuild($data);
        } elseif ('term' == $data['build_type']) {
            $this->ilj_set_individual_term_index_rebuild__premium_only($data);
        } elseif ('post_meta' == $data['build_type']) {
            $this->ilj_set_individual_post_meta_index_rebuild__premium_only($data);
        } elseif ('term_meta' == $data['build_type']) {
            $this->ilj_set_individual_term_meta_rebuild__premium_only($data);
        }
    }
    /**
     * Set individual build schedules for posts until all posts batch are scheduled
     *
     * @param  mixed $data
     * @return void
     */
    public function ilj_set_individual_post_index_rebuild($data)
    {
        $posts = IndexAsset::getPostsBatched($this->building_batch_size, $data['offset']);
        $post_batches = array();
        if (is_array($posts) && !empty($posts)) {
            $post_batches = array_chunk($posts, $this->batch_size, true);
        }
        $batch_build_info = new BatchInfo();
        foreach ($post_batches as $batch) {
            as_enqueue_async_action(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_INCOMING, array(array('id' => $data['id'], 'batched_data' => $batch, 'batched_data_type' => 'post', 'type' => $data['type'], 'build_type' => $data['build_type'], 'start_time' => $data['start_time'], 'link_type' => $data['link_type'], 'offset' => $data['offset'])), BatchInfo::ILJ_ASYNC_GROUP);
            $batch_build_info->incrementBatchCounter();
            $batch_build_info->updateBatchBuildInfo();
        }
        $data['offset'] += $this->building_batch_size;
        // Checking for possible next recursive schedule
        $posts = IndexAsset::getPostsBatched($this->building_batch_size, $data['offset']);
        if (empty($posts)) {
            if (!has_action('action_scheduler_completed_action', array($this, 'ilj_after_scheduler_completed_action_individual'))) {
                add_action('action_scheduler_completed_action', array($this, 'ilj_after_scheduler_completed_action_individual'), 25, 1);
            }
            return;
        }
        as_enqueue_async_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD_INCOMING, array(array('id' => $data['id'], 'offset' => $data['offset'], 'start_time' => $data['start_time'], 'type' => $data['type'], 'build_type' => $data['build_type'], 'link_type' => $data['link_type'])), BatchInfo::ILJ_ASYNC_GROUP);
    }
    /**
     * Triggers the individual index rebuild for outgoing
     *
     * @param  mixed $data
     * @return void
     */
    public function ilj_individual_index_rebuild_outgoing($data)
    {
        User::update('index', array('last_trigger' => new \DateTime()));
        if (!defined('ILJ_THEME_COMPAT')) {
            ThemeCompat::init();
        }
        $index_builder = new IndexBuilder();
        $index_builder->setBatchedKeywordOffset($data['keyword_offset']);
        $index_builder->setBatchedKeywordType($data['keyword_type']);
        $index_builder->setBatchedDataType($data['batched_data_type']);
        $index_builder->buildIndividualIndex($data['id'], $data['type'], $data['link_type']);
    }
    /**
     * Triggers the individual index rebuild for incoming
     *
     * @param  mixed $data
     * @return void
     */
    public function ilj_individual_index_rebuild_incoming($data)
    {
        User::update('index', array('last_trigger' => new \DateTime()));
        if (!defined('ILJ_THEME_COMPAT')) {
            ThemeCompat::init();
        }
        $index_builder = new IndexBuilder();
        $index_builder->setBatchedData($data['batched_data']);
        $index_builder->setBatchedDataType($data['batched_data_type']);
        $index_builder->buildIndividualIndex($data['id'], $data['type'], $data['link_type']);
    }
    /**
     * Calculate posts and term keyword batches count
     *
     * @return void
     */
    public function set_keyword_batching()
    {
        $post_keyword_count = Postmeta::getLinkDefinitionCount();
        $this->post_keyword_batch = (int) ($post_keyword_count / $this->fetch_batch_keyword_size);
        $batch_excess = $post_keyword_count % $this->fetch_batch_keyword_size;
        if ($batch_excess > 0) {
            $this->post_keyword_batch++;
        }
    }
    /**
     * Set Scheduled Batch index builds for post/term
     *
     * @since 2.0.3
     * @param  mixed $data
     * @return void
     */
    public function ilj_set_batched_index_rebuild($data)
    {
        $this->set_keyword_batching();
        if ('post' == $data['type']) {
            $this->ilj_set_batched_post_index_rebuild($data);
        }
    }
    /**
     * Setup batched index per keyword offset
     *
     * @param  mixed $batch
     * @param  mixed $data
     * @return void
     */
    public function set_looped_keywords($batch, $data)
    {
        $batch_build_info = new BatchInfo();
        $keyword_offset = 0;
        for ($x = 0; $x < $this->post_keyword_batch; $x++) {
            as_enqueue_async_action(IndexBuilder::ILJ_BUILD_BATCHED_INDEX, array(array('batched_data' => $batch, 'type' => $data['type'], 'start_time' => $data['start_time'], 'keyword_offset' => $keyword_offset, 'keyword_type' => 'post')), BatchInfo::ILJ_ASYNC_GROUP);
            $keyword_offset += $this->fetch_batch_keyword_size;
            $batch_build_info->incrementBatchCounter();
            $batch_build_info->updateBatchBuildInfo();
        }
    }
    /**
     * Set Scheduled Batch build for posts
     *
     * @param  mixed $data Containing Offset, start time and type
     * @return void
     */
    public function ilj_set_batched_post_index_rebuild($data)
    {
        $posts = IndexAsset::getPostsBatched($this->building_batch_size, $data['offset']);
        $post_batches = array();
        if (is_array($posts) && !empty($posts)) {
            $post_batches = array_chunk($posts, $this->batch_size, true);
        }
        foreach ($post_batches as $batch) {
            $this->set_looped_keywords($batch, $data);
        }
        $data['offset'] += $this->building_batch_size;
        // Checking for possible next recursive schedule
        $posts = IndexAsset::getPostsBatched($this->building_batch_size, $data['offset']);
        if (empty($posts)) {
            $nextBuild = $this->getNextBuildType($data['type']);
            if ('' == $nextBuild) {
                as_enqueue_async_action(IndexBuilder::ILJ_UPDATE_STATISTICS_INFO, array(array('switch' => true)), BatchInfo::ILJ_ASYNC_GROUP);
            }
            return;
        }
        as_enqueue_async_action(IndexBuilder::ILJ_SET_BATCHED_INDEX_REBUILD, array(array('offset' => $data['offset'], 'start_time' => $data['start_time'], 'type' => 'post')), BatchInfo::ILJ_ASYNC_GROUP);
    }
    /**
     * Executes the batch build per batched data
     *
     * @since 2.0.0
     * @param  array $data
     * @return void
     */
    public function ilj_build_batched_index($data)
    {
        if (!defined('ILJ_THEME_COMPAT')) {
            ThemeCompat::init();
        }
        $batch_build_info = new BatchInfo();
        $index_builder = new IndexBuilder();
        $index_builder->setBatchedData($data['batched_data']);
        $index_builder->setBatchedDataType($data['type']);
        $index_builder->setBatchedKeywordOffset($data['keyword_offset']);
        $index_builder->setBatchedKeywordType($data['keyword_type']);
        $index_builder->buildBatchedIndex();
        $batch_build_info->incrementBatchFinished();
        $batch_build_info->updateBatchBuildInfo();
    }
    /**
     * Update Statistics info and switchTable
     *
     * @param  mixed $data
     * @return void
     */
    public function ilj_update_statistics_info($data)
    {
        if (true == $data['switch']) {
            LinkindexTemp::switchTableTemp();
        } elseif (false == $data['switch']) {
            LinkindexIndividualTemp::importIndexFromTemp();
        }
        Statistic::updateStatisticsInfo();
    }
    /**
     * Add this call back to actionscheduler action_scheduler_completed_action hook
     *
     * @return void
     */
    public function ilj_after_scheduler_completed_action_individual()
    {
        $has_scheduled_actions = as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD);
        if ($has_scheduled_actions) {
            return;
        }
        $has_scheduled_actions = as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD_INCOMING);
        if ($has_scheduled_actions) {
            return;
        }
        $has_scheduled_actions = as_has_scheduled_action(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_OUTGOING);
        if ($has_scheduled_actions) {
            return;
        }
        $has_scheduled_actions = as_has_scheduled_action(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_INCOMING);
        if ($has_scheduled_actions) {
            return;
        }
        remove_all_actions('action_scheduler_completed_action', 25);
        if (false === as_has_scheduled_action(IndexBuilder::ILJ_UPDATE_STATISTICS_INFO, array(array('switch' => false)), BatchInfo::ILJ_ASYNC_GROUP)) {
            as_enqueue_async_action(IndexBuilder::ILJ_UPDATE_STATISTICS_INFO, array(array('switch' => false)), BatchInfo::ILJ_ASYNC_GROUP);
        }
    }
    /**
     * Initiate the ILJ Full index Rebuild and Unschedule any ongoing batches
     *
     * @since 2.0.0
     * @return void
     */
    public function initiate_ilj_batch_rebuild()
    {
        User::update('index', array('last_trigger' => new \DateTime()));
        if (!function_exists('as_has_scheduled_action')) {
            return;
        }
        if (true === as_has_scheduled_action(IndexBuilder::ILJ_RUN_SETTING_BATCHED_INDEX_REBUILD) || true === as_has_scheduled_action(IndexBuilder::ILJ_SET_BATCHED_INDEX_REBUILD) || true === as_has_scheduled_action(IndexBuilder::ILJ_BUILD_BATCHED_INDEX) || true === as_has_scheduled_action(IndexBuilder::ILJ_DELETE_INDEX_BY_ID) || true === as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD) || true === as_has_scheduled_action(IndexBuilder::ILJ_INDIVIDUAL_DELETE_INDEX) || true === as_has_scheduled_action(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_OUTGOING) || true === as_has_scheduled_action(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD_INCOMING) || true === as_has_scheduled_action(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_INCOMING)) {
            as_unschedule_all_actions(IndexBuilder::ILJ_RUN_SETTING_BATCHED_INDEX_REBUILD);
            as_unschedule_all_actions(IndexBuilder::ILJ_SET_BATCHED_INDEX_REBUILD);
            as_unschedule_all_actions(IndexBuilder::ILJ_BUILD_BATCHED_INDEX);
            as_unschedule_all_actions(IndexBuilder::ILJ_DELETE_INDEX_BY_ID);
            as_unschedule_all_actions(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD);
            as_unschedule_all_actions(IndexBuilder::ILJ_INDIVIDUAL_DELETE_INDEX);
            as_unschedule_all_actions(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_OUTGOING);
            as_unschedule_all_actions(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD_INCOMING);
            as_unschedule_all_actions(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_INCOMING);
            LinkindexTemp::flush();
        }
        Transient_Cache::delete_all();
        $batch_build_info = new BatchInfo();
        $batch_build_info->setBatchCounter(0);
        $batch_build_info->resetBatchedFinished();
        $batch_build_info->updateBatchBuildInfo('calculating');
        as_enqueue_async_action(IndexBuilder::ILJ_RUN_SETTING_BATCHED_INDEX_REBUILD, array(), BatchInfo::ILJ_ASYNC_GROUP);
    }
    /**
     * Get what type the index build will start from
     *
     * @return string
     */
    public function getStartingBuildType()
    {
        $whitelist_post = Options::getOption(\ILJ\Core\Options\Whitelist::getKey());
        if (is_array($whitelist_post) || is_object($whitelist_post) && $whitelist_post instanceof \Countable && count($whitelist_post) > 0) {
            return 'post';
        }
        return '';
    }
    /**
     * Get the next build type
     *
     * @param  mixed $currentType
     * @return string
     */
    public function getNextBuildType($currentType)
    {
        switch ($currentType) {
            case 'post':
                return '';
                break;
        }
        return '';
    }
    /**
     * Check if the custom scheduler batch size filter is set, and if not, set it
     *
     * @return void
     */
    public static function add_scheduler_batch_size_setting()
    {
        $hook = 'action_scheduler_queue_runner_batch_size';
        $handler = array('ILJ\Core\Options\SchedulerBatchSize', 'set_scheduler_batch_size');
        if (!has_filter($hook, $handler)) {
            add_filter($hook, $handler);
        }
    }
}