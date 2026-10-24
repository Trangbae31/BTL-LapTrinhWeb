
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch phòng thực hành</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="page-container">
        <header class="page-header">
            <div>
                <p class="page-label">SMART CAMPUS</p>
                <h1>Lịch phòng thực hành</h1>
                <p class="page-description">
                    Theo dõi lịch sử dụng và kiểm tra tình trạng phòng.
                </p>
            </div>

            <a href="index.php?action=booking_create" class="btn-primary">
                + Đặt phòng
            </a>
        </header>

        <section class="content-card">
            <h2>Danh sách lịch phòng</h2>

            <?php if (empty($bookings)): ?>
                <p>Chưa có lịch đặt phòng nào.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Người đặt</th>
                                <th>Mã phòng</th>
                                <th>Tên phòng</th>
                                <th>Nội dung</th>
                                <th>Bắt đầu</th>
                                <th>Kết thúc</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $index => $booking): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <?= htmlspecialchars($booking['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($booking['room_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($booking['room_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($booking['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($booking['start_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($booking['end_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($booking['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
