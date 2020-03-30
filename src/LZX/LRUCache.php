<?php

namespace CHMLib\LZX;

use Exception;

/**
 * Handle the cache of recently used data.
 */
class LRUCache
{
    /**
     * The cached items.
     *
     * @var \CHMLib\LZX\LRUCacheItem[]
     */
    protected $cache;

    /**
     * The maximim capacity of the cache.
     *
     * @var int
     */
    protected $capacity;

    /**
     * Initializes the instance.
     *
     * @param int $capacity The maximim capacity of the cache.
     *
     * @throws \Exception Throws an Exception in case of errors.
     */
    public function __construct($capacity)
    {
        $this->capacity = (int) $capacity;
        if ($this->capacity < 1) {
            throw new Exception('The cache capacity must be greather than zero');
        }
        $this->cache = array();
    }

    /**
     * Get a cached items given its key (return null if no cached items with the specified key).
     *
     * @param mixed $key
     *
     * @return mixed|null
     */
    public function get($key)
    {
        $result = null;
        if (isset($this->cache[$key])) {
            $item = $this->cache[$key];
            array_map(
                function (LRUCacheItem $i) {
                    --$i->hits;
                },
                $this->cache
            );
            $item->hits += 2;
            $result = $item->value;
        }

        return $result;
    }

    /**
     * Ensure that the cache has at least one empty slot (it the cache is full, we'll remove the less used cache item).
     *
     * @return null|mixed Returns null if no item has been removed, otherwise returns the value of the removed item.
     */
    public function prune()
    {
        $result = null;
        if (count($this->cache) >= $this->capacity) {
            $kick = null;
            $kickKey = null;
            foreach ($this->cache as $key => $item) {
                if ($kick === null || $kick->hits > $item->hits) {
                    $kick = $item;
                    $kickKey = $key;
                }
            }
            unset($this->cache[$kickKey]);
            $result = $kick->value;
        }

        return $result;
    }

    /**
     * Add a new item to the cache.
     *
     * @param mixed $key The key to assign to the cached item.
     * @param mixed $value The value to be cached.
     */
    public function put($key, $value)
    {
        if (!isset($this->cache[$key])) {
            $this->prune();
        }
        $this->cache[$key] = new LRUCacheItem($value);
    }

    /**
     * Clear the cache.
     */
    public function clear()
    {
        $this->cache = array();
    }

    /**
     * Get the number of the currently cached items.
     *
     * @return int
     */
    public function size()
    {
        return count($this->cache);
    }
}
