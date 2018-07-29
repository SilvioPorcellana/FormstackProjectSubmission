<?php

require $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
spl_autoload_register('autoload');

function autoload($class)
{
    include_once $_SERVER['DOCUMENT_ROOT'] . '/../' . str_replace('\\', '/', $class) . '.php';
}
