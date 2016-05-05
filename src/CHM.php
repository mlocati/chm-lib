<?php

namespace CHMLib;

use Exception;
use CHMLib\Reader\Reader;
use CHMLib\Reader\StringReader;
use CHMLib\Exception\UnexpectedHeaderException;
use CHMLib\Reader\FileReader;

/**
 * Handle the contents of a CHM file.
 */
class CHM
{
    /**
     * The reader that provides the data.
     *
     * @var Reader
     */
    protected $reader;

    /**
     * The CHM initial header.
     *
     * @var Header\ITSF
     */
    protected $itsf;

    /**
     * The directory listing header.
     *
     * @var Header\ITSP
     */
    protected $itsp;

    /**
     * The entries found in this CHM.
     *
     * @var Entry[]
     */
    protected $entries;

    /**
     * The data sections.
     *
     * @var Section\Section[]
     */
    protected $sections;

    /**
     * The TOC.
     *
     * @var TOCIndex\Tree|null|false
     */
    protected $toc;

    /**
     * The index.
     *
     * @var TOCIndex\Tree|null|false
     */
    protected $index;

    /**
     * Initializes the instance.
     *
     * @param Reader $reader The reader that provides the data.
     *
     * @throws Exception Throws an Exception in case of errors.
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
        $reader->setPosition(0);
        $this->itsf = new Header\ITSF($reader);
        if ($this->itsf->getSectionOffset() >= 0 && $this->itsf->getSectionLength() >= 16 /* === 24*/) {
            $reader->setPosition($this->itsf->getSectionOffset());
            /* Unknown (510) */ $reader->readUInt32();
            /* Unknown (0) */ $reader->readUInt32();
            $totalLength = $reader->readUInt64();
            if ($totalLength !== $reader->getLength()) {
                throw new Exception("Invalid CHM size: expected length $totalLength, current length {$reader->getLength()}");
            }
        }
        $reader->setPosition($this->itsf->getDirectoryOffset());
        $this->itsp = new Header\ITSP($reader);

        $expectedDirectoryLength = $this->itsf->getDirectoryLength();
        $calculatedDirectoryLength = $this->itsp->getHeaderLength() + $this->itsp->getNumberOfDirectoryChunks() * $this->itsp->getDirectoryChunkSize();
        if ($expectedDirectoryLength !== $calculatedDirectoryLength) {
            throw new Exception("Unexpected directory list size (expected: $expectedDirectoryLength, calculated: $calculatedDirectoryLength)");
        }

        $this->sections = array();
        $this->sections[0] = new Section\UncompressedSection($this);

        $this->entries = $this->retrieveEntryList();

        $this->retrieveSectionList();

        $this->toc = null;
        $this->index = null;
    }

    /**
     * Destruct the instance.
     */
    public function __destruct()
    {
        unset($this->reader);
    }

    /**
     * Create a new CHM instance reading a file.
     *
     * @param string $filename
     *
     * @return static
     */
    public static function fromFile($filename)
    {
        $reader = new FileReader($filename);

        return new static($reader);
    }

    /**
     * Get the reader that provides the data.
     *
     * @return Reader
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * Get the CHM initial header.
     *
     * @return Header\ITSF
     */
    public function getITSF()
    {
        return $this->itsf;
    }

    /**
     * Get the directory listing header.
     *
     * @return Header\ITSP
     */
    public function getITSP()
    {
        return $this->itsp;
    }

    /**
     * Get an entry given its full path.
     *
     * @param string $path The full path (case sensitive) of the entry to look for.
     *
     * @return Entry|null
     */
    public function getEntryByPath($path)
    {
        return isset($this->entries[$path]) ? $this->entries[$path] : null;
    }

    /**
     * Get the entries contained in this CHM.
     *
     * @param int|null $type One or more Entry::TYPE_... values (defaults to Entry::TYPE_FILE | Entry::TYPE_DIRECTORY if null).
     */
    public function getEntries($type = null)
    {
        if ($type === null) {
            $type = Entry::TYPE_FILE | Entry::TYPE_DIRECTORY;
        }
        $result = array();
        foreach ($this->entries as $entry) {
            if (($entry->getType() & $type) !== 0) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    /**
     * Return a section given its index.
     *
     * @param int $i
     *
     * @return Section\Section|null
     */
    public function getSectionByIndex($i)
    {
        return isset($this->sections[$i]) ? $this->sections[$i] : null;
    }

    /**
     * Retrieve the list of the entries contained in this CHM.
     *
     * @throws Exception Throws an Exception in case of errors.
     *
     * @return Entry[]
     */
    protected function retrieveEntryList()
    {
        $result = array();
        $chunkOffset = $this->itsf->getDirectoryOffset() + $this->itsp->getHeaderLength();
        $chunkSize = $this->itsp->getDirectoryChunkSize();
        for ($i = $this->itsp->getFirstPMGLChunkNumber(), $l = $this->itsp->getLastPMGLChunkNumber(); $i <= $l; ++$i) {
            $offset = $chunkOffset + $i * $chunkSize;
            $this->reader->setPosition($offset);
            try {
                $pmgl = new Header\PMGL($this->reader);
            } catch (UnexpectedHeaderException $x) {
                if ($x->getFoundHeader() !== 'PMGI') {
                    throw $x;
                }
                $this->reader->setPosition($offset);
                $pmgi = new Header\PMGI($this->reader);
                $pmgl = null;
            }
            if ($pmgl !== null) {
                $end = $offset + $chunkSize - $pmgl->getFreeSpace();
                $cur = $this->reader->getPosition();
                while ($cur < $end) {
                    $this->reader->setPosition($cur);
                    $entry = new Entry($this);
                    $result[$entry->getPath()] = $entry;
                    $cur = $this->reader->getPosition();
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve the list of the data sections contained in this CHM.
     *
     * @throws Exception Throws an Exception in case of errors.
     */
    protected function retrieveSectionList()
    {
        $nameList = $this->getEntryByPath('::DataSpace/NameList');

        if ($nameList === null) {
            throw new Exception("Missing required entry: '::DataSpace/NameList'");
        }
        if ($nameList->getContentSectionIndex() !== 0) {
            throw new Exception("The content of the entry '{$nameList->getPath()}' should be in section 0, but it's in section {$nameList->getContentSection()}");
        }

        $nameListReader = new StringReader($nameList->getContents());
        /* Length */ $nameListReader->readUInt16();
        $numSections = $nameListReader->readUInt16();
        if ($numSections === 0) {
            throw new Exception('No content section defined.');
        }
        for ($i = 0; $i < $numSections; ++$i) {
            $nameLength = $nameListReader->readUInt16();
            $utf16name = $nameListReader->readString($nameLength * 2);
            $nameListReader->readUInt16();
            $name = iconv('UTF-16LE', 'UTF-8', $utf16name);
            switch ($name) {
                case 'Uncompressed':
                    break;
                case 'MSCompressed':
                    if ($i === 0) {
                        throw new Exception('First data section should be Uncompressed');
                    } else {
                        $this->sections[$i] = new Section\MSCompressedSection($this);
                    }
                    break;
                default:
                    throw new Exception("Unknown data section: $name");
            }
        }
    }

    /**
     * Get the TOC of this CHM file (if available).
     *
     * @return SpecialEntry\TOC|null
     */
    public function getTOC()
    {
        if ($this->toc === null) {
            $r = false;
            foreach ($this->entries as $entry) {
                if ($entry->isFile() && strcasecmp(substr($entry->getPath(), -4), '.hhc') === 0) {
                    $r = TOCIndex\Tree::fromString($this, $entry->getContents());
                    break;
                }
            }
            $this->toc = $r;
        }

        return ($this->toc === false) ? null : $this->toc;
    }

    /**
     * Get the index of this CHM file (if available).
     *
     * @return TOCIndex\Tree|null
     */
    public function getIndex()
    {
        if ($this->index === null) {
            $r = false;
            foreach ($this->entries as $entry) {
                if ($entry->isFile() && strcasecmp(substr($entry->getPath(), -4), '.hhk') === 0) {
                    $r = TOCIndex\Tree::fromString($this, $entry->getContents());
                    break;
                }
            }
            $this->index = $r;
        }

        return ($this->index === false) ? null : $this->index;
    }
}
