<?php declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use App\Http\HttpException;
use PDO;
use Throwable;

final class BookingsRepository {
    private PDO             $pdo;
    public TablesRepository $tablesRepository;

    public function __construct() {
        $this->pdo              = Connection::pdo();
        $this->tablesRepository = new TablesRepository();
    }

    /**
     * @throws Throwable
     */
    public function createBooking(array $data): array {
        try {
            $this->pdo->beginTransaction();

            $table = $this->tablesRepository->lockById($data['table_id']);
            if ($table === null || $table['is_active'] !== 1) {
                throw new HttpException('Table not found.', 404);
            }

            if ($data['guests_count'] > $table['capacity']) {
                throw new HttpException('Guest count exceeds table capacity.', 400);
            }

            $this->isTableIsAvailable($data['table_id'], $data['booking_date'], $data['start_time'], $data['end_time']);

            $id      = $this->create($data);
            $booking = $this->findById($id);

            $this->pdo->commit();

            return $booking ?? [];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * @throws Throwable
     */
    public function updateBooking($id, array $data): array {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->findById($id, true);
            if ($existing === null) {
                throw new HttpException('Booking not found.', 404);
            }

            $merged   = array_merge($existing, $data);
            $tableIds = array_values(array_unique([$existing['table_id'], $merged['table_id']]));
            foreach ($tableIds as $tableId) {
                $table = $this->tablesRepository->lockById($tableId);
                if ($table === null || $table['is_active'] !== 1) {
                    throw new HttpException('Table not found.', 404);
                }
            }

            $table = $this->tablesRepository->findById($merged['table_id']);
            if ($table === null) {
                throw new HttpException('Table not found.', 404);
            }

            if ($merged['guests_count'] > $table['capacity']) {
                throw new HttpException('Guest count exceeds table capacity.', 400);
            }

            $this->isTableIsAvailable(
                $merged['table_id'],
                $merged['booking_date'],
                $merged['start_time'],
                $merged['end_time'],
                $id
            );

            $this->update($id, [
                'table_id'     => $merged['table_id'],
                'guest_name'   => $merged['guest_name'],
                'guest_phone'  => $merged['guest_phone'],
                'booking_date' => $merged['booking_date'],
                'start_time'   => $merged['start_time'],
                'end_time'     => $merged['end_time'],
                'guests_count' => $merged['guests_count'],
                'status'       => $merged['status'] ?? 'confirmed',
            ]);

            $booking = $this->findById($id);

            $this->pdo->commit();

            return $booking ?? [];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function getBooking($id): array {
        $existing = $this->findById($id);
        if ($existing === null) {
            throw new HttpException('Booking not found.', 404);
        }
        return $existing;
    }

    public function cancelBooking($id): void {
        $existing = $this->findById($id);
        if ($existing === null) {
            throw new HttpException('Booking not found.', 404);
        }
        $this->cancel($id);
    }

    public function findById($id, $lock = false) {
        $sql = "
            SELECT id, table_id, guest_name, guest_phone, booking_date, start_time, end_time, guests_count, status, created_at
            FROM bookings
            WHERE id = :id
            LIMIT 1
        ";
        if ($lock) {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute(['id' => $id]);
        $booking = $statement->fetch();

        return $booking ?: null;
    }

    public function list(array $filters, int $page, int $limit): array {
        $page   = max(1, $page);
        $limit  = min(100, max(1, $limit));
        $offset = ($page - 1) * $limit;

        [$whereSql, $params] = $this->buildListWhere($filters);

        $countStatement = $this->pdo->prepare("SELECT COUNT(*) FROM bookings {$whereSql}");
        $countStatement->execute($params);
        $total = $countStatement->fetchColumn();

        $statement = $this->pdo->prepare(
            "SELECT id, table_id, guest_name, guest_phone, booking_date, start_time, end_time, guests_count, status, created_at
             FROM bookings
             {$whereSql}
             ORDER BY booking_date DESC, start_time DESC, id DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $name => $value) {
            $statement->bindValue(':' . $name, $value);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'pages' => max(1, (int)ceil($total / $limit)),
        ];
    }

    private function buildListWhere(array $filters): array {
        $conditions = [];
        $params     = [];

        if (!empty($filters['date'])) {
            $conditions[]           = 'booking_date = :booking_date';
            $params['booking_date'] = $filters['date'];
        }

        if (!empty($filters['status'])) {
            $conditions[]     = 'status = :status';
            $params['status'] = $filters['status'];
        }

        $whereSql = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);

        return [$whereSql, $params];
    }

    public function create(array $data): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO bookings (table_id, guest_name, guest_phone, booking_date, start_time, end_time, guests_count, status)
             VALUES (:table_id, :guest_name, :guest_phone, :booking_date, :start_time, :end_time, :guests_count, :status)'
        );
        $statement->execute([
            'table_id'     => $data['table_id'],
            'guest_name'   => $data['guest_name'],
            'guest_phone'  => $data['guest_phone'],
            'booking_date' => $data['booking_date'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'guests_count' => $data['guests_count'],
            'status'       => $data['status'] ?? 'confirmed',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update($id, array $data): bool {
        $statement = $this->pdo->prepare(
            'UPDATE bookings
             SET table_id = :table_id,
                 guest_name = :guest_name,
                 guest_phone = :guest_phone,
                 booking_date = :booking_date,
                 start_time = :start_time,
                 end_time = :end_time,
                 guests_count = :guests_count,
                 status = :status
             WHERE id = :id'
        );

        $statement->execute([
            'id'           => $id,
            'table_id'     => $data['table_id'],
            'guest_name'   => $data['guest_name'],
            'guest_phone'  => $data['guest_phone'],
            'booking_date' => $data['booking_date'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'guests_count' => $data['guests_count'],
            'status'       => $data['status'],
        ]);

        return $statement->rowCount() > 0;
    }

    public function cancel($id): bool {
        $statement = $this->pdo->prepare(
            "UPDATE bookings SET status = 'cancelled' WHERE id = :id AND status <> 'cancelled'"
        );
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function hasConflict($tableId, $date, $startTime, $endTime, $excludeBookingId = null): bool {
        $sql = "
            SELECT COUNT(*)
            FROM bookings
            WHERE table_id = :table_id
              AND booking_date = :booking_date
              AND status = 'confirmed'
              AND start_time < :end_time
              AND end_time > :start_time
        ";

        $params = [
            'table_id'     => $tableId,
            'booking_date' => $date,
            'start_time'   => $startTime,
            'end_time'     => $endTime,
        ];

        if ($excludeBookingId !== null) {
            $sql                  .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeBookingId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn() > 0;
    }

    private function isTableIsAvailable($tableId, $date, $startTime, $endTime, $excludeBookingId = null): void {
        if ($this->hasConflict($tableId, $date, $startTime, $endTime, $excludeBookingId)) {
            throw new HttpException('The table is already booked for the selected time.', 409);
        }
    }
}
