USE ql_phong_thuc_hanh;
-- =====================================================
-- 1. BOOKINGS: YEU CAU DAT PHONG
-- =====================================================
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    purpose TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM(
        'pending',
        'approved',
        'rejected',
        'cancelled'
    ) NOT NULL DEFAULT 'pending',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_bookings_room
        FOREIGN KEY (room_id) REFERENCES rooms(room_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_bookings_time
        CHECK (start_time < end_time),

    INDEX idx_bookings_user (user_id),
    INDEX idx_bookings_room_time (room_id, start_time, end_time),
    INDEX idx_bookings_status (status)
) ENGINE=InnoDB;

-- =====================================================
-- 2. BOOKING_APPROVALS: LICH SU DUYET DAT PHONG
-- =====================================================
CREATE TABLE IF NOT EXISTS booking_approvals (
    approval_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    action ENUM('approved', 'rejected') NOT NULL,
    comment TEXT,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_booking_approvals_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_booking_approvals_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    INDEX idx_approval_booking (booking_id),
    INDEX idx_approval_reviewer (reviewer_id)
) ENGINE=InnoDB;

-- =====================================================
-- 3. TRUY VAN LICH PHONG VA BOOKING
-- =====================================================

USE ql_phong_thuc_hanh;

-- 3.1. Danh sach booking kem ten nguoi dat va phong
SELECT
    b.booking_id,
    u.full_name AS nguoi_dat,
    r.room_code,
    r.room_name,
    b.title,
    b.purpose,
    b.start_time,
    b.end_time,
    b.status
FROM bookings b
JOIN users u ON b.user_id = u.user_id
JOIN rooms r ON b.room_id = r.room_id
ORDER BY b.start_time ASC;

-- 3.2. Cac booking dang cho duyet
SELECT
    b.booking_id,
    u.full_name AS nguoi_dat,
    r.room_name,
    b.title,
    b.start_time,
    b.end_time
FROM bookings b
JOIN users u ON b.user_id = u.user_id
JOIN rooms r ON b.room_id = r.room_id
WHERE b.status = 'pending'
ORDER BY b.start_time ASC;

-- 3.3. Booking trong mot ngay cu the
SELECT
    b.booking_id,
    r.room_name,
    b.title,
    b.start_time,
    b.end_time,
    b.status
FROM bookings b
JOIN rooms r ON b.room_id = r.room_id
WHERE b.start_time < '2026-10-27 23:59:59'
  AND b.end_time > '2026-10-27 00:00:00'
  AND b.status IN ('pending', 'approved')
ORDER BY b.start_time ASC;