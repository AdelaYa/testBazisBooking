CREATE TABLE IF NOT EXISTS tables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_number INT UNSIGNED NOT NULL UNIQUE,
    capacity INT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_id INT UNSIGNED NOT NULL,
    guest_name VARCHAR(100) NOT NULL,
    guest_phone VARCHAR(20) NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    guests_count INT UNSIGNED NOT NULL,
    status ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_table
        FOREIGN KEY (table_id) REFERENCES tables (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    INDEX idx_bookings_date_time (booking_date, start_time, end_time),
    INDEX idx_bookings_status_date_time (status, booking_date, start_time, id),
    INDEX idx_bookings_table_status (table_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tables (table_number, capacity, is_active) VALUES
    (1, 2, 1),
    (2, 4, 1),
    (3, 4, 1),
    (4, 6, 1),
    (5, 8, 1)
ON DUPLICATE KEY UPDATE
    capacity = VALUES(capacity),
    is_active = VALUES(is_active);
