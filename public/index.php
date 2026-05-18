<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Support docroot = public with app in public/OCL_HR (server), or standard public/ (local).
$laravelRoot = is_file(__DIR__.'/OCL_HR/vendor/autoload.php')
    ? __DIR__.'/OCL_HR'
    : dirname(__DIR__);

if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $laravelRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
