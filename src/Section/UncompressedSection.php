<?php

namespace CHMLib\Section;

use CHMLib\CHM;

/**
 * Represent an uncompressed section of data in a CHM file.
 */
class UncompressedSection extends Section
{
    /**
     * Initializes the instance.
     *
     * @param \CHMLib\CHM $chm The parent CHM file.
     */
    public function __construct(CHM $chm)
    {
        parent::__construct($chm);
        $this->sectionOffset = $chm->getITSF()->getContentOffset();
    }

    /**
     * {@inheritdoc}
     *
     * @see \CHMLib\Section\Section::getContents()
     */
    public function getContents($offset, $length)
    {
        if ($length === 0) {
            $result = '';
        } else {
            $reader = $this->chm->getReader();
            $reader->setPosition($this->sectionOffset + $offset);
            $result = $reader->readString($length);
        }

        return $result;
    }
}
