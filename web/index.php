<?php

if (false == isset($_SERVER['SYMFONY_ENV'], $_SERVER['SYMFONY_DEBUG'])) {
    http_response_code(500);
    die('Internal error. The environment is not configured proper way.');
}
$env = (string) $_SERVER['SYMFONY_ENV'];
$debug = (bool) $_SERVER['SYMFONY_DEBUG'];

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
