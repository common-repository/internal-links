<?php

namespace ILJ\Helper;

use ILJ\Database\Postmeta;
use ILJ\Posttypes\CustomLinks;
use ILJ\Type\KeywordList;
use ILJ\Helper\Statistic;
/**
 * Toolset for keywords
 *
 * Methods for keyword (-phrases)
 *
 * @package ILJ\Helper
 * @since   1.0.4
 */
class Keyword
{
    /**
     * From the 6th column onwards, the export only has information for the user
     * The code ignores anything after 5th column, but we need to make sure we have at least 5 columns
     * This allows for compatibility between exports from different versions
     */
    const ILJ_IMPORT_CSV_FORMAT_MINIMUM_REQUIRED_COLUMNS = 5;
    /**
     * Calculates an effective word count value with respect to configured gaps
     *
     * @since  1.0.4
     * @param  string $keyword The (keyword-) phrase where words get counted
     * @return int
     */
    public static function gapWordCount($keyword)
    {
        $word_count = count(explode(' ', $keyword));
        preg_match_all('/{(?:1,)?(\d),?}/', $keyword, $matches);
        if (isset($matches[1])) {
            $word_count -= count($matches[1]);
            foreach ($matches[1] as $match) {
                $word_count += (int) $match;
            }
        }
        return $word_count;
    }
    /**
     * Imports keywords for indexable assets from a given CSV file
     *
     * @since 1.2.0
     * @param string $file The path to the CSV file
     *
     * @return int
     */
    public static function importKeywordsFromFile($file)
    {
        ini_set('auto_detect_line_endings', true);
        $import_count = 0;
        if (!file_exists($file)) {
            return $import_count;
        }
        $handle = fopen($file, 'r');
        for ($i = 0; $row = fgetcsv($handle, 0, ';'); ++$i) {
            if (0 === $i) {
                continue;
            }
            if (!is_array($row) || count($row) < self::ILJ_IMPORT_CSV_FORMAT_MINIMUM_REQUIRED_COLUMNS) {
                continue;
            }
            $allowed_import_types = array('post');
            if (!in_array($row[1], $allowed_import_types) || '' == $row[2]) {
                continue;
            }
            $id = is_numeric($row[0]) ? (int) $row[0] : null;
            $type = $row[1];
            $keywords = KeywordList::fromInput($row[2]);
            $existing_keywords = KeywordList::fromMeta($id, $type);
            if (!$existing_keywords->hasAdditionalKeys($keywords)) {
                continue;
            }
            $existing_keywords->merge($keywords);
            if ('post' == $type) {
                if (!$id || !get_post($id)) {
                    continue;
                }
                update_post_meta($id, Postmeta::ILJ_META_KEY_LINKDEFINITION, $existing_keywords->getKeywords());
            }
            $import_count++;
        }
        fclose($handle);
        Statistic::count_all_configured_keywords();
        return $import_count;
    }
    /**
     * Calculates an effective character count value
     *
     * @since  1.2.15
     * @param  string $keyword The (keyword-) phrase where words get counted
     * @return int
     */
    public static function gapCharacterCount($keyword)
    {
        $character_count = strlen($keyword);
        return $character_count;
    }
}