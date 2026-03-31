<?php declare(strict_types=1);

namespace App\Http;

final class Request {
    private string $method;
    private string $path;
    private array  $query;
    private array  $body;

    public function __construct(string $method, string $path, array $query, array $body) {
        $this->method  = $method;
        $this->path    = $path;
        $this->query   = $query;
        $this->body    = $body;
    }

    public static function fromServer(): Request {
        $method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path    = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $body    = [];
        $headers = getallheaders();

        if (isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json') {
            $rawBody        = file_get_contents('php://input');
            $rawBodyDecoded = json_decode($rawBody, true);
            if ($rawBodyDecoded) {
                $body = $rawBodyDecoded;
            }
        }

        return new Request($method, $path, $_GET, $body);
    }

    public function method(): string {
        return $this->method;
    }

    public function path(): string {
        return $this->path;
    }

    public function query($key, $default = null) {
        return $this->query[$key] ?? $default;
    }

    public function json(): array {
        return $this->body;
    }
}
