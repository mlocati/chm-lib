<?php

return Symfony\CS\Config\Config::create()
    ->fixers(array(
        // Don't vertically align phpdoc tags
        '-phpdoc_params',
        // Allow 'return null'
        '-empty_return',
    ))
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->exclude(array('vendor'))
            ->in(__DIR__)
    )
;
