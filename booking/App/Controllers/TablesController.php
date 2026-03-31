<?php declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\TablesRepository;
use App\Validation\RequestValidator;

final class TablesController {
    public TablesRepository $tablesRepository;

    public function __construct() {
        $this->tablesRepository = new TablesRepository();
    }

    public function available(Request $request): void {
        $date      = RequestValidator::dateField($request->query('date'), 'date');
        $startTime = RequestValidator::timeField($request->query('start_time'), 'start_time');
        $endTime   = RequestValidator::timeField($request->query('end_time'), 'end_time');
        RequestValidator::validateDateTimeRange($date, $startTime, $endTime);

        $guestsCount = $request->query('guests_count');
        $guestsCount = $guestsCount === null || $guestsCount === '' ? null : RequestValidator::positiveInt($guestsCount, 'guests_count');

        $tables = $this->tablesRepository->findAvailableByDateTime($date, $startTime, $endTime, $guestsCount);

        Response::json(['data' => $tables]);
    }
}
