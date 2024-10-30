<?php

namespace ILJ\Core\Links;

/**
 * Interface for transforming text in to linked content.
 */
interface Text_To_Link_Converter_Interface
{
    /**
     * Return the linked content.
     *
     * @param mixed $content The content value passed to cache link value into
     * @return mixed 		 Mixed return value to cater different content compatibilities
     */
    public function link_content($content);
}