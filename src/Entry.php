<?php

namespace CHMLib;

use Exception;

/**
 * Represent an entry (file/directory) contained in a CHM file.
 */
class Entry
{
    /**
     * Is the content cache enabled?
     * 
     * @var bool
    */
    protected static $contentCacheEnabled = false;

    /**
     * Is the content cache enabled?
     *
     * @return bool
     */
    public static function getContentCacheEnabled()
    {
        return static::$contentCacheEnabled;
    }

    /**
     * Enable/disable the content cache.
     *
     * @param bool $enabled
     */
    public static function setContentCacheEnabled($enabled)
    {
        static::$contentCacheEnabled = (bool) $enabled;
    }

    /**
     * Entry type: directory.
     *
     * @var int
     */
    const TYPE_DIRECTORY = 0x1;

    /**
     * Entry type: normal file.
     *
     * @var int
     */
    const TYPE_FILE = 0x2;

    /**
     * Entry type: special file.
     *
     * @var int
     */
    const TYPE_SPECIAL_FILE = 0x4;

    /**
     * Entry type: meta data.
     *
     * @var int
     */
    const TYPE_METADATA = 0x8;

    /**
     * The parent CHM file.
     *
     * @var CHM
     */
    protected $chm;

    /**
     * The path of this entry.
     *
     * @var string
     */
    protected $path;

    /**
     * The index of the content section that contains the data of this entry.
     *
     * @var int
     */
    protected $contentSectionIndex;

    /**
     * The offset of the entry data from the beginning of the content section this entry is in, after the section has been decompressed (if appropriate).
     *
     * @var int
     */
    protected $offset;

    /**
     * The length of the entry data after decompression (if appropriate).
     *
     * @var int
     */
    protected $length;

    /**
     * The type of this entry (one of the static::TYPE_... constants).
     *
     * @var int
     */
    protected $type;

    /**
     * The previously read contents of this entry.
     *
     * @var string|null
     */
    protected $cachedContents;

    /**
     * Initializes the instance.
     *
     * @param CHM $chm The parent CHM file.
     */
    public function __construct(CHM $chm)
    {
        $reader = $chm->getReader();
        $this->chm = $chm;
        $stringLength = $reader->readCompressedUInt32();
        $this->path = $reader->readString($stringLength);
        $this->contentSectionIndex = $reader->readCompressedUInt32();
        $this->offset = $reader->readCompressedUInt32();
        $this->length = $reader->readCompressedUInt32();
        $pathLength = strlen($this->path);
        if (substr($this->path, -1) === '/') {
            $this->type = static::TYPE_DIRECTORY;
        } elseif ($this->path[0] === '/') {
            if ($pathLength > 1 && ($this->path[1] === '#' || $this->path[1] === '$')) {
                $this->type = static::TYPE_SPECIAL_FILE;
            } else {
                $this->type = static::TYPE_FILE;
            }
        } else {
            $this->type = static::TYPE_METADATA;
        }
        $this->cachedContents = null;
    }

    /**
     * Get the path of this entry.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the index of the content section that contains the data of this entry.
     *
     * @return int
     */
    public function getContentSectionIndex()
    {
        return $this->contentSectionIndex;
    }

    /**
     * Get the offset from the beginning of the content section this entry is in, after the section has been decompressed (if appropriate).
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Get the length of the entry data after decompression (if appropriate).
     *
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Is this a directory entry?
     *
     * @return bool
     */
    public function isDirectory()
    {
        return (bool) ($this->type === static::TYPE_DIRECTORY);
    }

    /**
     * Is this a normal file entry?
     *
     * @return bool
     */
    public function isFile()
    {
        return (bool) ($this->type === static::TYPE_FILE);
    }

    /**
     * Is this a special file entry?
     *
     * @return bool
     */
    public function isSpecialFile()
    {
        return (bool) ($this->type === static::TYPE_SPECIAL_FILE);
    }

    /**
     * Is this a meta-data entry?
     *
     * @return bool
     */
    public function isMetaData()
    {
        return (bool) ($this->type === static::TYPE_METADATA);
    }

    /**
     * Get the type of this entry (one of the static::TYPE_... constants).
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the contents of this entry.
     *
     * @throws Exception Throws an Exception in case of errors.
     *
     * @return string
     */
    public function getContents()
    {
        $cacheEnabled = static::getContentCacheEnabled();
        if ($cacheEnabled && $this->cachedContents !== null) {
            $result = $this->cachedContents;
        } else {
            $section = $this->chm->getSectionByIndex($this->contentSectionIndex);
            if ($section === null) {
                throw new Exception("The CHM file does not contain a data section with index {$this->contentSectionIndex}");
            }
            $result = $section->getContents($this->offset, $this->length);
            $this->cachedContents = $cacheEnabled ? $result : null;
        }

        return $result;
    }
}
