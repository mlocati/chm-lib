<?php

namespace CHMLib\Header;

use Exception;
use CHMLib\Windows\Language;
use CHMLib\Reader\Reader;
use CHMLib\Exception\UnexpectedHeaderException;

/**
 * The initial header of a CHM file.
 */
class ITSF extends VersionedHeader
{
    /**
     * The "timestamp" (lower 32 bits of a 64-bit value representing the number of centiseconds since 1601-01-01 00:00:00 UTC, plus 42).
     *
     * @var int
     */
    protected $timestamp;

    /**
     * The language of the OS at the time of compilation.
     *
     * @var Language
     */
    protected $originalOSLanguage;

    /**
     * The directory GUID (it should be '{7C01FD10-7BAA-11D0-9E0C-00A0-C922-E6EC}').
     *
     * @var string
     */
    protected $directoryGUID;

    /**
     * The stream GUID (it should be '{7C01FD11-7BAA-11D0-9E0C-00A0-C922-E6EC}').
     *
     * @var string
     */
    protected $streamGUID;

    /**
     * The offset of the section (from the beginning of the file).
     *
     * @var int
     */
    protected $sectionOffset;

    /**
     * The length of the section.
     *
     * @var int
     */
    protected $sectionLength;

    /**
     * The offset of the directory (from the beginning of the file).
     *
     * @var int
     */
    protected $directoryOffset;

    /**
     * The length of the directory.
     *
     * @var int
     */
    protected $directoryLength;

    /**
     * The offset of the content (from the beginning of the file).
     *
     * @var int
     */
    protected $contentOffset;

    /**
     * Initialize the instance.
     *
     * @param Reader $reader The reader that provides the data.
     *
     * @throws UnexpectedHeaderException Throws an UnexpectedHeaderException if the header signature is not valid.
     * @throws Exception Throws an Exception in case of errors.
     */
    public function __construct(Reader $reader)
    {
        parent::__construct($reader);
        if ($this->headerSignature !== 'ITSF') {
            throw UnexpectedHeaderException::create('ITSF', $this->headerSignature);
        }
        if ($this->headerVersion < 2 || $this->headerVersion > 3) {
            throw new Exception('Unsupported ITSF version number: '.$this->headerVersion);
        }
        /* Unknown (1) */ $reader->readUInt32();
        $this->timestamp = $reader->readUInt32();
        $this->originalOSLanguage = new Language($reader->readUInt32());
        $this->directoryGUID = $reader->readGUID();
        $this->streamGUID = $reader->readGUID();
        $this->sectionOffset = $reader->readUInt64();
        $this->sectionLength = $reader->readUInt64();
        $this->directoryOffset = $reader->readUInt64();
        $this->directoryLength = $reader->readUInt64();
        if ($this->headerLength >= 96) {
            $this->contentOffset = $reader->readUInt64();
        } else {
            $this->contentOffset = $this->directoryOffset + $this->directoryLength;
        }
    }

    /**
     * Get the "timestamp" (lower 32 bits of a 64-bit value representing the number of centiseconds since 1601-01-01 00:00:00 UTC, plus 42).
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get the language of the OS at the time of compilation.
     *
     * @return Language
     */
    public function getOriginalOSLanguage()
    {
        return $this->originalOSLanguage;
    }

    /**
     * Get the directory GUID (it should be '{7C01FD10-7BAA-11D0-9E0C-00A0-C922-E6EC}').
     *
     * @return string
     */
    public function getDirectoryGUID()
    {
        return $this->directoryGUID;
    }

    /**
     * Get the stream GUID (it should be '{7C01FD11-7BAA-11D0-9E0C-00A0-C922-E6EC}').
     *
     * @return string
     */
    public function getStreamGUID()
    {
        return $this->streamGUID;
    }

    /**
     * Get the offset of the section (from the beginning of the file).
     *
     * @return int
     */
    public function getSectionOffset()
    {
        return $this->sectionOffset;
    }

    /**
     * Get the length of section.
     *
     * @return int
     */
    public function getSectionLength()
    {
        return $this->sectionLength;
    }

    /**
     * Get the offset of the directory (from the beginning of the file).
     *
     * @return int
     */
    public function getDirectoryOffset()
    {
        return $this->directoryOffset;
    }

    /**
     * Get the length of the directory.
     *
     * @return int
     */
    public function getDirectoryLength()
    {
        return $this->directoryLength;
    }

    /**
     * Get the offset of the content (from the beginning of the file).
     *
     * @return int
     */
    public function getContentOffset()
    {
        return $this->contentOffset;
    }
}
