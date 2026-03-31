<?php declare(strict_types=1);

namespace App\Http;

use App\Controllers\BookingsController;
use App\Controllers\IndexController;
use App\Controllers\TablesController;
use Throwable;

final class Router {
    private array $routes;

    public function __construct() {
        $this->routes = [
            ''                     => [
                'GET' => [IndexController::class, 'index'],
            ],
            'api/tables/available' => [
                'GET' => [TablesController::class, 'available'],
            ],
            'api/bookings/(\d+)'   => [
                'GET'    => [BookingsController::class, 'getBooking'],
                'PUT'    => [BookingsController::class, 'updateBooking'],
                'DELETE' => [BookingsController::class, 'cancelBooking'],
            ],
            'api/bookings'         => [
                'GET'  => [BookingsController::class, 'getBookingsList'],
                'POST' => [BookingsController::class, 'createBooking'],
            ],
        ];
    }

    public function dispatch(Request $request): void {
        $path   = trim($request->path(), '/');
        $method = $request->method();

        try {
            foreach ($this->routes as $route => $methods) {
                if (!isset($methods[$method])) {
                    continue;
                }

                if (!preg_match('#^' . $route . '$#', $path, $matches)) {
                    continue;
                }

                [$controllerClass, $action] = $methods[$method];
                $controller = new $controllerClass();
                array_shift($matches);
                $matches = array_map(
                    fn($v) => is_numeric($v) ? (int)$v : $v,
                    $matches
                );
                $controller->$action($request, ...$matches);
                exit;

            }
        } catch (HttpException $exception) {
            Response::error($exception->getMessage(), $exception->status(), $exception->details());
        } catch (Throwable $exception) {
            Response::error('Internal server error.', 500, [
                'exception' => get_class($exception),
            ]);
        }

        Response::error('Route not found.', 404);
    }
}
