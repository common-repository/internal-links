<?php

namespace ILJ\Helper;

use ILJ\Core\IndexBuilder;
use ILJ\Database\Linkindex;
use ILJ\Database\LinkindexIndividualTemp;
use ILJ\Database\LinkindexTemp;
/**
 * Cleanup toolset
 *
 * Methods for clearing database and scheduled actions
 *
 * @package ILJ\Helper
 *
 * @since 2.1.2
 */
class Cleanup
{
    /**
     * Initiate the cleanup process
     *
     * @return void
     */
    public static function initiate_cleanup()
    {
        self::clean_scheduled_actions();
        self::clean_database();
    }
    /**
     * Cleans all scheduled actions
     *
     * @return void
     */
    public static function clean_scheduled_actions()
    {
        // Cancel all ongoing actions
        as_unschedule_all_actions(IndexBuilder::ILJ_RUN_SETTING_BATCHED_INDEX_REBUILD);
        as_unschedule_all_actions(IndexBuilder::ILJ_SET_BATCHED_INDEX_REBUILD);
        as_unschedule_all_actions(IndexBuilder::ILJ_BUILD_BATCHED_INDEX);
        as_unschedule_all_actions(IndexBuilder::ILJ_DELETE_INDEX_BY_ID);
        as_unschedule_all_actions(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD);
        as_unschedule_all_actions(IndexBuilder::ILJ_INDIVIDUAL_DELETE_INDEX);
        as_unschedule_all_actions(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_OUTGOING);
        as_unschedule_all_actions(IndexBuilder::ILJ_SET_INDIVIDUAL_INDEX_REBUILD_INCOMING);
        as_unschedule_all_actions(IndexBuilder::ILJ_INDIVIDUAL_INDEX_REBUILD_INCOMING);
    }
    /**
     * Resets all database entries and delete temp database
     *
     * @return void
     */
    public static function clean_database()
    {
        // Flush index
        Linkindex::flush();
        // delete if exists temp database
        LinkindexIndividualTemp::uninstall_temp_db();
        LinkindexTemp::uninstall_temp_db();
        // reset batch info ilj_batch_info
        BatchInfo::reset_batch_info();
        // reset statistics data
        Statistic::reset_statistics_info();
    }
}