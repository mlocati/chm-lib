<?php

namespace CHMLib\Section;

use CHMLib\CHM;

/**
 * Represent a generic section of data in a CHM file.
 */
abstract class Section
{
    /**
     * The parent CHM file.
     *
     * @var CHM
     */
    protected $chm;

    /**
     * The offset of the section data.
     *
     * @var int
     */
    protected $sectionOffset;

    /**
     * Initializes the instance.
     *
     * @param \CHMLib\CHM $chm The parent CHM file.
     */
    public function __construct(CHM $chm)
    {
        $this->chm = $chm;
    }

    /**
     * Return the (uncompressed) content.
     *
     * @param int $offset The position where the data starts (relative to the start of this section).
     * @param int $length The length of the (compressed) data.
     *
     * @throws \Exception Throws an Exception in case of errors.
     *
     * @return string
     */
    abstract public function getContents($offset, $length);
}
