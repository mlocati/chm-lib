<?php

namespace CHMLib\LZX;

use Exception;
use CHMLib\Reader\BitReader;

/**
 * Decompressor of LXZ-compressed data.
 */
class Inflater
{
    /**
     * The smallest allowable match length.
     *
     * @var int
     */
    const MIN_MATCH = 2;

    /**
     * The number of uncompressed character types.
     *
     * @var int
     */
    const NUM_CHARS = 256;

    /**
     * Block type: verbatim.
     *
     * @var int
     */
    const BLOCKTYPE_VERBATIM = 1;

    /**
     * Block type: aligned offset.
     *
     * @var int
     */
    const BLOCKTYPE_ALIGNED = 2;

    /**
     * Block type: uncompressed.
     *
     * @var int
     */
    const BLOCKTYPE_UNCOMPRESSED = 3;

    /**
     * The number of elements in the aligned offset tree.
     *
     * @var int
     */
    const ALIGNED_NUM_ELEMENTS = 8;

    /**
     * Unknown.
     *
     * @var int
     */
    const NUM_PRIMARY_LENGTHS = 7;

    /**
     * The number of elements in the length tree.
     *
     * @var int
     */
    const NUM_SECONDARY_LENGTHS = 249;

    /**
     * The index matrix of the position slot bases.
     *
     * @var int[]
     */
    protected static $POSITION_BASE = array(
              0,       1,       2,      3,      4,      6,      8,     12,     16,     24,     32,       48,      64,      96,     128,     192,
            256,     384,     512,    768,   1024,   1536,   2048,   3072,   4096,   6144,   8192,    12288,   16384,   24576,   32768,   49152,
          65536,   98304,  131072, 196608, 262144, 393216, 524288, 655360, 786432, 917504, 1048576, 1179648, 1310720, 1441792, 1572864, 1703936,
        1835008, 1966080, 2097152,
    );

    /**
     * Number of needed bits for offset-from-base data.
     *
     * @var int
     */
    protected static $EXTRA_BITS = array(
         0,  0,  0,  0,  1,  1,  2,  2,  3,  3,  4,  4,  5,  5,  6,  6,
         7,  7,  8,  8,  9,  9, 10, 10, 11, 11, 12, 12, 13, 13, 14, 14,
        15, 15, 16, 16, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17,
        17, 17, 17,
    );

    /**
     * The size of the actual decoding window.
     *
     * @var int
     */
    protected $windowSize;

    /**
     * The actual decoding window.
     *
     * @var int[]
     */
    protected $window;

    /**
     * The current offset within the window.
     *
     * @var int
     */
    protected $windowPosition;

    /**
     * The most recent non-repeated offset (LRU offset system).
     *
     * @var int
     */
    protected $r0;

    /**
     * The second most recent non-repeated offset (LRU offset system).
     *
     * @var int
     */
    protected $r1;

    /**
     * The third most recent non-repeated offset (LRU offset system).
     *
     * @var int
     */
    protected $r2;

    /**
     * The number of main tree elements.
     *
     * @var int
     */
    protected $mainElements;

    /**
     * Decoding has already been started?
     *
     * @var bool
     */
    protected $headerRead;

    /**
     * The type of the current block.
     *
     * @var int
     */
    protected $blockType;

    /**
     * The uncompressed length of the current block.
     *
     * @var int
     */
    protected $blockLength;

    /**
     * The number of ncompressed bytes still left to decode in the current block.
     *
     * @var int
     */
    protected $remainingInBlock;

    /**
     * The number of CFDATA blocks processed.
     *
     * @var int
     */
    protected $framesRead;

    /**
     * The magic header value used for transform (0 if not encoded).
     *
     * @var int
     */
    protected $intelFilesize;

    /**
     * The current offset in transform space.
     *
     * @var int
     */
    protected $intelCurrentPosition;

    /**
     * Have we seen any translatable data yet?
     *
     * @var bool
     */
    protected $intelStarted;

    /**
     * The main Huffman tree.
     *
     * @var Tree
     */
    protected $mainTree;

    /**
     * The length Huffman tree.
     *
     * @var Tree
     */
    protected $lengthTree;

    /**
     * The aligned offset Huffman tree.
     *
     * @var Tree
     */
    protected $alignedTree;

