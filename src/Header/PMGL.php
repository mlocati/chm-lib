<?php

namespace CHMLib\Header;

use Exception;
use CHMLib\Reader\Reader;
use CHMLib\Exception\UnexpectedHeaderException;

/**
 * A PMGL (directory listing) header of a CHM file.
 */
class PMGL extends Header
{
    /**
     * The length of the free space and/or the QuickRef area at the end of the directory chunk.
     *
     * @var int
     */
    protected $freeSpace;

    /**
     * The chunk number of the previous listing chunk when reading directory in sequence (-1 if this is the first listing chunk).
     *
     * @var int
     */
    protected $previousChunk;

    /**
     * The chunk number of the next listing chunk when reading directory in sequence (-1 if this is the last listing chunk).
     *
     * @var int
     */
    protected $nextChunk;

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
        parent::__construct($reader);
        if ($this->headerSignature !== 'PMGL') {
            throw UnexpectedHeaderException::create('PMGL', $this->headerSignature);
        }
        $this->freeSpace = $reader->readUInt32();
        /* Unknown (0) */ $reader->readUInt32();
        $this->previousChunk = $reader->readInt32();
        $this->nextChunk = $reader->readInt32();
    }

    /**
     * Get the length of the free space and/or the QuickRef area at the end of the directory chunk.
     *
     * @return int
     */
    public function getFreeSpace()
    {
        return $this->freeSpace;
    }

    /**
     * Get the chunk number of the previous listing chunk when reading directory in sequence (-1 if this is the first listing chunk).
     *
     * @return int
     */
    public function getPreviousChunk()
    {
        return $this->previousChunk;
    }

    /**
     * Get the chunk number of the next listing chunk when reading directory in sequence (-1 if this is the last listing chunk).
     *
     * @return int
     */
    public function getNextChunk()
    {
        return $this->nextChunk;
    }
}
