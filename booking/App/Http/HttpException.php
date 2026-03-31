<?php declare(strict_types=1);

namespace App\Http;

use RuntimeException;

class HttpException extends RuntimeException {
    private int   $status;
    private array $details;

    public function __construct(string $message, int $status = 400, array $details = []) {
        $this->status  = $status;
        $this->details = $details;
        parent::__construct($message, $status);
    }

    public function status(): int {
        return $this->status;
    }

    public function details(): array {
        return $this->details;
    }
}
