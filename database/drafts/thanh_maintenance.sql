
-- =====================================================
-- THANH: QUAN LY BAO HONG VA BAO TRI THIET BI
-- Database: ql_phong_thuc_hanh
-- =====================================================

USE ql_phong_thuc_hanh;


-- =====================================================
-- 1. BANG MAINTENANCE_REPORTS: BAO HONG / BAO TRI
-- =====================================================

CREATE TABLE IF NOT EXISTS maintenance_reports (
    report_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT UNSIGNED NOT NULL,
    reporter_id INT UNSIGNED NOT NULL,
    assigned_to INT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,

    status ENUM(
        'reported',
        'processing',
        'resolved',
        'cancelled'
    ) NOT NULL DEFAULT 'reported',

    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolution_note TEXT NULL,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_maintenance_equipment
        FOREIGN KEY (equipment_id)
        REFERENCES equipment(equipment_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_maintenance_reporter
        FOREIGN KEY (reporter_id)
        REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_maintenance_assignee
        FOREIGN KEY (assigned_to)
        REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    INDEX idx_maintenance_equipment (equipment_id),
    INDEX idx_maintenance_reporter (reporter_id),
    INDEX idx_maintenance_status (status)
) ENGINE=InnoDB;


-- =====================================================
-- 2. BANG MAINTENANCE_HISTORY: LICH SU BAO TRI
-- =====================================================

CREATE TABLE IF NOT EXISTS maintenance_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,

    old_status ENUM(
        'reported',
        'processing',
        'resolved',
        'cancelled'
    ) NULL,

    new_status ENUM(
        'reported',
        'processing',
        'resolved',
        'cancelled'
    ) NOT NULL,

    action_note TEXT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_maintenance_history_report
        FOREIGN KEY (report_id)
        REFERENCES maintenance_reports(report_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_maintenance_history_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    INDEX idx_maintenance_history_report (report_id)
) ENGINE=InnoDB;


-- =====================================================
-- 3. SELECT 1: DANH SACH TAT CA PHIEU BAO HONG
-- =====================================================

SELECT
    mr.report_id,
    e.equipment_code,
    e.equipment_name,
    mr.title,
    mr.description,
    mr.status,
    mr.reported_at
FROM maintenance_reports AS mr
JOIN equipment AS e
    ON mr.equipment_id = e.equipment_id
ORDER BY mr.reported_at DESC;


-- =====================================================
-- 4. SELECT 2: DANH SACH BAO HONG DANG XU LY
-- =====================================================

SELECT
    mr.report_id,
    e.equipment_code,
    e.equipment_name,
    mr.title,
    mr.description,
    mr.status,
    mr.reported_at
FROM maintenance_reports AS mr
JOIN equipment AS e
    ON mr.equipment_id = e.equipment_id
WHERE mr.status = 'processing'
ORDER BY mr.reported_at DESC;


-- =====================================================
-- 5. SELECT 3: LICH SU XU LY MOT PHIEU BAO HONG
-- Doi 1 thanh ma phieu can xem neu can
-- =====================================================

SELECT
    mh.history_id,
    mr.report_id,
    mr.title,
    mh.old_status,
    mh.new_status,
    u.full_name AS nguoi_thuc_hien,
    mh.action_note,
    mh.changed_at
FROM maintenance_history AS mh
JOIN maintenance_reports AS mr
    ON mh.report_id = mr.report_id
LEFT JOIN users AS u
    ON mh.user_id = u.user_id
WHERE mr.report_id = 1
ORDER BY mh.changed_at DESC;
