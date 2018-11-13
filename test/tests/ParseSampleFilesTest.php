<?php

namespace CHMLib\Test;

use CHMLib\CHM;
use CHMLib\Entry;
use PHPUnit\Framework\TestCase;

class ParseSampleFilesTest extends TestCase
{
    public function compareContentProvider()
    {
        $result = array();
        foreach (SampleData::getInstances() as $sampleData) {
            foreach ($sampleData->getExtractedFiles() as $extractedFile) {
                $result[] = array($sampleData, $extractedFile);
            }
        }

        return $result;
    }

    /**
     * @dataProvider compareContentProvider
     */
    public function testCompareContent(SampleData $sampleData, $extractedFile)
    {
        $chm = $sampleData->getCHM();
        $entry = $chm->getEntryByPath($extractedFile);
        $this->assertNotNull($entry, "Failed to find file in CHM: $extractedFile");
        $this->assertInstanceOf('CHMLib\Entry', $entry);
        $expectedContent = file_get_contents($sampleData->getExtractedDirectory().'/'.$extractedFile);
        $extractedContent = $entry->getContents();
        $this->assertSame($expectedContent, $extractedContent, "Wrong extracted content for file $extractedFile in archive {$sampleData->getCHMFile()}");
    }

    public function findAllFilesProvider()
    {
        $result = array();
        foreach (SampleData::getInstances() as $sampleData) {
            $result[] = array($sampleData);
        }

        return $result;
    }

    /**
     * @dataProvider findAllFilesProvider
     */
    public function testFindAllFiles(SampleData $sampleData)
    {
        $chm = $sampleData->getCHM();
        $foundFiles = array();
        foreach ($chm->getEntries(-1 & ~Entry::TYPE_DIRECTORY) as $entry) {
            if (strpos($entry->getPath(), ':') === false) {
                $foundFiles[] = $entry->getPath();
            }
        }
        $extraFoundFiles = array_diff($foundFiles, $sampleData->getExtractedFiles());
        $this->assertEmpty($extraFoundFiles, 'Some extra files found');
        $notDetectedFiles = array_diff($sampleData->getExtractedFiles(), $foundFiles);
        $this->assertEmpty($extraFoundFiles, 'Some file have not been found');
    }
}
