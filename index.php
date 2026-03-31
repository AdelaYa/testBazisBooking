<?php declare(strict_types=1);

use App\Http\Request;
use App\Http\Router;

spl_autoload_register(function (string $class): bool {
    $file = __DIR__ . DIRECTORY_SEPARATOR . 'booking' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    return false;
});

$request = Request::fromServer();
$router = new Router();

$router->dispatch($request);
