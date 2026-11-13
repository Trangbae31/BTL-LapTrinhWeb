<?php
require_once __DIR__ . '/../core/CSRF.php';

function bookingFormEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$errors = $errors ?? [];
$formData = $formData ?? ($old ?? []);
$rooms = $rooms ?? [];
$teachers = $teachers ?? [];
$role = $role ?? (Authorization::hasRole('teacher') ? 'teacher' : (Authorization::hasRole('admin') ? 'admin' : 'manager'));
$isStaffBooking = in_array($role, ['admin', 'manager'], true);
$selectedBookingType = $formData['booking_type'] ?? ($isStaffBooking ? 'on_behalf' : 'personal');

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt phòng thực hành - Smart Campus</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: #ffffff;
            border-bottom: 1px solid #eef2f0;
            margin-bottom: var(--space-md);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: var(--primary-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .brand-title {
            font-weight: 700;
            font-size: 16px;
            color: var(--primary-color);
            margin: 0;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 11px;
            color: var(--text-sub);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .nav-back {
            color: var(--text-main);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .nav-back:hover {
            color: var(--primary-color);
        }

        .main-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px 40px 20px;
        }

        .page-header {
            margin-bottom: var(--space-md);
        }

        .page-title {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .page-desc {
            color: var(--text-sub);
            font-size: 14px;
            margin: 0;
        }

        .form-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 28px 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }

        .form-card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            margin: 0 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #f2f4f3;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .form-note {
            font-size: 12px;
            color: var(--text-sub);
            margin-top: 4px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #f2f4f3;
        }

        .error-message {
            color: var(--danger-color);
            font-size: 13px;
            margin-top: 4px;
        }

        .flash {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
        }

        .flash.error {
            background: #FDF2F1;
            color: var(--danger-color);
            border: 1px solid #F2D0D0;
        }

        .footer-text {
            text-align: center;
            color: #9aa29e;
            font-size: 12px;
            margin-top: 40px;
        }
    </style>
</head>

<body>

<!-- TOP NAVBAR -->
<div class="top-navbar">
    <div class="brand-logo">
        <div class="brand-icon">📝</div>
        <div>
            <h2 class="brand-title">Smart Campus</h2>
            <p class="brand-subtitle">Đặt phòng thực hành</p>
        </div>
    </div>
    <a href="index.php?action=calendar" class="nav-back">← Quay lại lịch phòng</a>
</div>

<div class="main-container">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <h1 class="page-title">Đặt phòng thực hành</h1>
        <p class="page-desc">Điền thông tin để tạo yêu cầu sử dụng phòng thực hành.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= bookingFormEscape($flash['type'] ?? 'error') ?>">
            <?= bookingFormEscape($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <!-- FORM CARD -->
    <div class="form-card">
        <h3 class="form-card-title">Thông tin đặt phòng</h3>

        <form method="post" action="index.php?action=booking_store">
            <?= CSRF::field() ?>

            <?php if (!empty($errors)): ?>
                <div class="flash error" role="alert">
                    <strong>Không thể gửi yêu cầu:</strong>
                    <ul style="margin:8px 0 0 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= bookingFormEscape($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Hình thức đặt phòng -->
            <div class="form-group">
                <label for="booking_type">Hình thức đặt phòng</label>
                <?php if ($isStaffBooking): ?>
                    <select name="booking_type" id="booking_type" class="form-control" required>
                        <option value="on_behalf" <?= $selectedBookingType === 'on_behalf' ? 'selected' : '' ?>>Đặt phòng thay giảng viên</option>
                        <option value="school_wide" <?= $selectedBookingType === 'school_wide' ? 'selected' : '' ?>>Đặt phòng cho hoạt động chung</option>
                    </select>
                    <p class="form-note">Booking do Admin/Manager tạo sẽ được duyệt tự động sau khi kiểm tra hợp lệ.</p>
                <?php else: ?>
                    <input type="text" class="form-control" value="Đặt phòng cá nhân" readonly style="background-color: #f8faf9;">
                    <p class="form-note">Yêu cầu đặt phòng cá nhân sẽ ở trạng thái chờ Quản lý/Admin duyệt.</p>
                <?php endif; ?>
            </div>

            <?php if ($isStaffBooking): ?>
                <div class="form-group" id="teacherSelectGroup" style="<?= $selectedBookingType === 'school_wide' ? 'display:none;' : '' ?>">
                    <label for="teacher_id">Giảng viên sử dụng phòng <span style="color: var(--danger-color);">*</span></label>
                    <select name="teacher_id" id="teacher_id" class="form-control" <?= $selectedBookingType === 'school_wide' ? '' : 'required' ?>>
                        <option value="">-- Chọn giảng viên --</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= (int)$teacher['user_id'] ?>" <?= ((int)($formData['teacher_id'] ?? 0) === (int)$teacher['user_id']) ? 'selected' : '' ?>>
                                <?= bookingFormEscape($teacher['full_name']) ?> (<?= bookingFormEscape($teacher['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-note">Chỉ hiển thị tài khoản giảng viên đang hoạt động.</p>
                </div>
            <?php endif; ?>

            <!-- Chọn phòng -->
            <div class="form-group">
                <label for="room_id">Phòng thực hành <span style="color: var(--danger-color);">*</span></label>
                <select name="room_id" id="room_id" class="form-control" required>
                    <option value="">-- Chọn phòng --</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= (int)$room['room_id'] ?>" <?= ((int)($formData['room_id'] ?? 0) === (int)$room['room_id']) ? 'selected' : '' ?>>
                            <?= bookingFormEscape($room['room_code']) ?> - <?= bookingFormEscape($room['room_name']) ?> (Sức chứa: <?= (int)$room['capacity'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['room_id'])): ?>
                    <p class="error-message"><?= bookingFormEscape($errors['room_id']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Tiêu đề booking -->
            <div class="form-group">
                <label for="title">Tên nội dung đặt phòng <span style="color: var(--danger-color);">*</span></label>
                <input
                    type="text"
                    name="title"
                    id="title"
                    class="form-control"
                    maxlength="200"
                    placeholder="Ví dụ: Thực hành PHP, Ôn tập lập trình C#..."
                    value="<?= bookingFormEscape($formData['title'] ?? '') ?>"
                    required
                >
            </div>

            <!-- Mục đích sử dụng -->
            <div class="form-group">
                <label for="purpose">Mục đích sử dụng <span style="color: var(--danger-color);">*</span></label>
                <textarea
                    name="purpose"
                    id="purpose"
                    class="form-control"
                    rows="3"
                    placeholder="Mô tả mục đích sử dụng phòng..."
                    required
                ><?= bookingFormEscape($formData['purpose'] ?? '') ?></textarea>
            </div>

            <!-- Ngày sử dụng -->
            <div class="form-group">
                <label for="booking_date">Ngày sử dụng <span style="color: var(--danger-color);">*</span></label>
                <input
                    type="date"
                    name="booking_date"
                    id="booking_date"
                    class="form-control"
                    value="<?= bookingFormEscape($formData['booking_date'] ?? '') ?>"
                    required
                >
                <?php if (!empty($errors['booking_date'])): ?>
                    <p class="error-message"><?= bookingFormEscape($errors['booking_date']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Giờ bắt đầu & Giờ kết thúc -->
            <div class="form-row">
                <div class="form-group">
                    <label for="start_time">Giờ bắt đầu <span style="color: var(--danger-color);">*</span></label>
                    <input
                        type="time"
                        name="start_time"
                        id="start_time"
                        class="form-control"
                        value="<?= bookingFormEscape($formData['start_time'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($errors['start_time'])): ?>
                        <p class="error-message"><?= bookingFormEscape($errors['start_time']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="end_time">Giờ kết thúc <span style="color: var(--danger-color);">*</span></label>
                    <input
                        type="time"
                        name="end_time"
                        id="end_time"
                        class="form-control"
                        value="<?= bookingFormEscape($formData['end_time'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($errors['end_time'])): ?>
                        <p class="error-message"><?= bookingFormEscape($errors['end_time']) ?></p>
                    <?php endif; ?>
                </div>
            </div>



            <div id="roomAvailabilityMessage" class="form-group" aria-live="polite" style="display:none;padding:12px;border-radius:8px;background:#f4f7f5;"></div>

            <!-- Nút thao tác -->
            <div class="form-actions">
                <button type="reset" class="btn btn-secondary">Nhập lại</button>
                <button type="submit" class="btn btn-primary">Gửi yêu cầu đặt phòng</button>
            </div>
        </form>
    </div>

    <div class="footer-text">
        © Smart Campus Management · Quản lý phòng thực hành và thiết bị
    </div>

</div>

<script>
(function () {
    const bookingType = document.getElementById('booking_type');
    const teacherGroup = document.getElementById('teacherSelectGroup');
    const teacherSelect = document.getElementById('teacher_id');

    function updateTeacherRequirement() {
        if (!bookingType || !teacherGroup || !teacherSelect) return;
        const isOnBehalf = bookingType.value === 'on_behalf';
        teacherGroup.style.display = isOnBehalf ? '' : 'none';
        teacherSelect.required = isOnBehalf;
        if (!isOnBehalf) teacherSelect.value = '';
    }

    if (bookingType) {
        bookingType.addEventListener('change', updateTeacherRequirement);
        updateTeacherRequirement();
    }
})();

(function () {
    const room = document.getElementById('room_id');
    const date = document.getElementById('booking_date');
    const start = document.getElementById('start_time');
    const end = document.getElementById('end_time');
    const message = document.getElementById('roomAvailabilityMessage');
    let requestId = 0;

    async function checkRoom() {
        if (!room || !date || !start || !end || !message) return;
        if (!room.value || !date.value || !start.value || !end.value) {
            message.style.display = 'none';
            return;
        }
        const startValue = date.value + 'T' + start.value;
        const endValue = date.value + 'T' + end.value;
        if (endValue <= startValue) {
            message.style.display = 'block';
            message.textContent = 'Giờ kết thúc phải sau giờ bắt đầu.';
            message.style.color = '#a12e2a';
            return;
        }
        const currentRequest = ++requestId;
        message.style.display = 'block';
        message.style.color = '#66736c';
        message.textContent = 'Đang kiểm tra tình trạng phòng...';
        try {
            const params = new URLSearchParams({room_id: room.value, start_time: startValue, end_time: endValue});
            const response = await fetch('index.php?action=room_availability_api&' + params.toString(), {
                headers: {'Accept': 'application/json'}
            });
            const data = await response.json();
            if (currentRequest !== requestId) return;
            message.textContent = data.message || 'Không thể kiểm tra phòng.';
            message.style.color = data.success && data.available ? '#287a45' : '#a12e2a';
        } catch (error) {
            if (currentRequest !== requestId) return;
            message.textContent = 'Không thể kết nối API kiểm tra phòng. Hệ thống vẫn kiểm tra lại khi gửi yêu cầu.';
            message.style.color = '#986500';
        }
    }
    [room, date, start, end].forEach(function (field) {
        if (field) field.addEventListener('change', checkRoom);
    });
    checkRoom();
})();
</script>
</body>
</html>
