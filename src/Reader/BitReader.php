<?php

namespace CHMLib\Reader;

use Exception;

/**
 * Read data from a raw string of bytes.
 */
class BitReader extends StringReader
{
    /**
     * The maximum size of the buffer in bits.
     *
     * @var int
     */
    const BUFFER_BITS = 32;

    /**
     * Pre-calculated mask to make sure we get.
     *
     * @var int[]
     */
    protected static $UNSIGNED_MASK = array(
        0x0000,
        0x0001, 0x0003, 0x0007, 0x000f,
        0x001f, 0x003f, 0x007f, 0x00ff,
        0x01ff, 0x03ff, 0x07ff, 0x0fff,
        0x1fff, 0x3fff, 0x7fff, 0xffff,
    );

    /**
     * The current bit buffer.
     *
     * @var int
     */
    protected $buffer;

    /**
     * The number of bits in the buffer.
     *
     * @var int
     */
    protected $bufferSize;

    /**
     * Initializes the instance.
     *
     * @param string $string The raw string containing the data to be read.
     */
    public function __construct($string)
    {
        parent::__construct($string);
        $this->buffer = 0;
        $this->bufferSize = 0;
    }

    /**
     * Make sure that there are at least $n (<=16) bits in the buffer.
     * If less than $n bits are there, read a 16-bit little-endian word from the byte array.
     *
     * @param int $n The minimum number of bits that should be available in the remaining bit buffer.
     *
     * @return int Return the number of remaining bits in the buffer.
     */
    public function ensure($n)
    {
        while ($this->bufferSize < $n) {
            if (($this->length - $this->position) < 2) {
                $this->position = $this->length;
                break;
            }
            $word = $this->readUInt16();
            $this->buffer |= $word << (static::BUFFER_BITS - 16 - $this->bufferSize);
            $this->bufferSize += 16;
        }

        return $this->bufferSize;
    }

    /**
     * Peek n bits, may raise an EOF Exception if there are not enough bits and $suppressException is false.
     *
     * @param int $n The number of bits to peek.
     * @param bool $suppressException Set to true to avoid raising an exception if there are not enough bits.
     *
     * @return int
     */
    public function peek($n, $suppressException = false)
    {
        if ($this->ensure($n) < $n) {
            if (!$suppressException) {
                throw new Exception('EOF');
            }
        }

        return (($this->buffer >> (static::BUFFER_BITS - $n))) & static::$UNSIGNED_MASK[$n];
    }

    /**
     * Read no more than 16 bits (represented as a little endian integer).
     *
     * @param int $n The number of bits to retrieve.
     *
     * @return int
     */
    public function readLE($n)
    {
        $result = $this->peek($n);
        $this->buffer <<= $n;
        $this->bufferSize -= $n;

        return $result;
    }

    /**
     * Reset the bits buffer and flush $n bytes.
     *
     * @param int $n The number of bytes to skip.
     *
     * @throws \Exception Throws an Exception in case of errors.
     */
    public function skip($n)
    {
        $this->buffer = 0;
        $this->bufferSize = 0;
        $this->setPosition($this->position + $n);
    }

    /**
     * Read a number of bytes and insert them in an array at a specified position.
     *
     * @param array $buffer The buffer where the data should be inserted.
     * @param int $offset The offset at which the data should be inserted.
     * @param int $length The number of bytes to read.
     *
     * @throws \Exception Throws an Exception in case of errors.
     *
     * @return array Return the $buffer array with filled-in bytes.
     */
    public function readFully($buffer, $offset, $length)
    {
        if ($length === 0) {
            $result = $buffer;
        } else {
            $bufferSize = count($buffer);
            $postSize = $bufferSize - $offset - $length;
            if ($postSize < 0) {
                throw new Exception('Buffer too small');
            }
            $pre = ($offset === 0) ? array() : array_slice($buffer, 0, $offset);
            $mid = $this->readBytes($length);
            $post = ($postSize === 0) ? array() : array_slice($buffer, -$postSize);
            $result = array_merge($pre, $mid, $post);
        }

        return $result;
    }
}
