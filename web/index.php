<?php

if (isset($_SERVER['SYMFONY_ENV'])) {
    $env = (string) $_SERVER['SYMFONY_ENV'];
}
elseif (isset($_ENV['SYMFONY_ENV'])) {
    $env = (string) $_ENV['SYMFONY_ENV'];
}

if (isset($_SERVER['SYMFONY_DEBUG'])) {
    $debug = (bool) $_SERVER['SYMFONY_ENV'];
}
elseif (isset($_ENV['SYMFONY_ENV'])) {
    $debug = (bool) $_ENV['SYMFONY_ENV'];
}

if (null === $env || null === $debug) {
    http_response_code(500);
    die('Internal error. The environment is not configured correctly. Please set SYMFONY_ENV and SYMFONY_DEBUG.');
}

use Symfony\Component\HttpFoundation\Request;

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel($env, $debug);
$kernel->loadClassCache();
Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
