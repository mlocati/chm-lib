<?php

namespace CHMLib\Header;

use Exception;
use CHMLib\Reader\Reader;
use CHMLib\Exception\UnexpectedHeaderException;

/**
 * A PMGI (directory listing) header of a CHM file.
 */
class PMGI extends Header
{
    /**
     * The length of the free space and/or the QuickRef area at the end of the directory chunk.
     *
     * @var int
     */
    protected $freeSpace;

    /**
     * Initializes the instance.
     *
     * @param Reader $reader The reader that provides the data.
     *
     * @throws \CHMLib\Exception\UnexpectedHeaderException Throws an UnexpectedHeaderException if the header signature is not valid.
     * @throws \Exception Throws an Exception in case of errors.
     */
    public function __construct(Reader $reader)
    {
        parent::__construct($reader);
        if ($this->headerSignature !== 'PMGI') {
            throw UnexpectedHeaderException::create('PMGI', $this->headerSignature);
        }
        $this->freeSpace = $reader->readUInt32();
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
}
