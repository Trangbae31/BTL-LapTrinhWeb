<?php

function bookingEditEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$booking = $booking ?? [];
$rooms = $rooms ?? [];
$errors = $errors ?? [];

$bookingId = (int)($booking['booking_id'] ?? $_GET['id'] ?? 0);

$startDate = !empty($booking['start_time'])
    ? date('Y-m-d', strtotime($booking['start_time']))
    : '';

$startTime = !empty($booking['start_time'])
    ? date('H:i', strtotime($booking['start_time']))
    : '';

$endTime = !empty($booking['end_time'])
    ? date('H:i', strtotime($booking['end_time']))
    : '';

$old = $_POST ?? [];

$selectedRoom = $old['room_id'] ?? $booking['room_id'] ?? '';
$title = $old['title'] ?? $booking['title'] ?? '';
$bookingDate = $old['booking_date'] ?? $startDate;
$inputStartTime = $old['start_time'] ?? $startTime;
$inputEndTime = $old['end_time'] ?? $endTime;
$purpose = $old['purpose'] ?? $booking['purpose'] ?? '';

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sửa thông tin đặt phòng</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<main class="page-container">

    <header class="page-header">

        <div>
            <p class="page-label">SMART CAMPUS</p>

            <h1>Sửa thông tin đặt phòng</h1>

            <p class="page-description">
                Cập nhật thông tin yêu cầu đặt phòng của bạn.
            </p>
        </div>

        <a href="index.php?action=calendar" class="btn-secondary">
            Quay lại lịch phòng
        </a>

    </header>

    <section class="content-card">

        <h2>Thông tin đặt phòng</h2>

        <?php if (!empty($errors)): ?>

            <div class="error-message" role="alert">

                <?php foreach ($errors as $error): ?>

                    <p><?= bookingEditEscape($error) ?></p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <form
            method="post"
            action="index.php?action=booking_update&id=<?= $bookingId ?>"
        >

            <div class="form-group">

                <label for="room_id">Phòng thực hành</label>

                <select id="room_id" name="room_id" required>

                    <option value="">-- Chọn phòng --</option>

                    <?php foreach ($rooms as $room): ?>

                        <option
                            value="<?= (int)$room['room_id'] ?>"
                            <?= (string)$room['room_id'] ===
                                (string)$selectedRoom ? 'selected' : '' ?>
                        >
                            <?= bookingEditEscape(
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
                    value="<?= bookingEditEscape($title) ?>"
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
                    value="<?= bookingEditEscape($bookingDate) ?>"
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
                        value="<?= bookingEditEscape($inputStartTime) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="end_time">Giờ kết thúc</label>

                    <input
                        type="time"
                        id="end_time"
                        name="end_time"
                        value="<?= bookingEditEscape($inputEndTime) ?>"
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
                    required
                ><?= bookingEditEscape($purpose) ?></textarea>

            </div>

            <div class="form-actions">

                <a
                    href="index.php?action=calendar"
                    class="btn-secondary"
                >
                    Hủy
                </a>

                <button type="submit" class="btn-primary">
                    Lưu thay đổi
                </button>

            </div>

        </form>

    </section>

</main>

</body>
</html>