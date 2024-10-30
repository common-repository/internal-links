<?php

namespace ILJ\Helper;

use ActionScheduler_Store;
use ILJ\Backend\BatchInfo as BackendBatchInfo;
use ILJ\Backend\Environment;
use ILJ\Core\Options;
use ILJ\Helper\Stopwatch;
/**
 * Batch info helper
 *
 * Methods for handling batch building information
 *
 * @package ILJ\Helper
 * @since   2.0.0
 */
class BatchInfo
{
    const ILJ_ASYNC_GROUP = 'ilj_async_link_index';
    const STATE_BUILDING = 'building';
    const STATE_COMPLETED = 'completed';
    const STATE_PROCESSING = 'processing';
    const STATE_NO_BATCH_SCHEDULED = 'no_batch_scheduled';
    public $batch_counter;
    public $batch_finished;
    public $batch_build;
    public $batch_status;
    public function __construct()
    {
        $this->batch_build = Options::getOption(Options::ILJ_OPTION_KEY_BATCH);
        if (is_array($this->batch_build) && !empty($this->batch_build)) {
            $this->batch_counter = number_format((int) $this->batch_build['batch_build']['last_update']['batch_count']);
            $this->batch_finished = number_format((int) $this->batch_build['batch_build']['last_update']['batch_finished']);
            $this->batch_status = $this->batch_build['batch_build']['last_update']['status'];
        }
        $this->checkPendingAsyncAction();
    }
    /**
     * Get Batch Counter
     *
     * @return int
     */
    public function getBatchCounter()
    {
        return $this->batch_counter;
    }
    public static function translateBatchStatus($status)
    {
        switch ($status) {
            case self::STATE_COMPLETED:
                return __('Build complete', 'internal-links');
            case self::STATE_PROCESSING:
            case self::STATE_BUILDING:
                return __('Currently building...', 'internal-links');
            case self::STATE_NO_BATCH_SCHEDULED:
                return __('Nothing scheduled', 'internal-links');
            default:
                return __('Unknown', 'internal-links');
        }
    }
    /**
     * Get Batch Finished value
     *
     * @return int
     */
    public function getBatchFinished()
    {
        return $this->batch_finished;
    }
    /**
     * Generate the Batch Status
     *
     * @since 2.0.0
     * @param  mixed $action
     * @return String
     */
    public function getBatchStatus($action = null)
    {
        $this->batch_status = self::STATE_BUILDING;
        if ($this->batch_finished >= $this->batch_counter) {
            return $this->batch_status = self::STATE_COMPLETED;
        }
        if ('calculating' == $action) {
            return $this->batch_status = self::STATE_PROCESSING;
        }
        if (false == $this->batch_build) {
            return $this->batch_status = self::STATE_NO_BATCH_SCHEDULED;
        }
        return $this->batch_status;
    }
    /**
     * Calculates and returns the progress in percentage of the current batch queue
     *
     * @return float
     */
    public function getBatchPercentage()
    {
        $batch_percentage = 0;
        if (!is_array($this->batch_build) && empty($this->batch_build) || 0 == $this->batch_counter) {
            $batch_percentage = 100;
            return floor($batch_percentage);
        }
        if (0 != $this->batch_counter && 0 != $this->batch_finished && (null != $this->batch_counter && null != $this->batch_finished)) {
            $batch_percentage = (int) $this->batch_finished / (int) $this->batch_counter * 100;
        }
        return floor($batch_percentage);
    }
    /**
     * Fetch an array of Batch Info
     *
     * @since  2.0.0
     * @return array
     */
    public function getBatchInfo()
    {
        $batch_build_info = array();
        $batch_percentage = $this->getBatchPercentage();
        $batch_count = $this->getBatchCounter();
        $batch_finished = $this->getBatchFinished();
        $status = $this->getBatchStatus();
        $is_complete = false;
        if (self::STATE_COMPLETED == $status || self::STATE_NO_BATCH_SCHEDULED == $status) {
            $is_complete = true;
        }
        $batch_build_info['batch_percentage'] = $batch_percentage;
        $batch_build_info['batch_count'] = $batch_count;
        $batch_build_info['batch_finished'] = $batch_finished;
        $batch_build_info['status'] = self::translateBatchStatus($this->batch_status);
        $batch_build_info['progress'] = $batch_percentage;
        $batch_build_info['is_complete'] = $is_complete;
        $linkindex_info = Environment::get('linkindex');
        if (!is_null($linkindex_info) && is_object($linkindex_info)) {
            $date = $linkindex_info['last_update']['date']->setTimezone(Stopwatch::timezone());
            $batch_build_info['linkindex_count'] = number_format($linkindex_info['last_update']['entries']);
            $batch_build_info['keywords_count'] = number_format(Statistic::get_all_configured_keywords_count());
            $batch_build_info['last_built'] = $date->format(get_option('date_format')) . ' ' . __('at', 'internal-links') . ' ' . $date->format(get_option('time_format'));
        }
        return $batch_build_info;
    }
    /**
     * Set Current Batch Counter value
     *
     * @param  int $batch_counter
     * @return void
     */
    public function setBatchCounter($batch_counter)
    {
        $this->batch_counter = $batch_counter;
    }
    /**
     * Reset the Batch Finished Counter
     *
     * @return void
     */
    public function resetBatchedFinished()
    {
        $this->batch_finished = 0;
    }
    /**
     * Increments the current batch counter by 1
     *
     * @return int
     */
    public function incrementBatchCounter()
    {
        if (isset($this->batch_status) && strtolower($this->batch_status) == self::STATE_COMPLETED) {
            $this->resetBatchedFinished();
            $this->batch_counter = 0;
        }
        return (int) $this->batch_counter++;
    }
    /**
     * Increments the current Batch Finish counter by 1
     *
     * @since  2.0.0
     * @return int|void
     */
    public function incrementBatchFinished()
    {
        if ($this->batch_finished >= $this->batch_counter) {
            return;
        }
        return (int) $this->batch_finished++;
    }
    /**
     * Updating Batch Info
     *
     * @since  2.0.0
     * @param  mixed $action
     * @return bool
     */
    public function updateBatchBuildInfo($action = null)
    {
        $status = $this->getBatchStatus($action);
        $feedback = array("last_update" => array("batch_count" => $this->batch_counter, "batch_finished" => $this->batch_finished, "last_update" => Stopwatch::timestamp(), "status" => $status));
        return BackendBatchInfo::update('batch_build', $feedback);
    }
    /**
     * Checks Pending Async Actions and auto corrects if Batch Info fails to render correctly
     * Prevents wrong batch info details
     *
     * @since  2.0.0
     * @return void
     */
    public function checkPendingAsyncAction()
    {
        $pending_actions = 0;
        $args = array('status' => ActionScheduler_Store::STATUS_PENDING, 'group' => self::ILJ_ASYNC_GROUP);
        $pending_actions += count(as_get_scheduled_actions($args));
        $args = array('status' => ActionScheduler_Store::STATUS_RUNNING, 'group' => self::ILJ_ASYNC_GROUP);
        $pending_actions += count(as_get_scheduled_actions($args));
        if (0 == $pending_actions && $this->batch_finished != $this->batch_counter) {
            // Auto correction for Batch info
            $this->batch_finished = $this->batch_counter;
            $this->updateBatchBuildInfo();
        }
    }
    /**
     * Resets the batch info data
     *
     * @return void
     */
    public static function reset_batch_info()
    {
        $feedback = array("last_update" => array("batch_count" => 1, "batch_finished" => 1, "last_update" => Stopwatch::timestamp(), "status" => self::STATE_COMPLETED));
        return BackendBatchInfo::update('batch_build', $feedback);
    }
}