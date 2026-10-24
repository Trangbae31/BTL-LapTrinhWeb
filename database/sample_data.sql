USE ql_phong_thuc_hanh;

-- 1. VAI TRO
INSERT INTO roles (role_name, description)
VALUES
('admin', 'Quan tri he thong'),
('manager', 'Quan ly phong va thiet bi'),
('teacher', 'Giang vien dat phong thuc hanh'),
('technician', 'Nhan vien ky thuat va bao tri')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- 2. TAI KHOAN DEMO
-- Mat khau_hash chi la chuoi demo de phuc vu test CSDL; neu can dang nhap,
-- hay tao hash bang PHP password_hash() va cap nhat vao cot password_hash.
INSERT INTO users (full_name, email, password_hash, status)
VALUES
('Quan tri he thong', 'admin.sample@example.com', 'DEMO_HASH_REPLACE_FOR_LOGIN', 'active'),
('Nguyen Minh Anh', 'minhanh.sample@example.com', 'DEMO_HASH_REPLACE_FOR_LOGIN', 'active'),
('Tran Quoc Bao', 'quocbao.sample@example.com', 'DEMO_HASH_REPLACE_FOR_LOGIN', 'active'),
('Le Thu Ha', 'thuha.sample@example.com', 'DEMO_HASH_REPLACE_FOR_LOGIN', 'active'),
('Pham Duc Long', 'duclong.sample@example.com', 'DEMO_HASH_REPLACE_FOR_LOGIN', 'active'),
('Hoang Mai Lan', 'mailan.sample@example.com', 'DEMO_HASH_REPLACE_FOR_LOGIN', 'active'),
('Vu Tuan Kiet', 'tuankiet.sample@example.com', 'DEMO_HASH_REPLACE_FOR_LOGIN', 'inactive')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), status = VALUES(status);

-- 3. GAN VAI TRO
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.user_id, r.role_id
FROM users u JOIN roles r
WHERE (u.email = 'admin.sample@example.com' AND r.role_name = 'admin')
   OR (u.email = 'minhanh.sample@example.com' AND r.role_name = 'teacher')
   OR (u.email = 'quocbao.sample@example.com' AND r.role_name = 'teacher')
   OR (u.email = 'thuha.sample@example.com' AND r.role_name = 'manager')
   OR (u.email = 'duclong.sample@example.com' AND r.role_name = 'technician')
   OR (u.email = 'mailan.sample@example.com' AND r.role_name = 'teacher')
   OR (u.email = 'tuankiet.sample@example.com' AND r.role_name = 'teacher');

-- 4. TOA NHA (khong co UNIQUE building_name trong schema, nen kiem tra truoc)
INSERT INTO buildings (building_name, address, description, status)
SELECT v.building_name, v.address, v.description, 'active'
FROM (
    SELECT 'Toa nha A' AS building_name, 'Co so 1 - Ha Noi' AS address, 'Khu phong thuc hanh may tinh' AS description
    UNION ALL SELECT 'Toa nha B', 'Co so 1 - Ha Noi', 'Khu thuc hanh mang va phan cung'
    UNION ALL SELECT 'Toa nha C', 'Co so 2 - Ha Noi', 'Khu thuc hanh da nang'
) v
WHERE NOT EXISTS (
    SELECT 1 FROM buildings b WHERE b.building_name = v.building_name
);

-- 5. PHONG THUC HANH
INSERT INTO rooms (building_id, room_code, room_name, capacity, location, description, status)
SELECT b.building_id, v.room_code, v.room_name, v.capacity, v.location, v.description, v.status
FROM (
    SELECT 'A101' room_code, 'Phong may tinh A101' room_name, 40 capacity, 'Tang 1' location, 'Phong thuc hanh lap trinh' description, 'active' status, 'Toa nha A' building_name
    UNION ALL SELECT 'A202', 'Phong thuc hanh A202', 30, 'Tang 2', 'Phong thuc hanh co may chieu', 'active', 'Toa nha A'
    UNION ALL SELECT 'B105', 'Phong mang B105', 25, 'Tang 1', 'Phong thuc hanh mang may tinh', 'active', 'Toa nha B'
    UNION ALL SELECT 'B210', 'Phong phan cung B210', 20, 'Tang 2', 'Phong lap rap va sua chua may tinh', 'active', 'Toa nha B'
    UNION ALL SELECT 'C001', 'Phong da nang C001', 50, 'Tang 1', 'Phong hoc va hoi thao', 'active', 'Toa nha C'
    UNION ALL SELECT 'C002', 'Phong du phong C002', 15, 'Tang 1', 'Phong nho danh cho nhom hoc tap', 'inactive', 'Toa nha C'
) v JOIN buildings b ON b.building_name = v.building_name
WHERE NOT EXISTS (SELECT 1 FROM rooms r WHERE r.room_code = v.room_code);

