<?php
function bookingFormEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$errors = $errors ?? [];
$old = $_POST ?? [];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt phòng thực hành</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
<main class="page-container">

    <header class="page-header">
        <div>
            <p class="page-label">SMART CAMPUS</p>
            <h1>Đặt phòng thực hành</h1>
            <p class="page-description">
                Điền thông tin để gửi yêu cầu đặt phòng.
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
                <strong>Không thể gửi yêu cầu:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= bookingFormEscape($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="index.php?action=booking_store">

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
                    Gửi yêu cầu đặt phòng
                </button>
            </div>

        </form>
    </section>

</main>
</body>
</html>