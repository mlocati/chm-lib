<?php

namespace CHMLib\LZX;

/**
 * Represent an cached item handled by LRUCache.
 */
class LRUCacheItem
{
    /**
     * The item value.
     *
     * @var mixed
     */
    public $value;

    /**
     * The item hit count.
     *
     * @var int
     */
    public $hits;

    /**
     * Initializes the instance.
     *
     * @param mixed $value The item value.
     */
    public function __construct($value)
    {
        $this->value = $value;
        $this->hits = 1;
    }
}
