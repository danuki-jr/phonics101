<?php


use ReadXYZ\Models\RouteMe;
use Symfony\Component\ErrorHandler\Debug;

require dirname(__DIR__).'/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

RouteMe::parseRoute();