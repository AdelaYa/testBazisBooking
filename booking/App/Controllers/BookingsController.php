<?php declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\BookingsRepository;
use App\Validation\RequestValidator;
use Throwable;

class BookingsController {

    public BookingsRepository $bookingsRepository;

    public function __construct() {
        $this->bookingsRepository = new BookingsRepository();
    }

    /**
     * @throws Throwable
     */
    public function createBooking(Request $request): void {
        $data    = RequestValidator::validateCreate($request->json());
        $booking = $this->bookingsRepository->createBooking($data);

        Response::json(['data' => $booking], 201);

    }

    /**
     * @throws Throwable
     */
    public function updateBooking(Request $request, int $id): void {
        $data    = RequestValidator::validateUpdate($request->json());
        $booking = $this->bookingsRepository->updateBooking($id, $data);

        Response::json(['data' => $booking]);


    }

    public function getBooking(Request $request, int $id): void {
        $id      = RequestValidator::validateGet($id);
        $booking = $this->bookingsRepository->getBooking($id);
        Response::json(['data' => $booking]);
    }

    public function cancelBooking(Request $request, int $id): void {
        $this->bookingsRepository->cancelBooking($id);
        Response::noContent();
    }

    public function getBookingsList(Request $request): void {
        $filters = [];

        $date = $request->query('date');
        if ($date !== null && $date !== '') {
            $filters['date'] = RequestValidator::dateField($date, 'date');
        }

        $status = $request->query('status');
        if ($status !== null && $status !== '') {
            $filters['status'] = RequestValidator::validateStatus($status);
        }

        $page  = (int)($request->query('page', 1));
        $limit = (int)($request->query('limit', 10));
        $page  = max(1, $page);
        $limit = min(100, max(1, $limit));

        $result = $this->bookingsRepository->list($filters, $page, $limit);

        Response::json(['data' => $result['items'], 'pagination' => [
            'total' => $result['total'],
            'page'  => $result['page'],
            'limit' => $result['limit'],
            'pages' => $result['pages'],
        ]]);
    }
}
