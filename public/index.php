<?php

use App\Controller\HomeController;
use App\Service\ErrorHandler;
use AttributeRouter\Router;
use DI\Container;

ini_set('display_errors', 'off');
error_reporting(0);

require '../vendor/autoload.php';

const ROOT = __DIR__ . '/../';
$container = new Container;
$container->get(ErrorHandler::class);
$router = $container->get(Router::class);
$router->registerRoutes([HomeController::class]);
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];
$router->dispatch($requestUri, $requestMethod);
$router->invokeController();
