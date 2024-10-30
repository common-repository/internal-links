<?php

namespace ILJ\Core\Links;

use ILJ\Cache\Transient_Cache;
class Cache_Layer extends Layer
{
    /**
     * The post/term id.
     *
     * @var int $id
     */
    protected $id;
    /**
     * The resource type.
     *
     * @var string $type
     */
    protected $type;
    /**
     * Constructor for {@link Cache_Layer}
     *
     * @param int                              $id       The post/term id.
     * @param string                           $type     The resource type.
     * @param Text_To_Link_Converter_Interface $delegate The delegate.
     */
    public function __construct($id, $type, $delegate)
    {
        $this->id = $id;
        $this->type = $type;
        parent::__construct($delegate);
    }
    /**
     * Return the linked content.
     *
     * @param mixed $content The content value passed to cache link value into
     * @return mixed 		 Mixed return value to cater different content compatibilities
     */
    public function link_content($content)
    {
        $cached_content = Transient_Cache::get_cache_for_content($this->id, $this->type, $content);
        if ($cached_content) {
            return $cached_content;
        }
        $linked_content = $this->delegate->link_content($content);
        Transient_Cache::set_cache_for_content($this->id, $this->type, $content, $linked_content);
        return $linked_content;
    }
}