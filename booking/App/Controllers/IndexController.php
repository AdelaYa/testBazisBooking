<?php declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;

class IndexController {

    public function index(Request $request): void {
        Response::json([
            'name' => 'Table Booking REST API',
            'status' => 'ok',
            'endpoints' => [
                'GET /api/tables/available',
                'POST /api/bookings',
                'GET /api/bookings',
                'PUT /api/bookings/{id}',
                'DELETE /api/bookings/{id}',
            ],
        ]);
    }
}