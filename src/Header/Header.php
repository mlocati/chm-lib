<?php

namespace CHMLib\Header;

use CHMLib\Reader\Reader;

/**
 * A generic header.
 */
abstract class Header
{
    /**
     * The header signature.
     *
     * @var string
     */
    protected $headerSignature;

    /**
     * Initialize the instance.
     *
     * @param Reader $reader The reader that provides the data.
     *
     * @throws \Exception Throws an Exception in case of errors.
     */
    public function __construct(Reader $reader)
    {
        $this->headerSignature = $reader->readString(4);
    }

    /**
     * Get the header signature.
     *
     * @return string
     */
    public function getHeaderSignature()
    {
        return $this->headerSignature;
    }
}
