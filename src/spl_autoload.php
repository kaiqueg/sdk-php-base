<?php

$classBase = __DIR__;
spl_autoload_register(function ($className) use ($classBase) {
    $classFile = $classBase . str_replace(["SdkBase", "\\"], ["", DIRECTORY_SEPARATOR], $className) . ".php";
    if (file_exists($classFile)) {
        include_once $classFile;
    }
});