<?php

namespace ILJ\Core\Links;

/**
 * The below class acts as abstract class for all the layers.
 * The Layer classes uses decorator pattern, so that each layer has specific responsibility.
 */
abstract class Layer implements Text_To_Link_Converter_Interface
{
    /**
     * The delegate which this request must be passed.
     *
     * @var Text_To_Link_Converter_Interface
     */
    protected $delegate;
    public function __construct($delegate)
    {
        $this->delegate = $delegate;
    }
}