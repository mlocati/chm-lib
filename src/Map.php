<?php

namespace CHMLib;

/**
 * Map one or more CHM files to the parsed CHM instances.
 */
class Map
{
    /**
     * The map dictionary.
     *
     * @var array
     */
    protected $map;

    /**
     * Initializes the instance.
     */
    public function __construct()
    {
        $this->map = array();
    }

    /**
     * Add a parsed CHM file to this map.
     *
     * @param string $name The name to give the new CHM instance.
     * @param CHM $chm The parsed CHM file.
     */
    public function add($name, CHM $chm)
    {
        $this->map[$name] = $chm;
    }

    /**
     * Get a parsed CHM file given its name.
     *
     * @param string $name The mapped name.
     *
     * @return CHM|null
     */
    public function get($name)
    {
        return isset($this->map[$name]) ? $this->map[$name] : null;
    }
}
