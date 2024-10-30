<?php

namespace ILJ\Core\Links;

use ILJ\Core\LinkBuilder;
class Timeout_Monitor_Layer extends Layer
{
    private $allowed_processing_time_in_secs;
    /**
     * Initialize the allowed procesing time
     *
     * @param  int   $allowed_processing_time_in_secs
     * @param  mixed $delegate
     */
    public function __construct($allowed_processing_time_in_secs, $delegate)
    {
        $this->allowed_processing_time_in_secs = $allowed_processing_time_in_secs;
        parent::__construct($delegate);
    }
    /**
     * Return the linked content.
     *
     * @param mixed $content The input content.
     * @return mixed  		 Mixed return value to cater different content compatibilities
     */
    public function link_content($content)
    {
        if (is_array($content) && empty($content)) {
            return $content;
        } elseif (is_string($content) && empty(trim($content))) {
            // If empty, don't pass it to other layers.
            return $content;
        }
        $start_time = time();
        $tick_callback = function () use ($start_time) {
            if (time() - $start_time >= $this->allowed_processing_time_in_secs) {
                throw new \Exception('Time limit exceeded');
            }
        };
        try {
            /**
             * How the below callback is invoked even though there are no ticks set on this file ?
             * The `ticks` declaration is done on {@link LinkBuilder} which is passed as a delegate
             * to this class since it also implements {@link Text_To_Link_Converter_Interface}.
             */
            register_tick_function($tick_callback);
            $result = $this->delegate->link_content($content);
        } catch (\Throwable $throwable) {
            $result = $content;
        } finally {
            /**
             * Ensure that we don't listen to any tick events after this method is completed.
             */
            unregister_tick_function($tick_callback);
            return $result;
        }
    }
}