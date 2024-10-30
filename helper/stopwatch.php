<?php

namespace ILJ\Helper;

/**
 * Stopwatch helper
 *
 * Keeps track of time for logging purposes
 *
 * @package ILJ\Helper
 * @since 2.23.4
 */
class Stopwatch
{
    /**
     * Start time value
     *
     * @var float
     */
    private $start;
    /**
     * Setup the stopwatch initial values
     *
     * @param  mixed $start
     * @return void
     */
    public function __construct($start = null)
    {
        if (null == $start) {
            $this->start = microtime(true);
        } else {
            $this->start = $start;
        }
    }
    /**
     * Get current timestamp
     *
     * @return DateTime
     */
    public static function timestamp(): \DateTime
    {
        return new \DateTime('now', self::timezone());
    }
    /**
     * Get current timezone
     *
     * @return DateTimeZone
     */
    public static function timezone(): \DateTimeZone
    {
        $offset = get_option('gmt_offset');
        $hours = (int) $offset;
        $minutes = ($offset - floor($offset)) * 60;
        return new \DateTimeZone(sprintf('%+03d:%02d', $hours, $minutes));
    }
    /**
     * Get the runtime of the stopwatch
     *
     * @return float
     */
    public function duration(): float
    {
        return round(microtime(true) - $this->start, 2);
    }
    /**
     * Get the actual start time
     *
     * @return float
     */
    public function get_start_time(): float
    {
        return $this->start;
    }
}