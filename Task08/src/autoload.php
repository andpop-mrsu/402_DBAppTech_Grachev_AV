<?php

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/';
    
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    $directFile = $baseDir . $class . '.php';
    if (file_exists($directFile)) {
        require_once $directFile;
        return;
    }
    
    $controllerFile = $baseDir . 'Controllers/' . $class . '.php';
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        return;
    }
});
