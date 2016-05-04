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

```php
require_once 'CHMLib.php'; // You don't need this if you use Composer

$chm = \CHMLib\CHM::fromFile('YourFile.chm');
foreach ($chm->getEntries(\CHMLib\Entry::TYPE_FILE) as $entry) {
    echo "File: ", $entry->getPath(), "\n";
    echo "Contents: ", $entry->getContents(), "\n\n";
}
```