-- 6. LOAI THIET BI
INSERT INTO equipment_types (type_name, description)
VALUES
('May tinh', 'May tinh de ban hoac may tram'),
('May chieu', 'Thiet bi trinh chieu'),
('Thiet bi mang', 'Router, switch va thiet bi mang'),
('Thiet bi ngoai vi', 'Ban phim, chuot va thiet bi ngoai vi'),
('Thiet bi dien', 'Day nguon va thiet bi dien')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- 7. THIET BI
INSERT INTO equipment (room_id, equipment_type_id, equipment_code, equipment_name, brand, purchase_date, status, description)
SELECT r.room_id, et.equipment_type_id, v.equipment_code, v.equipment_name, v.brand, v.purchase_date, v.status, v.description
FROM (
    SELECT 'PC-A101-01' equipment_code, 'May tinh thuc hanh 01' equipment_name, 'Dell' brand, '2023-08-15' purchase_date, 'available' status, 'May tram cho sinh vien' description, 'A101' room_code, 'May tinh' type_name
    UNION ALL SELECT 'PC-A101-02', 'May tinh thuc hanh 02', 'HP', '2023-08-15', 'available', 'May tram cho sinh vien', 'A101', 'May tinh'
    UNION ALL SELECT 'PC-A101-03', 'May tinh thuc hanh 03', 'Dell', '2022-09-01', 'broken', 'Khong khoi dong duoc', 'A101', 'May tinh'
    UNION ALL SELECT 'PR-A202-01', 'May chieu A202', 'Epson', '2022-05-20', 'available', 'May chieu chinh cua phong', 'A202', 'May chieu'
    UNION ALL SELECT 'PC-A202-01', 'May tinh giang vien A202', 'Lenovo', '2024-01-10', 'available', 'May tinh dieu khien may chieu', 'A202', 'May tinh'
    UNION ALL SELECT 'SW-B105-01', 'Switch mang B105', 'Cisco', '2021-11-12', 'maintenance', 'Dang kiem tra cong mang', 'B105', 'Thiet bi mang'
    UNION ALL SELECT 'RT-B105-01', 'Router thuc hanh B105', 'TP-Link', '2023-03-10', 'available', 'Router phuc vu bai thuc hanh', 'B105', 'Thiet bi mang'
    UNION ALL SELECT 'PC-B210-01', 'May tinh ky thuat B210', 'Asus', '2022-02-18', 'available', 'May tinh dung cho ky thuat vien', 'B210', 'May tinh'
    UNION ALL SELECT 'KB-A101-01', 'Bo ban phim chuot A101', 'Logitech', '2023-08-15', 'available', 'Bo ngoai vi may tinh', 'A101', 'Thiet bi ngoai vi'
    UNION ALL SELECT 'PR-C001-01', 'May chieu C001', 'BenQ', '2020-07-30', 'retired', 'Da ngung su dung', 'C001', 'May chieu'
) v JOIN rooms r ON r.room_code = v.room_code
JOIN equipment_types et ON et.type_name = v.type_name
WHERE NOT EXISTS (SELECT 1 FROM equipment e WHERE e.equipment_code = v.equipment_code);

