<?php declare(strict_types=1);

namespace App\Http;

final class Response {
    public static function json($payload, $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    public static function noContent(): void {
        http_response_code(204);
        exit;
    }

    public static function error($message, $status = 400, array $details = []): void {
        $payload = ['error' => $message];

        if ($details !== []) {
            $payload['details'] = $details;
        }

        self::json($payload, $status);
    }
}
