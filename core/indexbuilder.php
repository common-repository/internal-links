<?php

namespace ILJ\Core;

use ILJ\Backend\BatchInfo;
use ILJ\Core\IndexStrategy\DefaultStrategy;
use ILJ\Core\IndexStrategy\StrategyInterface;
use ILJ\Backend\Environment;
use ILJ\Database\LinkindexTemp;
use ILJ\Helper\BatchInfo as HelperBatchInfo;
use ILJ\Helper\Stopwatch;
/**
 * IndexBuilder
 *
 * This class is responsible for the creation of the links
 *
 * @package ILJ\Core
 *
 * @since 1.0.0
 */
class IndexBuilder
{
    const ILJ_ACTION_AFTER_INDEX_BUILT = 'ilj_after_index_built';
    const ILJ_FILTER_INDEX_STRATEGY = 'ilj_index_strategy';
    const ILJ_INITIATE_BATCH_REBUILD = 'initiate_ilj_batch_rebuild';
    const ILJ_SET_BATCHED_INDEX_REBUILD = 'ilj_set_batched_index_rebuild';
    const ILJ_BUILD_BATCHED_INDEX = 'ilj_build_batched_index';
    const ILJ_DELETE_INDEX_BY_ID = 'ilj_delete_index_by_id';
    const ILJ_SET_INDIVIDUAL_INDEX_REBUILD = 'ilj_set_individual_index_rebuild';
    const ILJ_INDIVIDUAL_INDEX_REBUILD_OUTGOING = 'ilj_individual_index_rebuild_outgoing';
    const ILJ_INDIVIDUAL_INDEX_REBUILD_INCOMING = 'ilj_individual_index_rebuild_incoming';
    const ILJ_SET_INDIVIDUAL_INDEX_REBUILD_INCOMING = 'ilj_set_individual_index_rebuild_incoming';
    const ILJ_INDIVIDUAL_DELETE_INDEX = 'ilj_individual_delete_index';
    const ILJ_RUN_SETTING_BATCHED_INDEX_REBUILD = 'ilj_run_setting_batched_index_rebuild';
    const ILJ_UPDATE_STATISTICS_INFO = 'ilj_update_statistics_info';
    /**
     * Strategy variable
     *
     * @var   StrategyInterface|null
     * @since 1.2.0
     */
    public $strategy = null;
    public $link_options;
    public $batched_data = array();
    public $batched_data_type = '';
    public $keyword_offset = 0;
    public $keyword_type = '';
    public function __construct()
    {
        $link_options = array();
        $link_options['multi_keyword_mode'] = (bool) Options::getOption(\ILJ\Core\Options\MultipleKeywords::getKey());
        $link_options['links_per_page'] = (false === $link_options['multi_keyword_mode']) ? Options::getOption(\ILJ\Core\Options\LinksPerPage::getKey()) : 0;
        $link_options['links_per_target'] = (false === $link_options['multi_keyword_mode']) ? Options::getOption(\ILJ\Core\Options\LinksPerTarget::getKey()) : 0;
        $this->link_options = $link_options;
        $strategy = new DefaultStrategy();
        /**
         * Filter and change the strategy that gets used to build the index
         *
         * WARNING: Changing this data may throw an exception.
         *
         * @since 1.2.0
         *
         * @param StrategyInterface $strategy
         */
        $strategy = apply_filters(self::ILJ_FILTER_INDEX_STRATEGY, $strategy);
        if (!$strategy instanceof StrategyInterface) {
            throw new \Exception('The filtered strategy must implement StrategyInterface.');
        }
        $strategy->setLinkOptions($link_options);
        $this->setStrategy($strategy);
    }
    /**
     * Executes all processes for building a new index
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function buildIndex()
    {
        if (!$this->strategy) {
            return array();
        }
        LinkindexTemp::install_temp_db();
        $stopwatch = new Stopwatch();
        $status_update = array("last_update" => array("batch_count" => 1, "batch_finished" => 0, "last_update" => Stopwatch::timestamp(), "status" => HelperBatchInfo::STATE_BUILDING));
        BatchInfo::update('batch_build', $status_update);
        $entries_count = $this->strategy->setIndices();
        $duration = $stopwatch->duration();
        $feedback = array("last_update" => array("date" => Stopwatch::timestamp(), "entries" => $entries_count, "duration" => $duration));
        Environment::update('linkindex', $feedback);
        LinkindexTemp::switchTableTemp();
        $status_update = array("last_update" => array("batch_count" => 1, "batch_finished" => 1, "last_update" => Stopwatch::timestamp(), "status" => HelperBatchInfo::STATE_COMPLETED));
        BatchInfo::update('batch_build', $status_update);
        /**
         * Fires after the index got built.
         *
         * @since 1.0.0
         */
        do_action(self::ILJ_ACTION_AFTER_INDEX_BUILT);
        return $feedback;
    }
    /**
     * Set the individual index builds
     *
     * @param  int    $id
     * @param  string $type
     * @param  string $link_type
     * @return void
     */
    public function buildIndividualIndex($id, $type, $link_type)
    {
        if (!$this->strategy) {
            return array();
        }
        $this->strategy->setIndividualIndices($id, $type, $link_type, $this->keyword_offset, $this->keyword_type, $this->batched_data, $this->batched_data_type);
        /**
         * Fires after the index got built.
         *
         * @since 1.0.0
         */
        do_action(self::ILJ_ACTION_AFTER_INDEX_BUILT);
    }
    /**
     * Sets the index building strategy
     *
     * @since 1.2.0
     * @param StrategyInterface $strategy The strategy that gets used to build the index
     *
     * @return void
     */
    public function setStrategy(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }
    /**
     * Set the batched data value
     *
     * @param  mixed $batched_data
     */
    public function setBatchedData($batched_data)
    {
        $this->batched_data = $batched_data;
    }
    /**
     * Set the Type of the batched data
     *
     * @param  string $batched_data_type
     */
    public function setBatchedDataType($batched_data_type)
    {
        $this->batched_data_type = $batched_data_type;
    }
    /**
     * Set the keyword offset
     *
     * @param  int $keyword_offset
     */
    public function setBatchedKeywordOffset($keyword_offset)
    {
        $this->keyword_offset = $keyword_offset;
    }
    /**
     * Set the type of the batched keyword
     *
     * @param  string $keyword_type
     */
    public function setBatchedKeywordType($keyword_type)
    {
        $this->keyword_type = $keyword_type;
    }
    /**
     * Executes batched process for building a new index
     *
     * @since 1.3.10
     *
     * @return void
     */
    public function buildBatchedIndex()
    {
        if (!$this->strategy) {
            return array();
        }
        $this->strategy->setBatchedIndices($this->batched_data, $this->batched_data_type, $this->keyword_offset, $this->keyword_type);
    }
}