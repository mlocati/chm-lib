[![Build Status](https://api.travis-ci.org/mlocati/chm-lib.svg?branch=master)](https://travis-ci.org/mlocati/chm-lib)
[![HHVM Status](http://hhvm.h4cc.de/badge/mlocati/chm-lib.svg?style=flat)](http://hhvm.h4cc.de/package/mlocati/chm-lib)
[![StyleCI Status](https://styleci.io/repos/58052834/shield)](https://styleci.io/repos/58052834)
[![Coverage Status](https://coveralls.io/repos/github/mlocati/chm-lib/badge.svg?branch=master)](https://coveralls.io/github/mlocati/chm-lib?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mlocati/chm-lib/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mlocati/chm-lib/?branch=master)

# CHMLib - Read CHM files from PHP

This PHP library can read CHM (Microsoft Compiled HTML Help) files and extract their contents.

There's no external dependencies to use this library: the only requirement is PHP 5.3.3 or greater (PHP 7 and HHVM are supported too).


## Tests

You can run your own tests against this library (see [here](https://github.com/mlocati/chm-lib/blob/master/test/samples/README.md) for details).
In the GitHub repo I included only one test file, but I tested locally all the CHM files I found in my PC (they are about 400), and everything worked like a charm:wink:.


## Sample usage

### Analyzing the contents of a CHM file

```php
require_once 'CHMLib.php'; // You don't need this if you use Composer

$chm = \CHMLib\CHM::fromFile('YourFile.chm');
foreach ($chm->getEntries(\CHMLib\Entry::TYPE_FILE) as $entry) {
    echo "File: ", $entry->getPath(), "\n";
    echo "Contents: ", $entry->getContents(), "\n\n";
}
```

### Extracting the contents of a CHM file to a local directory

```php
<?php
use \CHMLib\CHM;
use \CHMLib\Entry;

// Specify the output directory
$outputDirectory = 'output';

// Specify the input CHM file
$inputCHMFile = 'YourFile.chm';

require_once 'CHMLib.php';

if (!is_dir($outputDirectory)) {
    mkdir($outputDirectory, 0777, true);
}
$chm = CHM::fromFile($inputCHMFile);
foreach ($chm->getEntries(Entry::TYPE_FILE) as $entry) {
    echo "Processing {$entry->getPath()}... ";
    $entryPath = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $entry->getPath()), DIRECTORY_SEPARATOR);
    $parts = explode(DIRECTORY_SEPARATOR, $entryPath);
    $subDirectory = count($parts) > 1 ? implode(DIRECTORY_SEPARATOR, array_splice($parts, 0, -1)) : '';
    $filename = array_pop($parts);
    $path = $outputDirectory;
    if ($subDirectory !== '') {
        $path .= DIRECTORY_SEPARATOR . $subDirectory;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
    $path .= DIRECTORY_SEPARATOR . $filename;
    file_put_contents($path, $entry->getContents());
    echo "done.\n";
}
echo "\nAll done.\n";
```

### Parsing Index and TOC

```php
require_once 'CHMLib.php'; // You don't need this if you use Composer

function printTree($tree, $level)
{
    if ($tree !== null) {
        foreach ($tree->getItems() as $child) {
            echo str_repeat("\t", $level).print_r($child->getName(), 1)."\n";
            printTree($child->getChildren(), $level + 1);
        }
    }
}


$chm = \CHMLib\CHM::fromFile('YourFile.chm');

$toc = $chm->getTOC(); // Parse the contents of the .hhc file
$index = $chm->getIndex(); // Parse the contents of the .hhk file

printTree($toc, 0);
```

### Resolving multiple-CHM TOCs

Some CHM file may come splitted into multiple CHM files.
Let's assume that we have a main file (`main.chm`) that contains a TOC that references two other CHM files (`sub1.chm` and `sub2.chm`).

This can easily be parsed with this code:

```php
require_once 'CHMLib.php'; // You don't need this if you use Composer

$main = \CHMLib\CHM::fromFile('main.chm');
$map = new \CHMLib\Map();
$map->add('sub1.chm', \CHMLib\CHM::fromFile('sub1.chm'));
$map->add('sub2.chm', \CHMLib\CHM::fromFile('sub2.chm'));

$toc = $main->getTOC();
$toc->resolve($map);

// Now the TOC of the main CHM file contains references to the entries in the other two CHM files 
printTree($toc, 0);
```
