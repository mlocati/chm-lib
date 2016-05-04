<?php

namespace CHMLib\Header;

use Exception;
use CHMLib\Reader\Reader;
use CHMLib\Exception\UnexpectedHeaderException;

/**
 * The LXZ header of a CHM file.
 */
class LZXC extends Header
{
    /**
     * The header version.
     *
     * @var int
     */
    protected $version;

    /**
     * The LZX reset interval.
     *
     * @var int
     */
    protected $resetInterval;

    /**
     * The window size in 32KB blocks.
     *
     * @var int
     */
    protected $windowSize;

    /**
     * The cache size.
     *
     * @var int
     */
    protected $cacheSize;

    /**
     * Initializes the instance.
     *
     * @param Reader $reader The reader that provides the data.
     *
     * @throws UnexpectedHeaderException Throws an UnexpectedHeaderException if the header signature is not valid.
     * @throws Exception Throws an Exception in case of errors.
     */
    public function __construct(Reader $reader)
    {
        $size = $reader->readUInt32();
        if ($size < 6) {
            throw new Exception('The LZXC entry is too small');
        }
        parent::__construct($reader);
        if ($this->headerSignature !== 'LZXC') {
            throw UnexpectedHeaderException::create('LZXC', $this->headerSignature);
        }
        $this->version = $reader->readUInt32();
        if ($this->version !== 2) {
            throw new Exception("Unsupported LZXC header version: {$this->version}");
        }
        $this->resetInterval = $reader->readUInt32();
        $this->windowSize = $reader->readUInt32();
        $this->cacheSize = $reader->readUInt32();
    }

    /**
     * Get the header version.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get the LZX reset interval.
     *
     * @return int
     */
    public function getResetInterval()
    {
        return $this->resetInterval;
    }

    /**
     * Get the window size in 32KB blocks.
     *
     * @return int
     */
    public function getWindowSize()
    {
        return $this->windowSize;
    }

    /**
     * Get the cache size.
     *
     * @return int
     */
    public function getCacheSize()
    {
        return $this->cacheSize;
    }
}
