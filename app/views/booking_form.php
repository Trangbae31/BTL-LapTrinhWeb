
<?php

function bookingFormEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$errors = $errors ?? [];
$old = $old ?? ($_POST ?? []);
$role = $role ?? 'teacher';

$isManager = in_array($role, ['admin', 'manager'], true);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt phòng thực hành</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .form-hint {
            color: #796b60;
            font-size: 13px;
            margin-top: 5px;
        }

        .form-errors {
            padding: 12px 18px;
            margin-bottom: 18px;
            border-radius: 6px;
            background: #fbe8e7;
            color: #a12e2a;
        }

        .form-errors ul {
            margin-bottom: 0;
        }
    </style>
</head>

<body>

<main class="page-container">

    <header class="page-header">
        <div>
            <p class="page-label">SMART CAMPUS</p>
            <h1>Đặt phòng thực hành</h1>
            <p class="page-description">
                Điền thông tin để tạo lịch sử dụng phòng thực hành.
            </p>
        </div>

        <a href="index.php?action=calendar" class="btn-secondary">
            Quay lại lịch phòng
        </a>
    </header>

    <section class="content-card">

        <h2>Thông tin đặt phòng</h2>

        <?php if (!empty($errors)): ?>
            <div class="form-errors" role="alert">
                <strong>Không thể tạo lịch:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= bookingFormEscape($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="index.php?action=booking_store">

            <?= CSRF::field() ?>

            <?php if ($isManager): ?>

                <div class="form-group">
                    <label for="booking_type">Hình thức đặt phòng</label>

                    <select
                        id="booking_type"
                        name="booking_type"
                        required
                    >
                        <option value="">-- Chọn hình thức --</option>

                        <option
                            value="on_behalf"
                            <?= ($old['booking_type'] ?? '') === 'on_behalf' ? 'selected' : '' ?>
                        >
                            Đặt thay giảng viên
                        </option>

                        <option
                            value="school_wide"
                            <?= ($old['booking_type'] ?? '') === 'school_wide' ? 'selected' : '' ?>
                        >
                            Hoạt động chung của nhà trường
                        </option>
                    </select>

                    <p class="form-hint">
                        Lịch do Manager/Admin tạo sẽ được xác nhận tự động.
                    </p>
                </div>

                <div
                    class="form-group"
                    id="teacher-selector"
                    style="display: none;"
                >
                    <label for="teacher_id">Giảng viên sử dụng phòng</label>

                    <select id="teacher_id" name="teacher_id">
                        <option value="">-- Chọn giảng viên --</option>

                        <?php foreach ($teachers as $teacher): ?>
                            <option
                                value="<?= (int)$teacher['user_id'] ?>"
                                <?= (string)($old['teacher_id'] ?? '') ===
                                    (string)$teacher['user_id'] ? 'selected' : '' ?>
                            >
                                <?= bookingFormEscape($teacher['full_name']) ?>
                                <?php if (!empty($teacher['email'])): ?>
                                    (<?= bookingFormEscape($teacher['email']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

            <?php else: ?>

                <div class="form-group">
                    <label>Hình thức đặt phòng</label>
                    <input type="text" value="Đặt phòng cá nhân" disabled>
                    <p class="form-hint">
                        Yêu cầu của giảng viên sẽ ở trạng thái chờ duyệt.
                    </p>
                </div>

            <?php endif; ?>

            <div class="form-group">
                <label for="room_id">Phòng thực hành</label>

                <select id="room_id" name="room_id" required>
                    <option value="">-- Chọn phòng --</option>

                    <?php foreach ($rooms as $room): ?>
                        <option
                            value="<?= (int)$room['room_id'] ?>"
                            <?= (string)($old['room_id'] ?? '') ===
                                (string)$room['room_id'] ? 'selected' : '' ?>
                        >
                            <?= bookingFormEscape(
                                $room['room_code'] . ' - ' . $room['room_name']
                            ) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label for="title">Tên nội dung đặt phòng</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="200"
                    placeholder="Ví dụ: Học thực hành PHP"
                    value="<?= bookingFormEscape($old['title'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="booking_date">Ngày sử dụng</label>

                <input
                    type="date"
                    id="booking_date"
                    name="booking_date"
                    min="<?= date('Y-m-d') ?>"
                    value="<?= bookingFormEscape($old['booking_date'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-row">

                <div class="form-group">
                    <label for="start_time">Giờ bắt đầu</label>

                    <input
                        type="time"
                        id="start_time"
                        name="start_time"
                        value="<?= bookingFormEscape($old['start_time'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="end_time">Giờ kết thúc</label>

                    <input
                        type="time"
                        id="end_time"
                        name="end_time"
                        value="<?= bookingFormEscape($old['end_time'] ?? '') ?>"
                        required
                    >
                </div>

            </div>

            <div class="form-group">
                <label for="purpose">Mục đích sử dụng</label>

                <textarea
                    id="purpose"
                    name="purpose"
                    rows="4"
                    placeholder="Nhập mục đích sử dụng phòng..."
                    required
                ><?= bookingFormEscape($old['purpose'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <button type="reset" class="btn-secondary">
                    Nhập lại
                </button>

                <button type="submit" class="btn-primary">
                    <?= $isManager ? 'Tạo lịch đặt phòng' : 'Gửi yêu cầu đặt phòng' ?>
                </button>
            </div>

        </form>
    </section>

</main>

<?php if ($isManager): ?>
<script>
    const bookingType = document.getElementById('booking_type');
    const teacherSelector = document.getElementById('teacher-selector');
    const teacherSelect = document.getElementById('teacher_id');

    function updateTeacherSelector() {
        const isOnBehalf = bookingType.value === 'on_behalf';

        teacherSelector.style.display = isOnBehalf ? 'block' : 'none';
        teacherSelect.required = isOnBehalf;

        if (!isOnBehalf) {
            teacherSelect.value = '';
        }
    }

    bookingType.addEventListener('change', updateTeacherSelector);
    updateTeacherSelector();
</script>
<?php endif; ?>

</body>
</html>
