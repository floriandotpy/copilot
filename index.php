<?php


// handle php webserver static files
// start php server: php -S localhost:8080 index.php
if (PHP_SAPI == 'cli-server' && is_file(__DIR__.parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}


include_once(__DIR__.'/system/bootstrap.php');

copi::run();
