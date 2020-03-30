<?php

namespace CHMLib\Header;

use Exception;
use CHMLib\Reader\Reader;
use CHMLib\Windows\Language;
use CHMLib\Exception\UnexpectedHeaderException;

/**
 * The directory header of a CHM file.
 */
class ITSP extends VersionedHeader
{
    /**
     * The directory chunk size.
     *
     * @var int
     */
    protected $directoryChunkSize;

    /**
     * The "density" of the QuickRef section (usually 2).
     *
     * @var int
     *
     * @example quickRef = 1 + 2 * quickRefDensity;
     */
    protected $quickRefDensity;

    /**
     * The depth of the index tree (1: no index; 2 one level of PMGI chunks).
     *
     * @var int
     */
    protected $indexDepth;

    /**
     * The chunk number of the root index chunk  (-1: none, though at least sometimes this is 0 even if there is no index chunk - probably a bug).
     *
     * @var int
     */
    protected $rootIndexChunkNumber;

    /**
     * The chunk number of the first PMGL (listing) chunk.
     *
     * @var int
     */
    protected $firstPMGLChunkNumber;

    /**
     * The chunk number of the last PMGL (listing) chunk.
     *
     * @var int
     */
    protected $lastPMGLChunkNumber;

    /**
     * The total number of directory chunks.
     *
     * @var int
     */
    protected $numberOfDirectoryChunks;

    /**
     * The language of the program that generated the CHM file.
     *
     * @var \CHMLib\Windows\Language
     */
    protected $generatorLanguage;

    /**
     * The system GUID (it should be '{5D02926A-212E-11D0-9DF9-00A0C922E6EC}').
     *
     * @var string
     */
    protected $systemGUID;

    /**
     * Initialize the instance.
     *
     * @param Reader $reader The reader that provides the data.
     *
     * @throws \CHMLib\Exception\UnexpectedHeaderException Throws an UnexpectedHeaderException if the header signature is not valid.
     * @throws \Exception Throws an Exception in case of errors.
     */
    public function __construct(Reader $reader)
    {
        parent::__construct($reader);
        if ($this->headerSignature !== 'ITSP') {
            throw UnexpectedHeaderException::create('ITSP', $this->headerSignature);
        }
        if ($this->headerVersion !== 1) {
            throw new Exception('Unsupported ITSP version number: '.$this->headerVersion);
        }
        /* Unknown (10) */ $reader->readUInt32();
        $this->directoryChunkSize = $reader->readUInt32();
        $this->quickRefDensity = $reader->readUInt32();
        $this->indexDepth = $reader->readUInt32();
        $this->rootIndexChunkNumber = $reader->readInt32();
        $this->firstPMGLChunkNumber = $reader->readUInt32();
        $this->lastPMGLChunkNumber = $reader->readUInt32();
        /* Unknown (-1) */ $reader->readInt32();
        $this->numberOfDirectoryChunks = $reader->readUInt32();
        $this->generatorLanguage = new Language($reader->readUInt32());
        $this->systemGUID = $reader->readGUID();
        /* Again the size of this header (84) */ $reader->readUInt32();
        /* Unknown (-1) */ $reader->readInt32();
        /* Unknown (-1) */ $reader->readInt32();
        /* Unknown (-1) */ $reader->readInt32();
    }

    /**
     * Get the directory chunk size.
     *
     * @return int
     */
    public function getDirectoryChunkSize()
    {
        return $this->directoryChunkSize;
    }

    /**
     * Get the "density" of the QuickRef section (usually 2).
     *
     * @return int
     *
     * @example quickRef = 1 + 2 * quickRefDensity
     */
    public function getQuickRefDensity()
    {
        return $this->quickRefDensity;
    }

    /**
     * Get the depth of the index tree (1: no index; 2 one level of PMGI chunks).
     *
     * @return int
     */
    public function getIndexDepth()
    {
        return $this->indexDepth;
    }

    /**
     * Get the chunk number of the root index chunk (-1: none, though at least sometimes this is 0 even if there is no index chunk - probably a bug).
     *
     * @return int
     */
    public function getRootIndexChunkNumber()
    {
        return $this->rootIndexChunkNumber;
    }

    /**
     * Get the chunk number of the first PMGL (listing) chunk.
     *
     * @return int
     */
    public function getFirstPMGLChunkNumber()
    {
        return $this->firstPMGLChunkNumber;
    }

    /**
     * Get the chunk number of the last PMGL (listing) chunk.
     *
     * @return int
     */
    public function getLastPMGLChunkNumber()
    {
        return $this->lastPMGLChunkNumber;
    }

    /**
     * Get the total number of directory chunks.
     *
     * @return int
     */
    public function getNumberOfDirectoryChunks()
    {
        return $this->numberOfDirectoryChunks;
    }

    /**
     * Get the language of the program that generated the CHM file.
     *
     * @return \CHMLib\Windows\Language
     */
    public function getGeneratorLanguage()
    {
        return $this->generatorLanguage;
    }

    /**
     * Get the system GUID (it should be '{5D02926A-212E-11D0-9DF9-00A0C922E6EC}').
     *
     * @return string
     */
    public function getSystemGUID()
    {
        return $this->systemGUID;
    }
}
