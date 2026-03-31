<?php declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use PDO;


final class TablesRepository {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Connection::pdo();
    }

    public function findById($id, $lock = false): ?array {
        $sql = 'SELECT id, table_number, capacity, is_active FROM tables WHERE id = :id LIMIT 1';
        if ($lock) {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute(['id' => $id]);
        $table = $statement->fetch();

        return $table ?: null;
    }

    public function findAvailableByDateTime($date, $startTime, $endTime, $guestsCount = null): array {
        $params = [
            'booking_date' => $date,
            'start_time'   => $startTime,
            'end_time'     => $endTime,
        ];

        $capacityFilter = '';
        if ($guestsCount !== null) {
            $capacityFilter         = ' AND t.capacity >= :guests_count';
            $params['guests_count'] = $guestsCount;
        }

        $sql = "
            SELECT t.id, t.table_number, t.capacity
            FROM tables t
            WHERE t.is_active = 1
            {$capacityFilter}
            AND t.id NOT IN (
                SELECT b.table_id
                FROM bookings b
                WHERE b.booking_date = :booking_date
                  AND b.status = 'confirmed'
                  AND b.start_time < :end_time
                  AND b.end_time > :start_time
            )
            ORDER BY t.table_number ASC
        ";

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function lockById($id): ?array {
        $statement = $this->pdo->prepare('SELECT id, table_number, capacity, is_active FROM tables WHERE id = :id LIMIT 1 FOR UPDATE');
        $statement->execute(['id' => $id]);
        $table = $statement->fetch();
        return $table ?: null;
    }
}
