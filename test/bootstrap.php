<?php

if (class_exists('PHPUnit\\Runner\\Version') && version_compare(PHPUnit\Runner\Version::id(), '9') >= 0) {
    class_alias('CHMLib\\Test\\TestCase9', 'CHMLib\\Test\\TestCase');
} elseif (class_exists('PHPUnit\\Runner\\Version') && version_compare(PHPUnit\Runner\Version::id(), '7') >= 0) {
    class_alias('CHMLib\\Test\\TestCase7', 'CHMLib\\Test\\TestCase');
} else {
    class_alias('CHMLib\\Test\\TestCase4', 'CHMLib\\Test\\TestCase');
}
