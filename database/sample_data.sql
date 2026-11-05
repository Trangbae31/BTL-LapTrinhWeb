USE ql_phong_thuc_hanh;
SET NAMES utf8mb4;

-- =====================================================
-- 1. VAI TRÒ
-- =====================================================
INSERT INTO roles (role_name, description)
VALUES
('admin', 'Quản trị hệ thống'),
('manager', 'Quản lý phòng và thiết bị'),
('teacher', 'Giảng viên đặt phòng thực hành'),
('technician', 'Nhân viên kỹ thuật và bảo trì')
ON DUPLICATE KEY UPDATE
    description = VALUES(description);

-- =====================================================
-- 2. TÀI KHOẢN DEMO
-- Mật khẩu chung: Admin@123456
-- Hash được tạo bằng PHP password_hash()
-- =====================================================
INSERT INTO users (full_name, email, password_hash, status)
VALUES
('Quản trị hệ thống', 'admin.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'active'),
('Nguyễn Thu Trang', 'thutrang.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'active'),
('Phương Thảo', 'phuongthao.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'active'),
('Diệu Thanh', 'dieuthanh.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'active'),
('Ngô Nam Khánh', 'namkhanh.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'active'),
('Hoàng Mai Lan', 'mailan.sample@example.com',
 '$2y$10$xFrXoBqAZnF4PAv6R3QLhuUPIGmkHi5wjnGpmnWld1O7FTnYl27bq', 'inactive')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    password_hash = VALUES(password_hash),
    status = VALUES(status);

-- =====================================================
-- 3. GÁN VAI TRÒ
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
-- 4. TÒA NHÀ
-- =====================================================
INSERT INTO buildings (building_name, address, description, status)
SELECT
    danh_sach_toa_nha.building_name,
    danh_sach_toa_nha.address,
    danh_sach_toa_nha.description,
    'active'
FROM (
    SELECT
        'Tòa nhà A' AS building_name,
        'Cơ sở 1 - Hà Nội' AS address,
        'Khu phòng thực hành máy tính' AS description
    UNION ALL SELECT
        'Tòa nhà B',
        'Cơ sở 1 - Hà Nội',
        'Khu thực hành mạng và phần cứng'
    UNION ALL SELECT
        'Tòa nhà C',
        'Cơ sở 2 - Hà Nội',
        'Khu thực hành đa năng'
) AS danh_sach_toa_nha
WHERE NOT EXISTS (
    SELECT 1
    FROM buildings AS b
    WHERE b.building_name = danh_sach_toa_nha.building_name
);

-- =====================================================
-- 5. PHÒNG THỰC HÀNH
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
        'Phòng máy tính A101' AS room_name,
        40 AS capacity,
        'Tầng 1' AS location,
        'Phòng thực hành lập trình' AS description,
        'active' AS status,
        'Tòa nhà A' AS building_name
    UNION ALL SELECT
        'A202', 'Phòng thực hành A202', 30, 'Tầng 2',
        'Phòng thực hành có máy chiếu', 'active', 'Tòa nhà A'
    UNION ALL SELECT
        'B105', 'Phòng mạng B105', 25, 'Tầng 1',
        'Phòng thực hành mạng máy tính', 'active', 'Tòa nhà B'
    UNION ALL SELECT
        'B210', 'Phòng phần cứng B210', 20, 'Tầng 2',
        'Phòng lắp ráp và sửa chữa máy tính', 'active', 'Tòa nhà B'
    UNION ALL SELECT
        'C001', 'Phòng đa năng C001', 50, 'Tầng 1',
        'Phòng học và hội thảo', 'active', 'Tòa nhà C'
    UNION ALL SELECT
        'C002', 'Phòng dự phòng C002', 15, 'Tầng 1',
        'Phòng nhỏ dành cho nhóm học tập', 'inactive', 'Tòa nhà C'
) AS danh_sach_phong
JOIN buildings AS b
    ON b.building_name = danh_sach_phong.building_name
WHERE NOT EXISTS (
    SELECT 1
    FROM rooms AS r
    WHERE r.room_code = danh_sach_phong.room_code
);

-- =====================================================
-- 6. LOẠI THIẾT BỊ
-- =====================================================
INSERT INTO equipment_types (type_name, description)
VALUES
('Máy tính', 'Máy tính để bàn hoặc máy trạm'),
('Máy chiếu', 'Thiết bị trình chiếu'),
('Thiết bị mạng', 'Router, switch và thiết bị mạng'),
('Thiết bị ngoại vi', 'Bàn phím, chuột và thiết bị ngoại vi'),
('Thiết bị điện', 'Dây nguồn và thiết bị điện')
ON DUPLICATE KEY UPDATE
    description = VALUES(description);

-- =====================================================
-- 7. THIẾT BỊ
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
        'Máy tính thực hành 01' AS equipment_name,
        'Dell' AS brand,
        '2023-08-15' AS purchase_date,
        'available' AS status,
        'Máy trạm cho sinh viên' AS description,
        'A101' AS room_code,
        'Máy tính' AS type_name
    UNION ALL SELECT
        'PC-A101-02', 'Máy tính thực hành 02', 'HP',
        '2023-08-15', 'available', 'Máy trạm cho sinh viên',
        'A101', 'Máy tính'
    UNION ALL SELECT
        'PC-A101-03', 'Máy tính thực hành 03', 'Dell',
        '2022-09-01', 'broken', 'Không khởi động được',
        'A101', 'Máy tính'
    UNION ALL SELECT
        'PR-A202-01', 'Máy chiếu A202', 'Epson',
        '2022-05-20', 'available', 'Máy chiếu chính của phòng',
        'A202', 'Máy chiếu'
    UNION ALL SELECT
        'PC-A202-01', 'Máy tính giảng viên A202', 'Lenovo',
        '2024-01-10', 'available', 'Máy tính điều khiển máy chiếu',
        'A202', 'Máy tính'
    UNION ALL SELECT
        'SW-B105-01', 'Switch mạng B105', 'Cisco',
        '2021-11-12', 'maintenance', 'Đang kiểm tra cổng mạng',
        'B105', 'Thiết bị mạng'
    UNION ALL SELECT
        'RT-B105-01', 'Router thực hành B105', 'TP-Link',
        '2023-03-10', 'available', 'Router phục vụ bài thực hành',
        'B105', 'Thiết bị mạng'
    UNION ALL SELECT
        'PC-B210-01', 'Máy tính kỹ thuật B210', 'Asus',
        '2022-02-18', 'available', 'Máy tính dùng cho kỹ thuật viên',
        'B210', 'Máy tính'
    UNION ALL SELECT
        'KB-A101-01', 'Bộ bàn phím chuột A101', 'Logitech',
        '2023-08-15', 'available', 'Bộ ngoại vi máy tính',
        'A101', 'Thiết bị ngoại vi'
    UNION ALL SELECT
        'PR-C001-01', 'Máy chiếu C001', 'BenQ',
        '2020-07-30', 'retired', 'Đã ngừng sử dụng',
        'C001', 'Máy chiếu'
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
-- 8. YÊU CẦU ĐẶT PHÒNG
-- Giảng viên tự đặt: pending
-- Quản lý đặt thay giảng viên / hoạt động toàn trường: approved
-- Chỉ booking approved mới giữ chỗ và chặn trùng lịch.
-- =====================================================
INSERT INTO bookings (
    user_id, created_by_user_id, booking_type, room_id,
    title, purpose, start_time, end_time, status, note
)
SELECT
    owner.user_id,
    creator.user_id,
    d.booking_type,
    r.room_id,
    d.title,
    d.purpose,
    d.start_time,
    d.end_time,
    d.status,
    d.note
FROM (
    SELECT
        'thutrang.sample@example.com' AS owner_email,
        'thutrang.sample@example.com' AS creator_email,
        'personal' AS booking_type,
        'A101' AS room_code,
        'Thực hành CSDL - Nhóm 1' AS title,
        'Bài thực hành truy vấn SQL' AS purpose,
        '2026-11-06 08:00:00' AS start_time,
        '2026-11-06 10:00:00' AS end_time,
        'pending' AS status,
        'Giảng viên tự tạo, đang chờ quản lý duyệt' AS note
    UNION ALL SELECT
        'thutrang.sample@example.com', 'phuongthao.sample@example.com',
        'on_behalf', 'A202', 'Ôn tập lập trình C#',
        'Quản lý tạo lịch thay giảng viên Nguyễn Thu Trang',
        '2026-11-06 09:00:00', '2026-11-06 11:00:00',
        'approved', 'Quản lý tạo thay giảng viên, đã duyệt tự động'
    UNION ALL SELECT
        NULL, 'phuongthao.sample@example.com',
        'school_wide', 'C001', 'Sinh hoạt học thuật toàn trường',
        'Hoạt động chung của nhà trường',
        '2026-11-07 08:00:00', '2026-11-07 11:00:00',
        'approved', 'Quản lý tạo lịch hoạt động toàn trường'
    UNION ALL SELECT
        'thutrang.sample@example.com', 'thutrang.sample@example.com',
        'personal', 'B105', 'Thực hành mạng máy tính',
        'Cấu hình router và switch',
        '2026-11-08 13:30:00', '2026-11-08 16:30:00',
        'pending', 'Yêu cầu cá nhân đang chờ duyệt'
    UNION ALL SELECT
        'thutrang.sample@example.com', 'thutrang.sample@example.com',
        'personal', 'B210', 'Lắp ráp máy tính',
        'Thực hành lắp ráp phần cứng',
        '2026-11-09 13:00:00', '2026-11-09 15:00:00',
        'approved', 'Booking cá nhân đã được duyệt'
) AS d
LEFT JOIN users owner
    ON owner.email = d.owner_email
JOIN users creator
    ON creator.email = d.creator_email
JOIN rooms r
    ON r.room_code = d.room_code
WHERE NOT EXISTS (
    SELECT 1
    FROM bookings b
    WHERE b.title = d.title
      AND b.start_time = d.start_time
      AND b.room_id = r.room_id
);

-- =====================================================
-- 9. LỊCH SỬ DUYỆT ĐẶT PHÒNG
-- =====================================================
INSERT INTO booking_approvals (
    booking_id, reviewer_id, action, comment, reviewed_at
)
SELECT
    b.booking_id,
    reviewer.user_id,
    d.action,
    d.comment,
    d.reviewed_at
FROM (
    SELECT
        'Ôn tập lập trình C#' AS title,
        '2026-11-06 09:00:00' AS start_time,
        'approved' AS action,
        'Quản lý tạo thay giảng viên, lịch đã được xác nhận' AS comment,
        '2026-11-04 09:00:00' AS reviewed_at
    UNION ALL SELECT
        'Sinh hoạt học thuật toàn trường',
        '2026-11-07 08:00:00',
        'approved',
        'Chấp thuận hoạt động chung của nhà trường',
        '2026-11-04 09:10:00'
    UNION ALL SELECT
        'Lắp ráp máy tính',
        '2026-11-09 13:00:00',
        'approved',
        'Phòng và thời gian phù hợp',
        '2026-11-04 09:20:00'
) AS d
JOIN bookings b
    ON b.title = d.title
   AND b.start_time = d.start_time
JOIN users reviewer
    ON reviewer.email = 'phuongthao.sample@example.com'
WHERE NOT EXISTS (
    SELECT 1
    FROM booking_approvals a
    WHERE a.booking_id = b.booking_id
      AND a.reviewer_id = reviewer.user_id
      AND a.action = d.action
);

-- =====================================================
-- 9A. YÊU CẦU HỦY BOOKING ĐÃ ĐƯỢC DUYỆT
-- Booking vẫn giữ trạng thái approved cho đến khi quản lý xử lý.
-- =====================================================
INSERT INTO booking_cancellation_requests (
    booking_id, requester_id, reason, status, requested_at
)
SELECT
    b.booking_id,
    requester.user_id,
    'Giảng viên có lịch công tác đột xuất, xin phép hủy lịch.',
    'pending',
    '2026-11-04 10:00:00'
FROM bookings b
JOIN users requester
    ON requester.email = 'thutrang.sample@example.com'
WHERE b.title = 'Lắp ráp máy tính'
  AND b.start_time = '2026-11-09 13:00:00'
  AND NOT EXISTS (
      SELECT 1
      FROM booking_cancellation_requests cr
      WHERE cr.booking_id = b.booking_id
        AND cr.requester_id = requester.user_id
        AND cr.status = 'pending'
  );

-- =====================================================
-- 10. LỊCH SỬ THAY ĐỔI THIẾT BỊ
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
        'Ghi nhận máy không khởi động' AS change_note,
        '2026-10-18 09:00:00' AS changed_at,
        'dieuthanh.sample@example.com' AS email
    UNION ALL SELECT
        'SW-B105-01', 'available', 'maintenance',
        'Chuyển thiết bị sang trạng thái bảo trì',
        '2026-10-19 14:00:00', 'dieuthanh.sample@example.com'
    UNION ALL SELECT
        'PR-C001-01', 'available', 'retired',
        'Thiết bị hết hạn sử dụng',
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
-- 11. BÁO HỎNG / BẢO TRÌ
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
        'Máy tính không khởi động' AS title,
        'Nhấn nút nguồn nhưng máy không lên.' AS description,
        'processing' AS status,
        '2026-10-18 09:05:00' AS reported_at,
        NULL AS resolved_at,
        NULL AS resolution_note
    UNION ALL SELECT
        'SW-B105-01', 'mailan.sample@example.com',
        'dieuthanh.sample@example.com', 'Kiểm tra switch mạng',
        'Một số cổng mạng không có kết nối.', 'processing',
        '2026-10-19 14:05:00', NULL, NULL
    UNION ALL SELECT
        'PR-C001-01', 'phuongthao.sample@example.com',
        'dieuthanh.sample@example.com', 'Máy chiếu cũ hỏng',
        'Máy chiếu không còn đáp ứng nhu cầu sử dụng.', 'resolved',
        '2026-09-25 08:00:00', '2026-09-30 10:00:00',
        'Đề xuất thanh lý thiết bị.'
    UNION ALL SELECT
        'PC-A101-02', 'namkhanh.sample@example.com',
        'dieuthanh.sample@example.com', 'Bảo dưỡng định kỳ máy tính',
        'Vệ sinh và kiểm tra máy trạm.', 'reported',
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
-- 12. LỊCH SỬ XỬ LÝ BẢO TRÌ
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
        'Máy tính không khởi động' AS title,
        'reported' AS old_status,
        'processing' AS new_status,
        'Kỹ thuật viên đã tiếp nhận báo hỏng' AS action_note,
        '2026-10-18 10:00:00' AS changed_at,
        'dieuthanh.sample@example.com' AS email
    UNION ALL SELECT
        'Kiểm tra switch mạng', 'reported', 'processing',
        'Đang kiểm tra các cổng kết nối',
        '2026-10-19 15:00:00', 'dieuthanh.sample@example.com'
    UNION ALL SELECT
        'Máy chiếu cũ hỏng', 'processing', 'resolved',
        'Đã kết thúc kiểm tra và đề xuất thanh lý',
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
-- 13. KIỂM TRA SỐ LƯỢNG DỮ LIỆU SAU KHI CHẠY
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
UNION ALL SELECT 'booking_cancellation_requests', COUNT(*) FROM booking_cancellation_requests
UNION ALL SELECT 'equipment_history', COUNT(*) FROM equipment_history
UNION ALL SELECT 'maintenance_reports', COUNT(*) FROM maintenance_reports
UNION ALL SELECT 'maintenance_history', COUNT(*) FROM maintenance_history;

-- =====================================================
-- 14. KIỂM TRA TÀI KHOẢN VÀ VAI TRÒ
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