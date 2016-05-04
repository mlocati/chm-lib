<?php

spl_autoload_register(
    function ($class) {
        if (strpos($class, 'CHMLib\\') !== 0) {
            return;
        }
        $file = __DIR__.DIRECTORY_SEPARATOR.'src'.str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen('CHMLib'))).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
);