-- 8. YEU CAU DAT PHONG
-- Co du cac trang thai pending/approved/rejected/cancelled de test giao dien.
INSERT INTO bookings (user_id, room_id, title, purpose, start_time, end_time, status, note)
SELECT u.user_id, r.room_id, v.title, v.purpose, v.start_time, v.end_time, v.status, v.note
FROM (
    SELECT 'minhanh.sample@example.com' email, 'A101' room_code, 'Thuc hanh CSDL - Nhom 1' title, 'Bai thuc hanh truy van SQL' purpose, '2026-10-26 08:00:00' start_time, '2026-10-26 10:00:00' end_time, 'approved' status, 'Da duoc duyet' note
    UNION ALL SELECT 'quocbao.sample@example.com', 'A101', 'On tap lap trinh', 'On tap lap trinh C#', '2026-10-26 10:00:00', '2026-10-26 12:00:00', 'pending', 'Cho quan ly xac nhan'
    UNION ALL SELECT 'mailan.sample@example.com', 'B105', 'Thuc hanh mang may tinh', 'Cau hinh router va switch', '2026-10-26 13:30:00', '2026-10-26 16:30:00', 'pending', 'Can chuan bi thiet bi mang'
    UNION ALL SELECT 'minhanh.sample@example.com', 'A202', 'Bao cao do an', 'Trinh bay tien do do an', '2026-10-27 08:00:00', '2026-10-27 09:30:00', 'rejected', 'Phong da co lich su dung'
    UNION ALL SELECT 'quocbao.sample@example.com', 'C001', 'Sinh hoat hoc thuat', 'Sinh hoat cau lac bo hoc thuat', '2026-10-27 14:00:00', '2026-10-27 16:00:00', 'cancelled', 'Nguoi dat huy lich'
    UNION ALL SELECT 'mailan.sample@example.com', 'A101', 'Thuc hanh lap trinh Python', 'Thuc hanh vong lap va ham', '2026-10-28 08:00:00', '2026-10-28 11:00:00', 'approved', 'Da duyet'
    UNION ALL SELECT 'minhanh.sample@example.com', 'B210', 'Lap rap may tinh', 'Thuc hanh lap rap phan cung', '2026-10-28 13:00:00', '2026-10-28 15:00:00', 'pending', 'Cho duyet'
    UNION ALL SELECT 'quocbao.sample@example.com', 'A202', 'Thuc hanh Excel', 'Xu ly bang tinh', '2026-10-29 09:00:00', '2026-10-29 11:00:00', 'approved', 'Da duyet'
) v JOIN users u ON u.email = v.email
JOIN rooms r ON r.room_code = v.room_code
WHERE NOT EXISTS (
    SELECT 1 FROM bookings b
    WHERE b.user_id = u.user_id AND b.room_id = r.room_id
      AND b.title = v.title AND b.start_time = v.start_time
);

-- 9. LICH SU DUYET: chi tao lich su cho booking approved/rejected
INSERT INTO booking_approvals (booking_id, reviewer_id, action, comment, reviewed_at)
SELECT b.booking_id, reviewer.user_id, v.action, v.comment, v.reviewed_at
FROM (
    SELECT 'Thuc hanh CSDL - Nhom 1' title, '2026-10-26 08:00:00' start_time, 'approved' action, 'Phong va thoi gian phu hop' comment, '2026-10-20 09:00:00' reviewed_at
    UNION ALL SELECT 'Bao cao do an', '2026-10-27 08:00:00', 'rejected', 'Trung lich su dung phong', '2026-10-20 10:00:00'
    UNION ALL SELECT 'Thuc hanh lap trinh Python', '2026-10-28 08:00:00', 'approved', 'Da xac nhan lich', '2026-10-21 08:30:00'
    UNION ALL SELECT 'Thuc hanh Excel', '2026-10-29 09:00:00', 'approved', 'Dong y su dung phong', '2026-10-21 09:00:00'
) v JOIN bookings b ON b.title = v.title AND b.start_time = v.start_time
JOIN users reviewer ON reviewer.email = 'thuha.sample@example.com'
WHERE NOT EXISTS (
    SELECT 1 FROM booking_approvals a
    WHERE a.booking_id = b.booking_id AND a.reviewer_id = reviewer.user_id AND a.action = v.action
);

