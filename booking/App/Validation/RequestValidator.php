<?php declare(strict_types=1);

namespace App\Validation;

use App\Http\HttpException;

final class RequestValidator {

    public const MAX_DURATION_MINUTES = 5 * 60;

    public static function validateCreate(array $input): array {
        $required = ['table_id', 'guest_name', 'guest_phone', 'booking_date', 'start_time', 'end_time', 'guests_count'];
        $missing  = [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $input)) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw new HttpException('Missing required fields.', 400, ['missing' => $missing]);
        }

        return self::normalizeBookingData($input);
    }

    public static function validateUpdate(array $input): array {
        if ($input === []) {
            throw new HttpException('No fields provided for update.', 400);
        }

        return self::normalizeBookingData($input, true);
    }

    public static function validateGet(int $id): int {
        return self::positiveInt($id, 'id');
    }

    public static function validateStatus($status): string {
        if (!is_string($status) || $status === '') {
            throw new HttpException('Status is required. Allowed values: confirmed, cancelled.', 400);
        }

        if (!in_array($status, ['confirmed', 'cancelled'], true)) {
            throw new HttpException('Invalid status value. Allowed values: confirmed, cancelled.', 400);
        }

        return $status;
    }

    public static function normalizeBookingData(array $input, $partial = false): array {
        $data = [];

        if (!$partial || array_key_exists('table_id', $input)) {
            $data['table_id'] = self::positiveInt($input['table_id'] ?? null, 'table_id');
        }

        if (!$partial || array_key_exists('guest_name', $input)) {
            $data['guest_name'] = self::stringField($input['guest_name'] ?? null, 'guest_name', 100);
        }

        if (!$partial || array_key_exists('guest_phone', $input)) {
            $data['guest_phone'] = self::stringField($input['guest_phone'] ?? null, 'guest_phone', 20);
        }

        if (!$partial || array_key_exists('booking_date', $input)) {
            $data['booking_date'] = self::dateField($input['booking_date'] ?? null, 'booking_date');
        }

        if (!$partial || array_key_exists('start_time', $input)) {
            $data['start_time'] = self::timeField($input['start_time'] ?? null, 'start_time');
        }

        if (!$partial || array_key_exists('end_time', $input)) {
            $data['end_time'] = self::timeField($input['end_time'] ?? null, 'end_time');
        }

        if (!$partial || array_key_exists('guests_count', $input)) {
            $data['guests_count'] = self::positiveInt($input['guests_count'] ?? null, 'guests_count');
        }

        if (isset($data['booking_date'], $data['start_time'], $data['end_time'])) {
            self::validateDateTimeRange($data['booking_date'], $data['start_time'], $data['end_time']);
        }

        return $data;
    }

    public static function validateDateTimeRange($date, $startTime, $endTime): void {
        if (!self::isDate($date)) {
            throw new HttpException('Invalid booking_date format. Expected YYYY-MM-DD.', 400);
        }

        if (!self::isTime($startTime)) {
            throw new HttpException('Invalid start_time format. Expected HH:MM.', 400);
        }

        if (!self::isTime($endTime)) {
            throw new HttpException('Invalid end_time format. Expected HH:MM.', 400);
        }

        $start = strtotime($date . ' ' . $startTime);
        $end   = strtotime($date . ' ' . $endTime);

        if ($start === false || $end === false) {
            throw new HttpException('Invalid booking interval.', 400);
        }

        if ($start >= $end) {
            throw new HttpException('start_time must be earlier than end_time.', 400);
        }

        if ((int)(($end - $start) / 60) > self::MAX_DURATION_MINUTES) {
            throw new HttpException('Booking duration is too long.', 400, ['duration' => 'maximum_5_hours']);
        }
    }

    public static function stringField($value, $field, $maxLength): string {
        if (!is_string($value) || trim($value) === '') {
            throw new HttpException(sprintf('%s is required.', $field), 400, [$field => 'required']);
        }

        $value = trim($value);
        if (mb_strlen($value) > $maxLength) {
            throw new HttpException(sprintf('%s is too long.', $field), 400, [$field => 'max_' . $maxLength]);
        }

        return $value;
    }

    public static function positiveInt($value, $field): int {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value <= 0) {
            throw new HttpException(sprintf('%s must be a positive integer.', $field), 400, [$field => 'invalid_integer']);
        }

        return (int)$value;
    }

    public static function dateField($value, $field): string {
        if (!is_string($value) || !self::isDate($value)) {
            throw new HttpException(sprintf('%s must be a valid date in YYYY-MM-DD format.', $field), 400, [$field => 'invalid_date']);
        }

        return $value;
    }

    public static function timeField($value, $field): string {
        if (!is_string($value) || !self::isTime($value)) {
            throw new HttpException(sprintf('%s must be a valid time in HH:MM format.', $field), 400, [$field => 'invalid_time']);
        }

        return $value;
    }

    public static function isDate($value): bool {
        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $dateTime !== false && $dateTime->format('Y-m-d') === $value;
    }

    public static function isTime($value): bool {
        $dateTime = \DateTimeImmutable::createFromFormat('H:i', $value);
        return $dateTime !== false && $dateTime->format('H:i') === $value;
    }

}
