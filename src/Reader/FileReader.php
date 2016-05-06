<?php

namespace CHMLib\Reader;

use Exception;

/**
 * Read data from a file.
 */
class FileReader extends Reader
{
    /**
     * The file name.
     *
     * @var string
     */
    protected $filename;

    /**
     * The file size.
     *
     * @var int
     */
    protected $length;

    /**
     * The open file descriptor.
     *
     * @var resource
     */
    protected $fd;

    /**
     * Initializes the instance.
     *
     * @param string $filename The file name to be read.
     *
     * @throws Exception Throws an Exception in case of errors.
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
        if (!is_file($this->filename)) {
            throw new Exception('File not found: '.$this->filename);
        }
        if (!is_readable($this->filename)) {
            throw new Exception('File not readable: '.$this->filename);
        }
        $this->length = @filesize($this->filename);
        if ($this->length === false || $this->length < 0) {
            throw new Exception('Failed to retrieve the size of the file '.$this->filename);
        }
        $this->fd = @fopen($this->filename, 'rb');
        if ($this->fd === false) {
            $this->fd = null;
            throw new Exception('Failed to open file '.$this->filename);
        }
    }

    /**
     * Destruct the instance.
     */
    public function __destruct()
    {
        if (isset($this->fd)) {
            @fclose($this->fd);
            $this->fd = null;
        }
    }

    /**
     * Get the file name.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * {@inheritdoc}
     *
     * @see Reader::setPosition()
     */
    public function setPosition($position)
    {
        if (@fseek($this->fd, $position, SEEK_SET) !== 0) {
            throw new Exception('Failed to seek file to to position '.$position);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see Reader::getPosition()
     */
    public function getPosition()
    {
        $result = @ftell($this->fd);
        if ($result === false) {
            throw new Exception('Failed to get the current file position');
        }

        return $result;
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
        if ($length === 0) {
            $result = '';
        } else {
            $result = @fread($this->fd, $length);
            if ($result === false || strlen($result) !== $length) {
                throw new Exception('Read after end-of-file');
            }
        }

        return $result;
    }
}
