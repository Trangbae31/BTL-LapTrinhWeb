
<?php
$currentUserId = (int) ($_SESSION['user']['user_id'] ?? 0);
$keyword = $keyword ?? ($_GET['keyword'] ?? '');

$statusLabels = [
    'pending' => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Từ chối',
    'cancelled' => 'Đã hủy'
];
?>

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

            <form method="get" action="index.php" class="search-form">
                <input type="hidden" name="action" value="calendar">

                <input
                    type="text"
                    name="keyword"
                    placeholder="Tìm theo phòng, nội dung hoặc người đặt..."
                    value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                >

                <button type="submit" class="btn-primary">Tìm kiếm</button>

                <a href="index.php?action=calendar" class="btn-secondary">
                    Làm mới
                </a>
            </form>

            <?php if (empty($bookings)): ?>
                <p>Không tìm thấy lịch đặt phòng nào.</p>
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
                                <th>Thao tác</th>
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
                                        <?= htmlspecialchars(
                                            $statusLabels[$booking['status']] ?? $booking['status'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?php if (
                                            (int) ($booking['user_id'] ?? 0) === $currentUserId
                                            && ($booking['status'] ?? '') === 'pending'
                                        ): ?>
                                            <a
                                                href="index.php?action=booking_edit&id=<?= (int) $booking['booking_id'] ?>"
                                                class="btn-secondary"
                                            >
                                                Sửa
                                            </a>

                                            <form
                                                method="post"
                                                action="index.php?action=booking_cancel&id=<?= (int) $booking['booking_id'] ?>"
                                                onsubmit="return confirm('Bạn có chắc chắn muốn hủy yêu cầu đặt phòng này?');"
                                            >
                                                <button type="submit" class="btn-secondary">
                                                    Hủy
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span>—</span>
                                        <?php endif; ?>
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
