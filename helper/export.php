<?php

namespace ILJ\Helper;

use ILJ\Type\KeywordList;
use ILJ\Database\LinkIndex;
/**
 * Export toolset
 *
 * Methods for data export
 *
 * @package ILJ\Helper
 * @since   1.2.0
 */
class Export
{
    const ILJ_EXPORT_CSV_FORMAT_HEADLINE = '"%1$s";"%2$s";"%3$s";"%4$s";"%5$s"';
    const ILJ_EXPORT_CSV_FORMAT_LINE = '"%1$d";"%2$s";"%3$s";"%4$s";"%5$s"';
    const ILJ_ADDITIONAL_COLUMNS = 'Sub-Type;Incoming;Outgoing';
    const ILJ_EXPORT_FIELD_SEPARATOR = ';';
    /**
     * Prints the headline for keyword export as CSV
     *
     * @since  1.2.0
     * @param  bool $verbose 				 Permits echo of headline output if true
     * @param  bool $include_additional_cols Permits output of additional colums
     * @return string
     */
    public static function printCsvHeadline($verbose = false, $include_additional_cols = false)
    {
        $headline_format = explode(self::ILJ_EXPORT_FIELD_SEPARATOR, self::ILJ_EXPORT_CSV_FORMAT_HEADLINE);
        $titles = array(__('ID', 'internal-links'), __('Type', 'internal-links'), __('Keywords (ILJ)', 'internal-links'), __('Title', 'internal-links'), __('Url', 'internal-links'));
        if ($include_additional_cols) {
            self::additional_cols_configuration($headline_format, $titles, null, true);
        }
        $headline = vsprintf(implode(self::ILJ_EXPORT_FIELD_SEPARATOR, $headline_format), $titles);
        if (!$verbose) {
            echo $headline;
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored since the data is printed to csv file.
        }
        return $headline;
    }
    /**
     * Converts all index relevant posts to CSV data
     *
     * @since  1.2.0
     * @param  bool $empty   				 Flag for output of empty entries
     * @param  bool $verbose 				 Permits echo of CSV output if true
     * @param  bool $include_additional_cols Permits output of additional colums
     * @return string
     */
    public static function printCsvPosts($empty, $verbose = false, $include_additional_cols = false)
    {
        $row_format = explode(self::ILJ_EXPORT_FIELD_SEPARATOR, self::ILJ_EXPORT_CSV_FORMAT_LINE);
        $csv = '';
        $posts = IndexAsset::getPosts(array('ID', 'post_title'));
        if (is_null($posts) || empty($posts)) {
            return '';
        }
        if ($include_additional_cols) {
            self::additional_cols_configuration($row_format);
        }
        foreach ($posts as $post) {
            $keyword_list = KeywordList::fromMeta($post->ID, 'post');
            if ($empty && !$keyword_list->getCount()) {
                continue;
            }
            $row_data = array($post->ID, 'post', $keyword_list->encoded(false), $post->post_title, get_permalink($post->ID));
            if ($include_additional_cols) {
                $null = null;
                self::additional_cols_configuration($null, $row_data, array(get_post_type($post->ID), LinkIndex::get_grouped_count_for_type('link_to', 'post', $post->ID), LinkIndex::get_grouped_count_for_type('link_from', 'post', $post->ID)));
            }
            $csv_curr = "\r\n";
            $csv_curr .= vsprintf(implode(self::ILJ_EXPORT_FIELD_SEPARATOR, $row_format), $row_data);
            if (!$verbose) {
                echo $csv_curr;
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ignored since the data is printed to csv file.
            }
            $csv .= $csv_curr;
        }
        return $csv;
    }
    /**
     * Add additional columns to the format and data for the export
     *
     * @param array $row_format  sprintf() formatting string. By reference, will be modified if an array is actually passed
     * @param array $row_data    data that will be passed as parameter for vsprintf(). By reference, will be modified if an array is actually passed
     * @param array $add_values  the values that should be added to the $row_data
     * @param bool  $is_headline if `true` then the $add_values is ignored and the data is picked up from the class const. If `false` the values are taken from $add_values
     * @return void
     */
    public static function additional_cols_configuration(&$row_format, &$row_data = null, $add_values = array(), $is_headline = false)
    {
        $addcols = explode(self::ILJ_EXPORT_FIELD_SEPARATOR, self::ILJ_ADDITIONAL_COLUMNS);
        $headline_format = explode(self::ILJ_EXPORT_FIELD_SEPARATOR, self::ILJ_EXPORT_CSV_FORMAT_HEADLINE);
        $headline_format_count = count($headline_format);
        foreach ($addcols as $colidx => $ac) {
            if (is_array($row_data)) {
                if ($is_headline) {
                    $row_data[] = __($ac);
                } else {
                    $row_data[] = array_shift($add_values);
                }
            }
            if (is_array($row_format)) {
                $row_format[] = '"%' . ($headline_format_count + $colidx + 1) . '$s"';
            }
        }
    }
}