-- 10. LICH SU THIET BI
INSERT INTO equipment_history (equipment_id, user_id, old_status, new_status, change_note, changed_at)
SELECT e.equipment_id, u.user_id, v.old_status, v.new_status, v.change_note, v.changed_at
FROM (
    SELECT 'PC-A101-03' equipment_code, 'available' old_status, 'broken' new_status, 'Ghi nhan may khong khoi dong' change_note, '2026-10-18 09:00:00' changed_at, 'duclong.sample@example.com' email
    UNION ALL SELECT 'SW-B105-01', 'available', 'maintenance', 'Chuyen thiet bi sang trang thai bao tri', '2026-10-19 14:00:00', 'duclong.sample@example.com'
    UNION ALL SELECT 'PR-C001-01', 'available', 'retired', 'Thiet bi het han su dung', '2026-09-30 10:00:00', 'thuha.sample@example.com'
) v JOIN equipment e ON e.equipment_code = v.equipment_code
JOIN users u ON u.email = v.email
WHERE NOT EXISTS (
    SELECT 1 FROM equipment_history h
    WHERE h.equipment_id = e.equipment_id AND h.change_note = v.change_note
);

-- 11. BAO HONG / BAO TRI
INSERT INTO maintenance_reports (equipment_id, reporter_id, assigned_to, title, description, status, reported_at, resolved_at, resolution_note)
SELECT e.equipment_id, reporter.user_id, technician.user_id, v.title, v.description, v.status, v.reported_at, v.resolved_at, v.resolution_note
FROM (
    SELECT 'PC-A101-03' equipment_code, 'minhanh.sample@example.com' reporter_email, 'duclong.sample@example.com' technician_email, 'May tinh khong khoi dong' title, 'Nhan nut nguon nhung may khong len.' description, 'processing' status, '2026-10-18 09:05:00' reported_at, NULL resolved_at, NULL resolution_note
    UNION ALL SELECT 'SW-B105-01', 'mailan.sample@example.com', 'duclong.sample@example.com', 'Kiem tra switch mang', 'Mot so cong mang khong co ket noi.', 'processing', '2026-10-19 14:05:00', NULL, NULL
    UNION ALL SELECT 'PR-C001-01', 'thuha.sample@example.com', 'duclong.sample@example.com', 'May chieu cu hong', 'May chieu khong con dap ung nhu cau su dung.', 'resolved', '2026-09-25 08:00:00', '2026-09-30 10:00:00', 'De xuat thanh ly thiet bi.'
    UNION ALL SELECT 'PC-A101-02', 'quocbao.sample@example.com', 'duclong.sample@example.com', 'Bao duong dinh ky may tinh', 'Ve sinh va kiem tra may tram.', 'reported', '2026-10-22 15:30:00', NULL, NULL
) v JOIN equipment e ON e.equipment_code = v.equipment_code
JOIN users reporter ON reporter.email = v.reporter_email
LEFT JOIN users technician ON technician.email = v.technician_email
WHERE NOT EXISTS (
    SELECT 1 FROM maintenance_reports m
    WHERE m.equipment_id = e.equipment_id AND m.title = v.title
);

-- 12. LICH SU XU LY BAO TRI
INSERT INTO maintenance_history (report_id, user_id, old_status, new_status, action_note, changed_at)
SELECT m.report_id, u.user_id, v.old_status, v.new_status, v.action_note, v.changed_at
FROM (
    SELECT 'May tinh khong khoi dong' title, 'processing' new_status, 'reported' old_status, 'Ky thuat vien da tiep nhan bao hong' action_note, '2026-10-18 10:00:00' changed_at, 'duclong.sample@example.com' email
    UNION ALL SELECT 'Kiem tra switch mang', 'processing', 'reported', 'Dang kiem tra cac cong ket noi', '2026-10-19 15:00:00', 'duclong.sample@example.com'
    UNION ALL SELECT 'May chieu cu hong', 'resolved', 'processing', 'Da ket thuc kiem tra va de xuat thanh ly', '2026-09-30 10:00:00', 'duclong.sample@example.com'
) v JOIN maintenance_reports m ON m.title = v.title
JOIN users u ON u.email = v.email
WHERE NOT EXISTS (
    SELECT 1 FROM maintenance_history h
    WHERE h.report_id = m.report_id AND h.action_note = v.action_note
);

-- KIEM TRA SO LUONG DU LIEU SAU KHI CHAY
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
