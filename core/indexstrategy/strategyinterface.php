<?php

namespace ILJ\Core\IndexStrategy;

/**
 * IndexBuilder Strategy Interface
 *
 * Defines the interface for different indexbuilder strategies
 *
 * @package ILJ\Core\Indexbuilder;
 *
 * @since 1.2.0
 */
interface StrategyInterface
{
    /**
     * Responsible for building the index and writing possible internal links to it
     *
     * @since 1.0.1
     *
     * @return int The count of built index entries
     */
    public function setIndices();
    /**
     * Sets the link options
     *
     * @since 1.2.0
     * @param array $link_options The options
     *
     * @return void
     */
    public function setLinkOptions(array $link_options);
    /**
     * Responsible for building the index and writing possible internal links to it by batch
     *
     * @since 1.3.10
     * @param  mixed $batched_data
     * @param  mixed $batched_data_type
     * @param  mixed $keyword_offset
     * @param  mixed $keyword_type
     * @return void
     */
    public function setBatchedIndices($batched_data, $batched_data_type, $keyword_offset, $keyword_type);
}