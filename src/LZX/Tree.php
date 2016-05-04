<?php

namespace CHMLib\LZX;

use Exception;
use CHMLib\Reader\BitReader;

/**
 * Huffman tree used in LZX decoding.
 */
class Tree
{
    /**
     * Table decoding overruns are allowed.
     *
     * @var int
     */
    const LENTABLE_SAFETY = 64;

    /**
     * Maximum number of symbols in the pre-tree.
     *
     * @var int
     */
    const PRETREE_NUM_ELEMENTS = 20;

    /**
     * The number of bits.
     *
     * @var int
     */
    protected $bits;

    /**
     * The maximum symbol.
     *
     * @var int
     */
    protected $maxSymbol;

    /**
     * The symbol list used to decode.
     *
     * @var int[]
     */
    protected $symbols;

    /**
     * The code lengths table.
     *
     * @var int[]
     */
    protected $lens;

    /**
     * Initialize the instance.
     *
     * @param int $bits The number of bits.
     * @param int $maxSymbol The maximum symbol.
     */
    public function __construct($bits, $maxSymbol)
    {
        $this->bits = $bits;
        $this->maxSymbol = $maxSymbol;
        $this->symbols = array_fill(0, (1 << $this->bits) + ($this->maxSymbol << 1), 0);
        $this->lens = array_fill(0, $this->maxSymbol + static::LENTABLE_SAFETY, 0);
    }

    /**
     * Build a fast Huffman decoding table out of just a canonical Huffman code lengths table.
     *
     * @throws Exception Throws an Exception in case of errors.
     *
     * @author This function was coded by David Tritscher.
     */
    public function makeSymbolTable()
    {
        $bitNum = 1;
        $pos = 0;
        $tableMask = 1 << $this->bits;
        $bitMask = $tableMask >> 1;
        $nextSymbol = $bitMask;
        while ($bitNum <= $this->bits) {
            for ($symbol = 0; $symbol < $this->maxSymbol; ++$symbol) {
                if ($this->lens[$symbol] === $bitNum) {
                    $leaf = $pos;
                    $pos += $bitMask;
                    if ($pos > $tableMask) {
                        throw new Exception('Symbol table overruns');
                    }
                    while ($leaf < $pos) {
                        $this->symbols[$leaf++] = $symbol;
                    }
                }
            }
            $bitMask >>= 1;
            ++$bitNum;
        }
        if ($pos !== $tableMask) {
            for ($i = $pos; $i < $tableMask; ++$i) {
                $this->symbols[$i] = 0;
            }
            $pos <<= 16;
            $tableMask <<= 16;
            $bitMask = 1 << 15;
            while ($bitNum <= 16) {
                for ($symbol = 0; $symbol < $this->maxSymbol; ++$symbol) {
                    if ($this->lens[$symbol] === $bitNum) {
                        $leaf = $pos >> 16;
                        for ($fill = 0; $fill < $bitNum - $this->bits; ++$fill) {
                            if ($this->symbols[$leaf] === 0) {
                                $nextSymbol2 = $nextSymbol << 1;
                                $this->symbols[$nextSymbol2] = 0;
                                $this->symbols[$nextSymbol2 + 1] = 0;
                                $this->symbols[$leaf] = $nextSymbol++;
                            }
                            $leaf = $this->symbols[$leaf] << 1;
                            if ((($pos >> (15 - $fill)) & 1) !== 0) {
                                ++$leaf;
                            }
                        }
                        $this->symbols[$leaf] = $symbol;
                        $pos += $bitMask;
                        if ($pos > $tableMask) {
                            throw new Exception('Symbol table overflow');
                        }
                    }
                }
                $bitMask >>= 1;
                ++$bitNum;
            }
        }
        if ($pos !== $tableMask) {
            for ($sym = 0; $sym < $this->maxSymbol; ++$sym) {
                if ($this->lens[$sym] !== 0) {
                    throw new Exception('Erroneous symbol table');
                }
            }
        }
    }

    /**
     * Read the align lengths data.
     *
     * @param BitReader $reader The reader that provides the data.
     *
     * @throws Exception Throws an Exception in case of errors.
     */
    public function readAlignLengthTable(BitReader $reader)
    {
        for ($i = 0; $i < $this->maxSymbol; ++$i) {
            $this->lens[$i] = $reader->readLE(3);
        }
    }

    /**
     * Read in code lengths for symbols first to last in the given table.
     * The code lengths are stored in their own special LZX way.
     *
     * @param BitReader $reader The reader that provides the data.
     * @param int $first
     * @param int $last
     */
    public function readLengthTable(BitReader $reader, $first, $last)
    {
        $preTree = new self(6, static::PRETREE_NUM_ELEMENTS);
        for ($i = 0; $i < $preTree->maxSymbol; ++$i) {
            $preTree->lens[$i] = $reader->readLE(4);
        }
        $preTree->makeSymbolTable();
        for ($pos = $first; $pos < $last;) {
            $symbol = $preTree->readHuffmanSymbol($reader);
            switch ($symbol) {
                case 0x11:
                    $pos2 = $pos + $reader->readLE(4) + 4;
                    while ($pos < $pos2) {
                        $this->lens[$pos++] = 0;
                    }
                    break;
                case 0x12:
                    $pos2 = $pos + $reader->readLE(5) + 20;
                    while ($pos < $pos2) {
                        $this->lens[$pos++] = 0;
                    }
                    break;
                case 0x13:
                    $pos2 = $pos + $reader->readLE(1) + 4;
                    $symbol = $this->lens[$pos] - $preTree->readHuffmanSymbol($reader);
                    if ($symbol < 0) {
                        $symbol += 0x11;
                    }
                    while ($pos < $pos2) {
                        $this->lens[$pos++] = $symbol;
                    }
                    break;
                default:
                    $symbol = $this->lens[$pos] - $symbol;
                    if ($symbol < 0) {
                        $symbol += 0x11;
                    }
                    $this->lens[$pos++] = $symbol;
                    break;
            }
        }
    }

    /** 
     * Decode a Huffman symbol from the bitstream using the stated table and return it.
     *
     * @param BitReader $reader The reader that provides the data.
     *
     * @return int
     *
     * @throws Exception Throws an Exception in case of errors.
     */
    public function readHuffmanSymbol(BitReader $reader)
    {
        $next = $reader->peek(16, true);
        $symbol = $this->symbols[$reader->peek($this->bits, true)];
        if ($symbol >= $this->maxSymbol) {
            $j = 1 << (16 - $this->bits);
            do {
                $j >>= 1;
                $symbol <<= 1;
                $symbol |= ($next & $j) > 0 ? 1 : 0;
                $symbol = $this->symbols[$symbol];
            } while ($symbol >= $this->maxSymbol);
        }
        $reader->readLE($this->lens[$symbol]);

        return $symbol;
    }

    /**
     * Clear the code lengths table. 
     */
    public function clear()
    {
        $count = count($this->lens);
        $this->lens = array_fill(0, $count, 0);
    }

    /**
     * Check if the length at 0xe8 is not zero.
     *
     * @return bool
     */
    public function isIntel()
    {
        return $this->lens[0xe8] !== 0;
    }
}
