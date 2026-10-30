<?php
$startDate = date('Y-m-d', strtotime($booking['start_time']));
$startTime = date('H:i', strtotime($booking['start_time']));
$endTime = date('H:i', strtotime($booking['end_time']));
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
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php?action=booking_update&id=<?= (int) $booking['booking_id'] ?>">
                <div class="form-group">
                    <label for="room_id">Phòng thực hành</label>
                    <select id="room_id" name="room_id" required>
                        <option value="">-- Chọn phòng --</option>

                        <?php foreach ($rooms as $room): ?>
                            <option
                                value="<?= (int) $room['room_id'] ?>"
                                <?= (int) $room['room_id'] === (int) $booking['room_id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $room['room_code'] . ' - ' . $room['room_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
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
                        value="<?= htmlspecialchars($booking['title'], ENT_QUOTES, 'UTF-8') ?>"
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
                        value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?>"
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
                            value="<?= htmlspecialchars($startTime, ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="end_time">Giờ kết thúc</label>
                        <input
                            type="time"
                            id="end_time"
                            name="end_time"
                            value="<?= htmlspecialchars($endTime, ENT_QUOTES, 'UTF-8') ?>"
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
                    ><?= htmlspecialchars($booking['purpose'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="form-actions">
                    <a href="index.php?action=calendar" class="btn-secondary">
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