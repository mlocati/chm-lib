<?php

namespace CHMLib\Reader;

use Exception;

/**
 * Read data from a raw string of bytes.
 */
class StringReader extends Reader
{
    /**
     * The raw string of bytes.
     *
     * @var string
     */
    protected $string;

    /**
     * The current position in the string.
     *
     * @var int
     */
    protected $position;

    /**
     * The string length.
     *
     * @var int
     */
    protected $length;

    /**
     * Initializes the instance.
     *
     * @param string $string The raw string containing the data to be read.
     */
    public function __construct($string)
    {
        $this->string = (string) $string;
        $this->position = 0;
        $this->length = strlen($this->string);
    }

    /**
     * {@inheritdoc}
     *
     * @see Reader::setPosition()
     */
    public function setPosition($position)
    {
        if ($position < 0 || $position > $this->length) {
            throw new Exception('Failed to seek string to to position '.$position);
        }
        $this->position = $position;
    }

    /**
     * {@inheritdoc}
     *
     * @see Reader::getPosition()
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     *
     * @see Reader::getLength()
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * {@inheritdoc}
     *
     * @see Reader::readString()
     */
    public function readString($length)
    {
        if (!$length) {
            $result = '';
        } elseif ($this->position + $length > $this->length) {
            throw new Exception('Read after end-of-string');
        } else {
            $result = substr($this->string, $this->position, $length);
            $this->position += $length;
        }

        return $result;
    }
}
