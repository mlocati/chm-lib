[![Build Status](https://api.travis-ci.org/mlocati/chm-lib.svg?branch=master)](https://travis-ci.org/mlocati/chm-lib)
[![HHVM Status](http://hhvm.h4cc.de/badge/mlocati/chm-lib.svg?style=flat)](http://hhvm.h4cc.de/package/mlocati/chm-lib)
[![StyleCI Status](https://styleci.io/repos/58052834/shield)](https://styleci.io/repos/58052834)
[![Coverage Status](https://coveralls.io/repos/github/mlocati/chm-lib/badge.svg?branch=master)](https://coveralls.io/github/mlocati/chm-lib?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mlocati/chm-lib/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mlocati/chm-lib/?branch=master)

# CHMLib - Read CHM (Microsoft Compiled HTML Help) files from PHP


## Sample usage


```php
require_once 'CHMLib.php'; // You don't need this if you use Composer

$chm = \CHMLib\CHM::fromFile('YourFile.chm');
foreach ($chm->getEntries(\CHMLib\Entry::TYPE_FILE) as $entry) {
    echo "File: ", $entry->getPath(), "\n";
    echo "Contents: ", $entry->getContents(), "\n\n";
}
```
