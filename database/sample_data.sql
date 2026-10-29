USE ql_phong_thuc_hanh;

-- =====================================================
-- 1. VAI TRO
-- =====================================================
INSERT INTO roles (role_name, description)
VALUES
('admin', 'Quan tri he thong'),
('manager', 'Quan ly phong va thiet bi'),
('teacher', 'Giang vien dat phong thuc hanh'),
('technician', 'Nhan vien ky thuat va bao tri')
ON DUPLICATE KEY UPDATE
    description = VALUES(description);

-- =====================================================
-- 2. TAI KHOAN DEMO
-- Mat khau chung: Admin@123456
-- Hash duoc tao bang PHP password_hash()
-- =====================================================
INSERT INTO users (full_name, email, password_hash, status)
VALUES
('Quan tri he thong', 'admin.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'active'),
('Nguyen Thu Trang', 'thutrang.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'active'),
('Phuong Thao', 'phuongthao.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'active'),
('Dieu Thanh', 'dieuthanh.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'active'),
('Ngo Nam Khanh', 'namkhanh.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'active'),
('Hoang Mai Lan', 'mailan.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'inactive')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    password_hash = VALUES(password_hash),
    status = VALUES(status);

-- =====================================================
-- 3. GAN VAI TRO
-- =====================================================
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.user_id, r.role_id
FROM users AS u
JOIN roles AS r
WHERE
    (u.email = 'admin.sample@example.com' AND r.role_name = 'admin')
    OR (u.email = 'thutrang.sample@example.com' AND r.role_name = 'teacher')
    OR (u.email = 'phuongthao.sample@example.com' AND r.role_name = 'manager')
    OR (u.email = 'dieuthanh.sample@example.com' AND r.role_name = 'technician')
    OR (u.email = 'namkhanh.sample@example.com' AND r.role_name = 'admin')
    OR (u.email = 'mailan.sample@example.com' AND r.role_name = 'teacher');

-- =====================================================
-- 4. TOA NHA
-- =====================================================
INSERT INTO buildings (building_name, address, description, status)
SELECT
    danh_sach_toa_nha.building_name,
    danh_sach_toa_nha.address,
    danh_sach_toa_nha.description,
    'active'
FROM (
    SELECT
        'Toa nha A' AS building_name,
        'Co so 1 - Ha Noi' AS address,
        'Khu phong thuc hanh may tinh' AS description
    UNION ALL SELECT
        'Toa nha B',
        'Co so 1 - Ha Noi',
        'Khu thuc hanh mang va phan cung'
    UNION ALL SELECT
        'Toa nha C',
        'Co so 2 - Ha Noi',
        'Khu thuc hanh da nang'
) AS danh_sach_toa_nha
WHERE NOT EXISTS (
    SELECT 1
    FROM buildings AS b
    WHERE b.building_name = danh_sach_toa_nha.building_name
);

-- =====================================================
-- 5. PHONG THUC HANH
-- =====================================================
INSERT INTO rooms (
    building_id, room_code, room_name, capacity,
    location, description, status
)
SELECT
    b.building_id,
    danh_sach_phong.room_code,
    danh_sach_phong.room_name,
    danh_sach_phong.capacity,
    danh_sach_phong.location,
    danh_sach_phong.description,
    danh_sach_phong.status
FROM (
    SELECT
        'A101' AS room_code,
        'Phong may tinh A101' AS room_name,
        40 AS capacity,
        'Tang 1' AS location,
        'Phong thuc hanh lap trinh' AS description,
        'active' AS status,
        'Toa nha A' AS building_name
    UNION ALL SELECT
        'A202', 'Phong thuc hanh A202', 30, 'Tang 2',
        'Phong thuc hanh co may chieu', 'active', 'Toa nha A'
    UNION ALL SELECT
        'B105', 'Phong mang B105', 25, 'Tang 1',
        'Phong thuc hanh mang may tinh', 'active', 'Toa nha B'
    UNION ALL SELECT
        'B210', 'Phong phan cung B210', 20, 'Tang 2',
        'Phong lap rap va sua chua may tinh', 'active', 'Toa nha B'
    UNION ALL SELECT
        'C001', 'Phong da nang C001', 50, 'Tang 1',
        'Phong hoc va hoi thao', 'active', 'Toa nha C'
    UNION ALL SELECT
        'C002', 'Phong du phong C002', 15, 'Tang 1',
        'Phong nho danh cho nhom hoc tap', 'inactive', 'Toa nha C'
) AS danh_sach_phong
JOIN buildings AS b
    ON b.building_name = danh_sach_phong.building_name
WHERE NOT EXISTS (
    SELECT 1
    FROM rooms AS r
    WHERE r.room_code = danh_sach_phong.room_code
);

-- =====================================================
-- 6. LOAI THIET BI
-- =====================================================
INSERT INTO equipment_types (type_name, description)
VALUES
('May tinh', 'May tinh de ban hoac may tram'),
('May chieu', 'Thiet bi trinh chieu'),
('Thiet bi mang', 'Router, switch va thiet bi mang'),
('Thiet bi ngoai vi', 'Ban phim, chuot va thiet bi ngoai vi'),
('Thiet bi dien', 'Day nguon va thiet bi dien')
ON DUPLICATE KEY UPDATE
    description = VALUES(description);

-- =====================================================
-- 7. THIET BI
-- =====================================================
INSERT INTO equipment (
    room_id, equipment_type_id, equipment_code, equipment_name,
    brand, purchase_date, status, description
)
SELECT
    r.room_id,
    et.equipment_type_id,
    danh_sach_thiet_bi.equipment_code,
    danh_sach_thiet_bi.equipment_name,
    danh_sach_thiet_bi.brand,
    danh_sach_thiet_bi.purchase_date,
    danh_sach_thiet_bi.status,
    danh_sach_thiet_bi.description
FROM (
    SELECT
        'PC-A101-01' AS equipment_code,
        'May tinh thuc hanh 01' AS equipment_name,
        'Dell' AS brand,
        '2023-08-15' AS purchase_date,
        'available' AS status,
        'May tram cho sinh vien' AS description,
        'A101' AS room_code,
        'May tinh' AS type_name
    UNION ALL SELECT
        'PC-A101-02', 'May tinh thuc hanh 02', 'HP',
        '2023-08-15', 'available', 'May tram cho sinh vien',
        'A101', 'May tinh'
    UNION ALL SELECT
        'PC-A101-03', 'May tinh thuc hanh 03', 'Dell',
        '2022-09-01', 'broken', 'Khong khoi dong duoc',
        'A101', 'May tinh'
    UNION ALL SELECT
        'PR-A202-01', 'May chieu A202', 'Epson',
        '2022-05-20', 'available', 'May chieu chinh cua phong',
        'A202', 'May chieu'
    UNION ALL SELECT
        'PC-A202-01', 'May tinh giang vien A202', 'Lenovo',
        '2024-01-10', 'available', 'May tinh dieu khien may chieu',
        'A202', 'May tinh'
    UNION ALL SELECT
        'SW-B105-01', 'Switch mang B105', 'Cisco',
        '2021-11-12', 'maintenance', 'Dang kiem tra cong mang',
        'B105', 'Thiet bi mang'
    UNION ALL SELECT
        'RT-B105-01', 'Router thuc hanh B105', 'TP-Link',
        '2023-03-10', 'available', 'Router phuc vu bai thuc hanh',
        'B105', 'Thiet bi mang'
    UNION ALL SELECT
        'PC-B210-01', 'May tinh ky thuat B210', 'Asus',
        '2022-02-18', 'available', 'May tinh dung cho ky thuat vien',
        'B210', 'May tinh'
    UNION ALL SELECT
        'KB-A101-01', 'Bo ban phim chuot A101', 'Logitech',
        '2023-08-15', 'available', 'Bo ngoai vi may tinh',
        'A101', 'Thiet bi ngoai vi'
    UNION ALL SELECT
        'PR-C001-01', 'May chieu C001', 'BenQ',
        '2020-07-30', 'retired', 'Da ngung su dung',
        'C001', 'May chieu'
) AS danh_sach_thiet_bi
JOIN rooms AS r
    ON r.room_code = danh_sach_thiet_bi.room_code
JOIN equipment_types AS et
    ON et.type_name = danh_sach_thiet_bi.type_name
WHERE NOT EXISTS (
    SELECT 1
    FROM equipment AS e
    WHERE e.equipment_code = danh_sach_thiet_bi.equipment_code
);

-- =====================================================
-- 8. YEU CAU DAT PHONG
-- =====================================================
INSERT INTO bookings (
    user_id, room_id, title, purpose,
    start_time, end_time, status, note
)
SELECT
    u.user_id,
    r.room_id,
    danh_sach_dat_phong.title,
    danh_sach_dat_phong.purpose,
    danh_sach_dat_phong.start_time,
    danh_sach_dat_phong.end_time,
    danh_sach_dat_phong.status,
    danh_sach_dat_phong.note
FROM (
    SELECT
        'thutrang.sample@example.com' AS email,
        'A101' AS room_code,
        'Thuc hanh CSDL - Nhom 1' AS title,
        'Bai thuc hanh truy van SQL' AS purpose,
        '2026-10-29 08:00:00' AS start_time,
        '2026-10-29 10:00:00' AS end_time,
        'approved' AS status,
        'Da duoc duyet' AS note
    UNION ALL SELECT
        'mailan.sample@example.com', 'A101',
        'On tap lap trinh', 'On tap lap trinh C#',
        '2026-10-29 10:00:00', '2026-10-29 12:00:00',
        'pending', 'Cho quan ly xac nhan'
    UNION ALL SELECT
        'thutrang.sample@example.com', 'B105',
        'Thuc hanh mang may tinh', 'Cau hinh router va switch',
        '2026-10-30 13:30:00', '2026-10-30 16:30:00',
        'pending', 'Can chuan bi thiet bi mang'
    UNION ALL SELECT
        'mailan.sample@example.com', 'A202',
        'Bao cao do an', 'Trinh bay tien do do an',
        '2026-10-30 08:00:00', '2026-10-30 09:30:00',
        'rejected', 'Phong da co lich su dung'
    UNION ALL SELECT
        'thutrang.sample@example.com', 'C001',
        'Sinh hoat hoc thuat', 'Sinh hoat cau lac bo hoc thuat',
        '2026-10-31 14:00:00', '2026-10-31 16:00:00',
        'cancelled', 'Nguoi dat huy lich'
    UNION ALL SELECT
        'mailan.sample@example.com', 'A101',
        'Thuc hanh lap trinh Python', 'Thuc hanh vong lap va ham',
        '2026-11-01 08:00:00', '2026-11-01 11:00:00',
        'approved', 'Da duyet'
    UNION ALL SELECT
        'thutrang.sample@example.com', 'B210',
        'Lap rap may tinh', 'Thuc hanh lap rap phan cung',
        '2026-11-01 13:00:00', '2026-11-01 15:00:00',
        'pending', 'Cho duyet'
    UNION ALL SELECT
        'mailan.sample@example.com', 'A202',
        'Thuc hanh Excel', 'Xu ly bang tinh',
        '2026-11-02 09:00:00', '2026-11-02 11:00:00',
        'approved', 'Da duyet'
) AS danh_sach_dat_phong
JOIN users AS u
    ON u.email = danh_sach_dat_phong.email
JOIN rooms AS r
    ON r.room_code = danh_sach_dat_phong.room_code
WHERE NOT EXISTS (
    SELECT 1
    FROM bookings AS b
    WHERE b.user_id = u.user_id
      AND b.room_id = r.room_id
      AND b.title = danh_sach_dat_phong.title
      AND b.start_time = danh_sach_dat_phong.start_time
);

-- =====================================================
-- 9. LICH SU DUYET DAT PHONG
-- Phuong Thao la quan ly duyet phong
-- =====================================================
INSERT INTO booking_approvals (
    booking_id, reviewer_id, action, comment, reviewed_at
)
SELECT
    b.booking_id,
    reviewer.user_id,
    danh_sach_duyet.action,
    danh_sach_duyet.comment,
    danh_sach_duyet.reviewed_at
FROM (
    SELECT
        'Thuc hanh CSDL - Nhom 1' AS title,
        '2026-10-29 08:00:00' AS start_time,
        'approved' AS action,
        'Phong va thoi gian phu hop' AS comment,
        '2026-10-27 09:00:00' AS reviewed_at
    UNION ALL SELECT
        'Bao cao do an', '2026-10-30 08:00:00',
        'rejected', 'Trung lich su dung phong', '2026-10-27 10:00:00'
    UNION ALL SELECT
        'Thuc hanh lap trinh Python', '2026-11-01 08:00:00',
        'approved', 'Da xac nhan lich', '2026-10-27 10:30:00'
    UNION ALL SELECT
        'Thuc hanh Excel', '2026-11-02 09:00:00',
        'approved', 'Dong y su dung phong', '2026-10-27 11:00:00'
) AS danh_sach_duyet
JOIN bookings AS b
    ON b.title = danh_sach_duyet.title
   AND b.start_time = danh_sach_duyet.start_time
JOIN users AS reviewer
    ON reviewer.email = 'phuongthao.sample@example.com'
WHERE NOT EXISTS (
    SELECT 1
    FROM booking_approvals AS a
    WHERE a.booking_id = b.booking_id
      AND a.reviewer_id = reviewer.user_id
      AND a.action = danh_sach_duyet.action
);

-- =====================================================
-- 10. LICH SU THAY DOI THIET BI
-- =====================================================
INSERT INTO equipment_history (
    equipment_id, user_id, old_status, new_status,
    change_note, changed_at
)
SELECT
    e.equipment_id,
    u.user_id,
    danh_sach_thay_doi.old_status,
    danh_sach_thay_doi.new_status,
    danh_sach_thay_doi.change_note,
    danh_sach_thay_doi.changed_at
FROM (
    SELECT
        'PC-A101-03' AS equipment_code,
        'available' AS old_status,
        'broken' AS new_status,
        'Ghi nhan may khong khoi dong' AS change_note,
        '2026-10-18 09:00:00' AS changed_at,
        'dieuthanh.sample@example.com' AS email
    UNION ALL SELECT
        'SW-B105-01', 'available', 'maintenance',
        'Chuyen thiet bi sang trang thai bao tri',
        '2026-10-19 14:00:00', 'dieuthanh.sample@example.com'
    UNION ALL SELECT
        'PR-C001-01', 'available', 'retired',
        'Thiet bi het han su dung',
        '2026-09-30 10:00:00', 'phuongthao.sample@example.com'
) AS danh_sach_thay_doi
JOIN equipment AS e
    ON e.equipment_code = danh_sach_thay_doi.equipment_code
JOIN users AS u
    ON u.email = danh_sach_thay_doi.email
WHERE NOT EXISTS (
    SELECT 1
    FROM equipment_history AS h
    WHERE h.equipment_id = e.equipment_id
      AND h.change_note = danh_sach_thay_doi.change_note
);

-- =====================================================
-- 11. BAO HONG / BAO TRI
-- =====================================================
INSERT INTO maintenance_reports (
    equipment_id, reporter_id, assigned_to, title, description,
    status, reported_at, resolved_at, resolution_note
)
SELECT
    e.equipment_id,
    reporter.user_id,
    technician.user_id,
    danh_sach_bao_tri.title,
    danh_sach_bao_tri.description,
    danh_sach_bao_tri.status,
    danh_sach_bao_tri.reported_at,
    danh_sach_bao_tri.resolved_at,
    danh_sach_bao_tri.resolution_note
FROM (
    SELECT
        'PC-A101-03' AS equipment_code,
        'thutrang.sample@example.com' AS reporter_email,
        'dieuthanh.sample@example.com' AS technician_email,
        'May tinh khong khoi dong' AS title,
        'Nhan nut nguon nhung may khong len.' AS description,
        'processing' AS status,
        '2026-10-18 09:05:00' AS reported_at,
        NULL AS resolved_at,
        NULL AS resolution_note
    UNION ALL SELECT
        'SW-B105-01', 'mailan.sample@example.com',
        'dieuthanh.sample@example.com', 'Kiem tra switch mang',
        'Mot so cong mang khong co ket noi.', 'processing',
        '2026-10-19 14:05:00', NULL, NULL
    UNION ALL SELECT
        'PR-C001-01', 'phuongthao.sample@example.com',
        'dieuthanh.sample@example.com', 'May chieu cu hong',
        'May chieu khong con dap ung nhu cau su dung.', 'resolved',
        '2026-09-25 08:00:00', '2026-09-30 10:00:00',
        'De xuat thanh ly thiet bi.'
    UNION ALL SELECT
        'PC-A101-02', 'namkhanh.sample@example.com',
        'dieuthanh.sample@example.com', 'Bao duong dinh ky may tinh',
        'Ve sinh va kiem tra may tram.', 'reported',
        '2026-10-22 15:30:00', NULL, NULL
) AS danh_sach_bao_tri
JOIN equipment AS e
    ON e.equipment_code = danh_sach_bao_tri.equipment_code
JOIN users AS reporter
    ON reporter.email = danh_sach_bao_tri.reporter_email
LEFT JOIN users AS technician
    ON technician.email = danh_sach_bao_tri.technician_email
WHERE NOT EXISTS (
    SELECT 1
    FROM maintenance_reports AS m
    WHERE m.equipment_id = e.equipment_id
      AND m.title = danh_sach_bao_tri.title
);

-- =====================================================
-- 12. LICH SU XU LY BAO TRI
-- =====================================================
INSERT INTO maintenance_history (
    report_id, user_id, old_status, new_status,
    action_note, changed_at
)
SELECT
    m.report_id,
    u.user_id,
    danh_sach_lich_su_bao_tri.old_status,
    danh_sach_lich_su_bao_tri.new_status,
    danh_sach_lich_su_bao_tri.action_note,
    danh_sach_lich_su_bao_tri.changed_at
FROM (
    SELECT
        'May tinh khong khoi dong' AS title,
        'reported' AS old_status,
        'processing' AS new_status,
        'Ky thuat vien da tiep nhan bao hong' AS action_note,
        '2026-10-18 10:00:00' AS changed_at,
        'dieuthanh.sample@example.com' AS email
    UNION ALL SELECT
        'Kiem tra switch mang', 'reported', 'processing',
        'Dang kiem tra cac cong ket noi',
        '2026-10-19 15:00:00', 'dieuthanh.sample@example.com'
    UNION ALL SELECT
        'May chieu cu hong', 'processing', 'resolved',
        'Da ket thuc kiem tra va de xuat thanh ly',
        '2026-09-30 10:00:00', 'dieuthanh.sample@example.com'
) AS danh_sach_lich_su_bao_tri
JOIN maintenance_reports AS m
    ON m.title = danh_sach_lich_su_bao_tri.title
JOIN users AS u
    ON u.email = danh_sach_lich_su_bao_tri.email
WHERE NOT EXISTS (
    SELECT 1
    FROM maintenance_history AS h
    WHERE h.report_id = m.report_id
      AND h.action_note = danh_sach_lich_su_bao_tri.action_note
);

-- =====================================================
-- 13. KIEM TRA SO LUONG DU LIEU SAU KHI CHAY
-- =====================================================
SELECT 'roles' AS bang, COUNT(*) AS so_ban_ghi FROM roles
UNION ALL SELECT 'users', COUNT(*) FROM users
UNION ALL SELECT 'user_roles', COUNT(*) FROM user_roles
UNION ALL SELECT 'buildings', COUNT(*) FROM buildings
UNION ALL SELECT 'rooms', COUNT(*) FROM rooms
UNION ALL SELECT 'equipment_types', COUNT(*) FROM equipment_types
UNION ALL SELECT 'equipment', COUNT(*) FROM equipment
UNION ALL SELECT 'bookings', COUNT(*) FROM bookings
UNION ALL SELECT 'booking_approvals', COUNT(*) FROM booking_approvals
UNION ALL SELECT 'equipment_history', COUNT(*) FROM equipment_history
UNION ALL SELECT 'maintenance_reports', COUNT(*) FROM maintenance_reports
UNION ALL SELECT 'maintenance_history', COUNT(*) FROM maintenance_history;

-- =====================================================
-- 14. KIEM TRA TAI KHOAN VA VAI TRO
-- =====================================================
SELECT
    u.user_id,
    u.full_name,
    u.email,
    u.status,
    r.role_name
FROM users AS u
LEFT JOIN user_roles AS ur
    ON ur.user_id = u.user_id
LEFT JOIN roles AS r
    ON r.role_id = ur.role_id
ORDER BY u.user_id;