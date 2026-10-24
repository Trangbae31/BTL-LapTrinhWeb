CREATE DATABASE IF NOT EXISTS ql_phong_thuc_hanh
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ql_phong_thuc_hanh;

-- =====================================================
-- 1. ROLES: DANH SACH VAI TRO
-- =====================================================
CREATE TABLE IF NOT EXISTS roles (
    role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 2. USERS: TAI KHOAN NGUOI DUNG
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 3. USER_ROLES: GAN VAI TRO CHO TAI KHOAN
-- =====================================================
CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role
        FOREIGN KEY (role_id) REFERENCES roles(role_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 4. BUILDINGS: TOA NHA / KHU NHA
-- =====================================================
CREATE TABLE IF NOT EXISTS buildings (
    building_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    building_name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 5. ROOMS: PHONG THUC HANH
-- =====================================================
CREATE TABLE IF NOT EXISTS rooms (
    room_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    building_id INT UNSIGNED,
    room_code VARCHAR(30) NOT NULL UNIQUE,
    room_name VARCHAR(100) NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 1,
    location VARCHAR(255),
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rooms_building
        FOREIGN KEY (building_id) REFERENCES buildings(building_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 6. EQUIPMENT_TYPES: LOAI THIET BI
-- =====================================================
CREATE TABLE IF NOT EXISTS equipment_types (
    equipment_type_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 7. EQUIPMENT: THIET BI
-- =====================================================
CREATE TABLE IF NOT EXISTS equipment (
    equipment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NOT NULL,
    equipment_type_id INT UNSIGNED NOT NULL,
    equipment_code VARCHAR(50) NOT NULL UNIQUE,
    equipment_name VARCHAR(150) NOT NULL,
    brand VARCHAR(100),
    purchase_date DATE,
    status ENUM(
        'available',
        'maintenance',
        'broken',
        'retired'
    ) NOT NULL DEFAULT 'available',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_room
        FOREIGN KEY (room_id) REFERENCES rooms(room_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_equipment_type
        FOREIGN KEY (equipment_type_id)
        REFERENCES equipment_types(equipment_type_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_equipment_room (room_id),
    INDEX idx_equipment_type (equipment_type_id),
    INDEX idx_equipment_status (status)
) ENGINE=InnoDB;

-- =====================================================
-- 8. BOOKINGS: YEU CAU DAT PHONG
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
-- 9. BOOKING_APPROVALS: LICH SU DUYET DAT PHONG
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
-- 10. EQUIPMENT_HISTORY: LICH SU THIET BI
-- =====================================================
CREATE TABLE IF NOT EXISTS equipment_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED,
    old_status ENUM(
        'available', 'maintenance', 'broken', 'retired'
    ),
    new_status ENUM(
        'available', 'maintenance', 'broken', 'retired'
    ) NOT NULL,
    change_note TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_history_equipment
        FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_equipment_history_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_equipment_history_equipment (equipment_id)
) ENGINE=InnoDB;

-- =====================================================
-- 11. MAINTENANCE_REPORTS: BAO HONG / BAO TRI
-- =====================================================
CREATE TABLE IF NOT EXISTS maintenance_reports (
    report_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT UNSIGNED NOT NULL,
    reporter_id INT UNSIGNED NOT NULL,
    assigned_to INT UNSIGNED,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status ENUM(
        'reported',
        'processing',
        'resolved',
        'cancelled'
    ) NOT NULL DEFAULT 'reported',
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME,
    resolution_note TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_maintenance_equipment
        FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_maintenance_reporter
        FOREIGN KEY (reporter_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_maintenance_assignee
        FOREIGN KEY (assigned_to) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_maintenance_equipment (equipment_id),
    INDEX idx_maintenance_reporter (reporter_id),
    INDEX idx_maintenance_status (status)
) ENGINE=InnoDB;

-- =====================================================
-- 12. MAINTENANCE_HISTORY: LICH SU XU LY BAO TRI
-- =====================================================
CREATE TABLE IF NOT EXISTS maintenance_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED,
    old_status ENUM(
        'reported', 'processing', 'resolved', 'cancelled'
    ),
    new_status ENUM(
        'reported', 'processing', 'resolved', 'cancelled'
    ) NOT NULL,
    action_note TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_maintenance_history_report
        FOREIGN KEY (report_id)
        REFERENCES maintenance_reports(report_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_maintenance_history_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_maintenance_history_report (report_id)
) ENGINE=InnoDB;


-- Kiem tra sau khi tao bang (co the chay rieng):
-- SHOW TABLES;
-- SHOW CREATE TABLE bookings;
-- SHOW CREATE TABLE booking_approvals;
