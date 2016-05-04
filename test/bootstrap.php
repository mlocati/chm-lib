<?php

require_once __DIR__.'/../CHMLib.php';

spl_autoload_register(
    function ($class) {
        if (strpos($class, 'CHMLib\\Test\\') !== 0) {
            return;
        }
        $file = __DIR__.DIRECTORY_SEPARATOR.'tests'.str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen('CHMLib\\Test'))).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
);
