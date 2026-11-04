<?php

require_once __DIR__ . '/../core/CSRF.php';

function roomEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$canManage = Authorization::hasAnyRole(['admin', 'manager']);

$flash =$_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý phòng thực hành - Smart Campus</title>
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
            max-width: 1200px;
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

        .filter-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            max-width: 600px;
        }

        .data-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }

        .data-card-header {
            margin-bottom: 16px;
        }

        .data-card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            margin: 0 0 4px 0;
        }

        .data-card-subtitle {
            font-size: 13px;
            color: var(--text-sub);
            margin: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid #f2f4f3;
            text-align: left;
            font-size: 14px;
            vertical-align: middle;
        }

        th {
            background: #f8faf9;
            color: #6c757d;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr:hover {
            background-color: #fbfdfc;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #E8F5EE;
            color: #216B42;
        }

        .status-inactive {
            background: #FDF2F1;
            color: var(--danger-color);
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }

        .flash {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
        }

        .flash.success {
            background: #E8F5EE;
            color: #216B42;
            border: 1px solid #C9E9D2;
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
        <div class="brand-icon">🏫</div>
        <div>
            <h2 class="brand-title">Smart Campus</h2>
            <p class="brand-subtitle">Quản lý phòng thực hành</p>
        </div>
    </div>
    <a href="index.php?action=dashboard" class="nav-back">← Quay lại Dashboard</a>
</div>

<div class="main-container">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <h1 class="page-title">Quản lý phòng thực hành</h1>
        <p class="page-desc">Danh sách phòng thực hành thuộc hệ thống Smart Campus.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= roomEscape($flash['type'] ?? 'success') ?>">
            <?= roomEscape($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <!-- FILTER CARD -->
    <div class="filter-card">
        <form class="filter-form" method="get" action="index.php">
            <input type="hidden" name="action" value="rooms">
            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Tìm theo mã phòng, tên phòng, vị trí..."
                value="<?= roomEscape($_GET['keyword'] ?? '') ?>"
            >
            <button class="btn btn-primary" type="submit">Tìm kiếm</button>
            <?php if (!empty($_GET['keyword'])): ?>
                <a class="btn btn-secondary" href="index.php?action=rooms">Xóa</a>
            <?php endif; ?>
        </form>

        <?php if ($canManage): ?>
            <a class="btn btn-primary" href="index.php?action=room_form">+ Thêm phòng mới</a>
        <?php endif; ?>
    </div>

    <!-- DATA TABLE CARD -->
    <div class="data-card">
        <div class="data-card-header">
            <h3 class="data-card-title">Danh sách phòng thực hành</h3>
            <p class="data-card-subtitle">Tổng số phòng: <?= count($rooms ?? []) ?></p>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mã phòng</th>
                        <th>Tên phòng</th>
                        <th>Tòa nhà</th>
                        <th>Sức chứa</th>
                        <th>Vị trí</th>
                        <th>Trạng thái</th>
                        <?php if ($canManage): ?>
                            <th>Thao tác</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($rooms)): ?>
                    <tr>
                        <td colspan="<?= $canManage ? 7 : 6 ?>" style="text-align: center; padding: 24px; color: var(--text-sub);">
                            Không tìm thấy phòng thực hành phù hợp.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rooms as$room): ?>
                        <tr>
                            <td><strong><?= roomEscape($room['room_code'] ?? '') ?></strong></td>
                            <td><?= roomEscape($room['room_name'] ?? '') ?></td>
                            <td><?= roomEscape($room['building_name'] ?? 'Chưa phân tòa') ?></td>
                            <td><?= roomEscape($room['capacity'] ?? '') ?> người</td>
                            <td><?= roomEscape($room['location'] ?? '') ?></td>
                            <td>
                                <?php if (($room['status'] ?? '') === 'active'): ?>
                                    <span class="status-badge status-active">Đang hoạt động</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Ngừng hoạt động</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canManage): ?>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;" href="index.php?action=room_form&id=<?= (int)$room['room_id'] ?>">Sửa</a>
                                        <form method="post" action="index.php?action=room_delete" onsubmit="return confirm('Bạn có chắc muốn xóa phòng này?')">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$room['room_id'] ?>">
                                            <button class="btn btn-danger" style="padding: 6px 12px; font-size: 13px;" type="submit">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer-text">
        Quản lý phòng thực hành và thiết bị
    </div>

</div>

</body>
</html>