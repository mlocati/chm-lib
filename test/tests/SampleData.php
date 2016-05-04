<?php

namespace CHMLib\Test;

use CHMLib\CHM;

class SampleData
{
    /**
     * The full path to the sample CHM filename.
     *
     * @var string
     */
    protected $chmFile;

    /**
     * The full path to the directory that contains the extracted files.
     *
     * @var string
     */
    protected $extractedDirectory;

    /**
     * The list of the extracted files (paths are relative to the extracted directory).
     *
     * @var string[]
     */
    protected $extractedFiles;

    /**
     * The CHM instance.
     *
     * @var CHM|null
     */
    protected $chm;

    /**
     * Initializes the instance.
     *
     * @param string $chmFile The full path to the sample CHM filename.
     * @param string $extractedDirectory The full path to the directory that contains the extracted files.
     */
    public function __construct($chmFile, $extractedDirectory)
    {
        $this->chmFile = $chmFile;
        $this->extractedDirectory = $extractedDirectory;
        $this->extractedFiles = static::listDirectoryContents($this->extractedDirectory, '/');
        $this->chm = null;
    }

    /**
     * Recursively list the contents of a directory.
     *
     * @param string $parentDirectory
     * @param string $relativePath
     *
     * @return string[]
     */
    protected static function listDirectoryContents($parentDirectory, $relativePath)
    {
        $result = array();
        foreach (scandir($parentDirectory) as $f) {
            switch ($f) {
                case '.':
                case '..':
                    break;
                default:
                    $full = $parentDirectory.'/'.$f;
                    $relative = $relativePath.$f;
                    if (is_dir($full)) {
                        $result = array_merge($result, self::listDirectoryContents($full, $relative.'/'));
                    } else {
                        $result[] = $relative;
                    }
                    break;
            }
        }

        return $result;
    }

    /**
     * Get the full path to the sample CHM filename.
     *
     * @return string
     */
    public function getCHMFile()
    {
        return $this->chmFile;
    }

    /**
     * Get the full path to the directory that contains the extracted files.
     *
     * @return string
     */
    public function getExtractedDirectory()
    {
        return $this->extractedDirectory;
    }

    /**
     * Get the list of the extracted files (paths are relative to the extracted directory).
     *
     * @return string[]
     */
    public function getExtractedFiles()
    {
        return $this->extractedFiles;
    }

    /**
     * Get the CHM instance.
     *
     * @return CHM|null
     */
    public function getCHM()
    {
        if ($this->chm === null) {
            $this->chm = CHM::fromFile($this->chmFile);
        }

        return $this->chm;
    }

    /**
     * Set the CHM instance.
     *
     * @param CHM $chm
     */
    public function setCHM(CHM $chm)
    {
        $this->chm = $chm;
    }

    /**
     * Return a textual representation of this instance.
     *
     * @return string
     */
    public function __toString()
    {
        return basename($this->chmFile);
    }

    /**
     * The already listed instances.
     *
     * @var static[]|null
     */
    protected static $instances = null;

    /**
     * Get all the available sample data.
     *
     * return static[]
     */
    public static function getInstances()
    {
        if (static::$instances === null) {
            $instances = array();
            $samplesDirectory = str_replace(DIRECTORY_SEPARATOR, '/', dirname(dirname(__FILE__))).'/samples';
            foreach (scandir($samplesDirectory) as $f) {
                if (strlen($f) > 4 && strcasecmp(substr($f, -4), '.chm') === 0) {
                    $chmFile = $samplesDirectory.'/'.$f;
                    if (is_file($chmFile)) {
                        $extractedDirectory = substr($chmFile, 0, -4);
                        if (is_dir($extractedDirectory)) {
                            $instances[] = new static($chmFile, $extractedDirectory);
                        }
                    }
                }
            }
            static::$instances = $instances;
        }

        return static::$instances;
    }
}
