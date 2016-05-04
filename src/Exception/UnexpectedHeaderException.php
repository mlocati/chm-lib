<?php

namespace CHMLib\Exception;

/**
 * Exception thrown when finding an unexpected header.
 */
class UnexpectedHeaderException extends Exception
{
    /**
     * The expected header identifier.
     *
     * @var string
     */
    protected $expectedHeader;

    /**
     * The found header identifier.
     *
     * @var string
     */
    protected $foundHeader;

    /**
     * Create a new instance.
     *
     * @param string $expectedHeader The expected header identifier.
     * @param string $foundHeader The found header identifier.
     */
    public static function create($expectedHeader, $foundHeader)
    {
        $result = new static("Invalid header identifier: expecting '$expectedHeader', found '$foundHeader'");
        $result->expectedHeader = $expectedHeader;
        $result->foundHeader = $foundHeader;

        return $result;
    }

    /**
     * Get the expected header identifier.
     *
     * @return string
     */
    public function getExpectedHeader()
    {
        return $this->expectedHeader;
    }

    /**
     * Get the found header identifier.
     *
     * @return string
     */
    public function getFoundHeader()
    {
        return $this->foundHeader;
    }
}