    /**
     * Initializes the instance.
     *
     * @param int $windowSize The window size (determines the number of window subdivisions, or "position slots"),
     *
     * @throws Exception Throws an Exception in case of errors.
     */
    public function __construct($windowSize)
    {
        $this->windowSize = (int) $windowSize;
        if ($this->windowSize < 0x8000 || $this->windowSize > 0x200000) {
            throw new Exception("Unsupported window size: $windowSize");
        }
        $this->mainTree = new Tree(12, static::NUM_CHARS + 50 * 8);
        $this->lengthTree = new Tree(12, static::NUM_SECONDARY_LENGTHS + 1);
        $this->alignedTree = new Tree(7, static::ALIGNED_NUM_ELEMENTS);
        $this->window = array_fill(0, $this->windowSize, 0);
        $this->intelFilesize = 0;
        $numPositionSlots = 0;
        $windowSize = $this->windowSize;
        while ($windowSize > 1) {
            $windowSize >>= 1;
            $numPositionSlots += 2;
        }
        switch ($numPositionSlots) {
            case 40:
                $numPositionSlots = 42;
                break;
            case 42:
                $numPositionSlots = 50;
                break;
        }
        $this->mainElements = static::NUM_CHARS + ($numPositionSlots << 3);
    }

    /**
     * Uncompress bytes.
     *
     * @param bool $reset Reset the LXZ state?
     * @param BitReader $reader The reader that provides the data.
     * @param int $numberOfBytes The number of decompressed bytes to retrieve.
     *
     * @throws Exception Throws an Exception in case of errors.
     *
     * @return string
     */
    public function inflate($reset, BitReader $reader, $numberOfBytes)
    {
        if ($reset) {
            $this->r2 = $this->r1 = $this->r0 = 1;
            $this->headerRead = false;
            $this->framesRead = 0;
            $this->remainingInBlock = 0;
            $this->blockType = null;
            $this->intelCurrentPosition = 0;
            $this->intelStarted = false;
            $this->windowPosition = 0;
            $this->mainTree->clear();
            $this->lengthTree->clear();
        }

        if ($this->headerRead === false) {
            if ($reader->readLE(1) > 0) {
                $this->intelFilesize = ($reader->readLE(16) << 16) | $reader->readLE(16);
            }
            $this->headerRead = true;
        }

        $togo = $numberOfBytes;
        while ($togo > 0) {
            if ($this->remainingInBlock === 0) {
                if ($this->blockType === static::BLOCKTYPE_UNCOMPRESSED) {
                    if (($this->blockLength & 1) !== 0) {
                        $reader->skip(1);
                    }
                }
                $this->blockType = $reader->readLE(3);
                $this->remainingInBlock = $this->blockLength = $reader->readLE(16) << 8 | $reader->readLE(8);
                switch ($this->blockType) {
                    case static::BLOCKTYPE_ALIGNED:
                        $this->alignedTree->readAlignLengthTable($reader);
                        $this->alignedTree->makeSymbolTable();
                        /* @noinspection PhpMissingBreakStatementInspection */
                    case static::BLOCKTYPE_VERBATIM:
                        $this->mainTree->readLengthTable($reader, 0, static::NUM_CHARS);
                        $this->mainTree->readLengthTable($reader, static::NUM_CHARS, $this->mainElements);
                        $this->mainTree->makeSymbolTable();
                        if ($this->mainTree->isIntel()) {
                            $this->intelStarted = true;
                        }
                        $this->lengthTree->readLengthTable($reader, 0, static::NUM_SECONDARY_LENGTHS);
                        $this->lengthTree->makeSymbolTable();
                        break;
                    case static::BLOCKTYPE_UNCOMPRESSED:
                        $this->intelStarted = true;
                        if ($reader->ensure(16) > 16) {
                            $reader->skip(-2);
                        }
                        $this->r0 = $reader->readUInt32();
                        $this->r1 = $reader->readUInt32();
                        $this->r2 = $reader->readUInt32();
                        break;
                    default:
                        throw new Exception('Unexpected block type '.$this->blockType);
                }
            }
            while (($thisRun = $this->remainingInBlock) > 0 && $togo > 0) {
                if ($thisRun > $togo) {
                    $thisRun = $togo;
                }
                $togo -= $thisRun;
                $this->remainingInBlock -= $thisRun;
                $this->windowPosition %= $this->windowSize;
                if ($this->windowPosition + $thisRun > $this->windowSize) {
                    throw new Exception('Trying to read more that window size bytes');
                }
                if ($this->blockType === static::BLOCKTYPE_UNCOMPRESSED) {
                    $this->window = $reader->readFully($this->window, $this->windowPosition, $thisRun);
                    $this->windowPosition += $thisRun;
                } else {
                    while ($thisRun > 0) {
                        $mainElement = $this->mainTree->readHuffmanSymbol($reader);
                        if ($mainElement < static::NUM_CHARS) {
                            $this->window[$this->windowPosition++] = $mainElement;
                            --$thisRun;
                        } else {
                            $mainElement -= static::NUM_CHARS;
                            $matchLength = $mainElement & static::NUM_PRIMARY_LENGTHS;
                            if ($matchLength === static::NUM_PRIMARY_LENGTHS) {
                                $matchLength += $this->lengthTree->readHuffmanSymbol($reader);
                            }
                            $matchLength += static::MIN_MATCH;
                            $matchOffset = $mainElement >> 3;
                            switch ($matchOffset) {
                                case 0:
                                    $matchOffset = $this->r0;
                                    break;
                                case 1:
                                    $matchOffset = $this->r1;
                                    $this->r1 = $this->r0;
                                    $this->r0 = $matchOffset;
                                    break;
                                case 2:
                                    $matchOffset = $this->r2;
                                    $this->r2 = $this->r0;
                                    $this->r0 = $matchOffset;
                                    break;
                                default:
                                    switch ($this->blockType) {
                                        case static::BLOCKTYPE_VERBATIM:
                                            if ($matchOffset !== 3) {
                                                $extra = static::$EXTRA_BITS[$matchOffset];
                                                $matchOffset = static::$POSITION_BASE[$matchOffset] - 2 + $reader->readLE($extra);
                                            } else {
                                                $matchOffset = 1;
                                            }
                                            break;
                                        case static::BLOCKTYPE_ALIGNED:
                                            $extra = static::$EXTRA_BITS[$matchOffset];
                                            $matchOffset = static::$POSITION_BASE[$matchOffset] - 2;
                                            switch ($extra) {
                                                case 0:
                                                    $matchOffset = 1;
                                                    break;
                                                case 1:
                                                case 2:
                                                    // verbatim bits only
                                                    $matchOffset += $reader->readLE($extra);
                                                    break;
                                                case 3:
                                                    // aligned bits only
                                                    $matchOffset += $this->alignedTree->readHuffmanSymbol($reader);
                                                    break;
                                                default:
                                                    // verbatim and aligned bits
                                                    $extra -= 3;
                                                    $matchOffset += ($reader->readLE($extra) << 3);
                                                    $matchOffset += $this->alignedTree->readHuffmanSymbol($reader);
                                                    break;
                                            }
                                            break;
                                        default:
                                            throw new Exception('Unexpected block type ' + $this->blockType);
                                    }
                                    $this->r2 = $this->r1;
                                    $this->r1 = $this->r0;
                                    $this->r0 = $matchOffset;
                                    break;
                            }
                            $runSrc = 0;
                            $runDest = $this->windowPosition;
                            $thisRun -= $matchLength;
                            // copy any wrapped around source data
                            if ($this->windowPosition >= $matchOffset) {
                                // no wrap
                                $runSrc = $runDest - $matchOffset;
                            } else {
                                // wrap around
                                $runSrc = $runDest + ($this->windowSize - $matchOffset);
                                $copyLength = $matchOffset - $this->windowPosition;
                                if ($copyLength < $matchLength) {
                                    $matchLength -= $copyLength;
                                    $this->windowPosition += $copyLength;
                                    while ($copyLength-- > 0) {
                                        $this->window[$runDest++] = $this->window[$runSrc++];
                                    }
                                    $runSrc = 0;
                                }
                            }
                            $this->windowPosition += $matchLength;
                            // copy match data - no worries about destination wraps
                            while ($matchLength-- > 0) {
                                $this->window[$runDest++] = $this->window[$runSrc++];
                            }
                        }
                    }
                }
            }
        }

        if ($togo !== 0) {
            throw new Exception('should never happens');
        }

        $result = array_slice($this->window, ($this->windowPosition === 0 ? $this->windowSize : $this->windowPosition) - $numberOfBytes, $numberOfBytes);

        // Intel E8 decoding
        if (($this->intelFilesize !== 0)) {
            if ($this->framesRead++ < 32768) {
                if ($numberOfBytes <= 6 || $this->intelStarted === false) {
                    $this->intelCurrentPosition += $numberOfBytes;
                } else {
                    $currentPosition = $this->intelCurrentPosition;
                    $this->intelCurrentPosition += $numberOfBytes;
                    for ($i = 0; $i < $numberOfBytes - 10;) {
                        if ($result[$i++] !== 0xe8) {
                            ++$currentPosition;
                        } else {
                            $absoluteOffset = ($result[$i] & 0xff) | (($result[$i + 1] & 0xff) << 8) | (($result[$i + 2] & 0xff) << 16) | (($result[$i + 3] & 0xff) << 24);
                            if (($absoluteOffset >= -$currentPosition) && ($absoluteOffset < $this->intelFilesize)) {
                                $referenceOffset = ($absoluteOffset >= 0) ? $absoluteOffset - $currentPosition : $absoluteOffset + $this->intelFilesize;
                                $result[$i] = $referenceOffset;
                                $result[$i + 1] = $referenceOffset >> 8;
                                $result[$i + 2] = $referenceOffset >> 16;
                                $result[$i + 3] = $referenceOffset >> 24;
                            }
                            $i += 4;
                            $currentPosition += 5;
                        }
                    }
                }
            }
        }

        return implode('', array_map('chr', $result));
    }
}
