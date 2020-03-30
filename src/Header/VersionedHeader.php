<?php

namespace CHMLib\Header;

use CHMLib\Reader\Reader;

/**
 * Represent a generic header that specify also version and length.
 */
abstract class VersionedHeader extends Header
{
    /**
     * The header version.
     *
     * @var int
     */
    protected $headerVersion;

    /**
     * The header size.
     *
     * @var int
     */
    protected $headerLength;

    /**
     * Initializes the instance.
     *
     * @param \CHMLib\Reader\Reader $reader The reader that provides the data.
     *
     * @throws \Exception Throws an Exception in case of errors.
     */
    public function __construct(Reader $reader)
    {
        parent::__construct($reader);
        $this->headerVersion = $reader->readUInt32();
        $this->headerLength = $reader->readUInt32();
    }

    /**
     * Get the header version.
     *
     * @return int
     */
    public function getHeaderVersion()
    {
        return $this->headerVersion;
    }

    /**
     * Get the header size.
     *
     * @return int
     */
    public function getHeaderLength()
    {
        return $this->headerLength;
    }
}
