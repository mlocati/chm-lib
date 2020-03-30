<?php

namespace CHMLib\Section;

use Exception;
use CHMLib\CHM;
use CHMLib\Reader\StringReader;
use CHMLib\Reader\BitReader;
use CHMLib\Header\LZXC;
use CHMLib\LZX\Inflater;
use CHMLib\LZX\LRUCache;

/**
 * Represent a LXZ-compressed section of data in a CHM file.
 */
class MSCompressedSection extends Section
{
    /**
     * The LZX reset interval.
     *
     * @var int
     */
    protected $resetInterval;

    /**
     * The window size.
     *
     * @var int
     */
    protected $windowSize;

    /**
     * The size of the uncompressed data.
     *
     * @var int
     */
    protected $uncompressedLength;

    /**
     * The size of the compressed data.
     *
     * @var int
     */
    protected $compressedLength;

    /**
     * The block size.
     *
     * @var int
     */
    protected $blockSize;

    /**
     * The address table.
     *
     * @var int[]
     */
    protected $addressTable;

    /**
     * The currently cached blocks.
     *
     * @var \CHMLib\LZX\LRUCache
     */
    protected $cachedBlocks;

    /**
     * Initializes the instance.
     *
     * @param CHM $chm The parent CHM file.
     *
     * @throws Exception Throws an Exception in case of errors.
     */
    public function __construct(CHM $chm)
    {
        parent::__construct($chm);
        $controlDataEntry = $chm->getEntryByPath('::DataSpace/Storage/MSCompressed/ControlData');
        if ($controlDataEntry === null) {
            throw new Exception("Missing required entry: '::DataSpace/Storage/MSCompressed/ControlData'");
        }
        if ($controlDataEntry->getContentSectionIndex() !== 0) {
            throw new Exception("The content of the entry '{$controlDataEntry->getPath()}' should be in section 0, but it's in section {$controlDataEntry->getContentSectionIndex()}");
        }
        $controlDataReader = new StringReader($controlDataEntry->getContents());
        $lzxc = new LZXC($controlDataReader);
        $this->resetInterval = $lzxc->getResetInterval();
        $this->windowSize = $lzxc->getWindowSize() * 32768;
        $this->cachedBlocks = new LRUCache((1 + $lzxc->getCacheSize()) << 2);
        $resetTableEntry = $chm->getEntryByPath('::DataSpace/Storage/MSCompressed/Transform/{7FC28940-9D31-11D0-9B27-00A0C91E9C7C}/InstanceData/ResetTable');
        if ($resetTableEntry === null) {
            throw new Exception("Missing required entry: '::DataSpace/Storage/MSCompressed/Transform/{7FC28940-9D31-11D0-9B27-00A0C91E9C7C}/InstanceData/ResetTable'");
        }
        if ($resetTableEntry->getContentSectionIndex() !== 0) {
            throw new Exception("The content of the entry '{$resetTableEntry->getPath()}' should be in section 0, but it's in section {$resetTableEntry->getContentSectionIndex()}");
        }
        $resetTableReader = new StringReader($resetTableEntry->getContents());
        $resetTableVersion = $resetTableReader->readUInt32();
        if ($resetTableVersion !== 2) {
            throw new Exception("Unsupported LZX Reset Table version: $resetTableVersion");
        }
        $addressTableSize = $resetTableReader->readUInt32();
        /* Size of table entry (8) */ $resetTableReader->readUInt32();
        /* Header length (40) */ $resetTableReader->readUInt32();
        $this->uncompressedLength = $resetTableReader->readUInt64();
        $this->compressedLength = $resetTableReader->readUInt64();
        $this->blockSize = $resetTableReader->readUInt64(); // We do not support block sizes bigger than 32-bit integers
        $this->addressTable = array();
        for ($i = 0; $i < $addressTableSize; ++$i) {
            $this->addressTable[$i] = $resetTableReader->readUInt64();
        }
        $contentEntry = $chm->getEntryByPath('::DataSpace/Storage/MSCompressed/Content');
        if ($contentEntry === null) {
            throw new Exception("Missing required entry: '::DataSpace/Storage/MSCompressed/Content");
        }
        if ($this->compressedLength > $contentEntry->getLength()) {
            throw new Exception("Compressed section size should be {$this->compressedLength}, but it's {$contentEntry->getLength()}");
        }
        $this->sectionOffset = $chm->getITSF()->getContentOffset() + $contentEntry->getOffset();
    }

    /**
     * {@inheritdoc}
     *
     * @see Section::getContents()
     */
    public function getContents($offset, $length)
    {
        $result = '';
        if ($length > 0) {
            $startBlockNo = (int) ($offset / $this->blockSize);
            $startOffset = $offset % $this->blockSize;
            $endBlockNo = (int) (($offset + $length) / $this->blockSize);
            $endOffset = (int) (($offset + $length) % $this->blockSize);
            if ($endOffset === 0 && $endBlockNo > $startBlockNo) {
                $endOffset = $this->blockSize;
                --$endBlockNo;
            }
            $blockNo = $startBlockNo - $startBlockNo % $this->resetInterval;
            $inflater = new Inflater($this->windowSize);

            $buf = array();
            $pos = 0;
            $bytesLeft = 0;
            $reader = $this->chm->getReader();
            while ($bytesLeft > 0 || $blockNo <= $endBlockNo) {
                $data = '';
                while ($bytesLeft <= 0) {
                    // Read block
                    if ($blockNo > $endBlockNo) {
                        throw new Exception('Read after last data block');
                    }
                    $cacheNo = (int) ($blockNo / $this->resetInterval);
                    $cache = $this->cachedBlocks->get($cacheNo);
                    if ($cache === null) {
                        $this->cachedBlocks->prune();
                        $cache = array();
                        $resetBlockNo = $blockNo - $blockNo % $this->resetInterval;
                        for ($i = 0; $i < $this->resetInterval && $resetBlockNo + $i < count($this->addressTable); ++$i) {
                            $thisBlockNo = $resetBlockNo + $i;
                            $len = ($thisBlockNo + 1 < count($this->addressTable)) ?
                                ($this->addressTable[$thisBlockNo + 1] - $this->addressTable[$thisBlockNo])
                                :
                                ($this->compressedLength - $this->addressTable[$thisBlockNo]);
                            $reader->setPosition($this->sectionOffset + $this->addressTable[$thisBlockNo]);
                            $bitReader = new BitReader($reader->readString($len));
                            $cache[$i] = $inflater->inflate(
                                $i === 0,
                                $bitReader,
                                $this->blockSize
                            );
                        }
                        $this->cachedBlocks->put($cacheNo, $cache);
                    }
                    $data = $cache[$blockNo % $this->resetInterval];
                    // the start block has special pos value
                    $pos = ($blockNo === $startBlockNo) ? $startOffset : 0;
                    // the end block has special length
                    $bytesLeft = ($blockNo < $startBlockNo) ? 0 : (($blockNo < $endBlockNo) ? $this->blockSize : $endOffset);
                    $bytesLeft -= $pos;
                    ++$blockNo;
                }
                $togo = $bytesLeft;
                $result .= substr($data, $pos, $togo);
                $pos += $togo;
                $bytesLeft -= $togo;
            }
        }

        return $result;
    }
}
