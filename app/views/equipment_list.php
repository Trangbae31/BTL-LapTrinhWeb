<?php
require_once __DIR__ . '/../core/CSRF.php';

function equipmentEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$statusLabels = [
    'available' => 'Sẵn sàng',
    'maintenance' => 'Đang bảo trì',
    'broken' => 'Bị hỏng',
    'retired' => 'Đã thanh lý'
];

$canManage = $canManage ?? false;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý thiết bị - Smart Campus</title>
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
        min-width: 900px;
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

    .status-available { background: #E2F3E7; color: #287A45; }
    .status-maintenance { background: #FFF1D6; color: #986500; }
    .status-broken { background: #FBE8E7; color: #A12E2A; }
    .status-retired { background: #EEE8E4; color: #76665D; }

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
        <h1 class="page-title">Quản lý thiết bị</h1>
        <p class="page-desc">Theo dõi thông tin, tình trạng và phân bổ thiết bị phòng thực hành.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= equipmentEscape($flash['type'] ?? 'success') ?>">
            <?= equipmentEscape($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <!-- FILTER CARD -->
    <div class="filter-card">
        <form class="filter-form" method="get" action="index.php">
            <input type="hidden" name="action" value="equipment">
            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Tìm mã, tên, hãng, phòng..."
                value="<?= equipmentEscape($_GET['keyword'] ?? '') ?>"
            >
            <button class="btn btn-primary" type="submit">Tìm kiếm</button>
            <?php if (!empty($_GET['keyword'])): ?>
                <a class="btn btn-secondary" href="index.php?action=equipment">Xóa</a>
            <?php endif; ?>
        </form>

        <?php if ($canManage): ?>
            <a class="btn btn-primary" href="index.php?action=equipment_form">+ Thêm thiết bị</a>
        <?php endif; ?>
    </div>

    <!-- DATA TABLE CARD -->
    <div class="data-card">
        <div class="data-card-header">
            <h3 class="data-card-title">Danh sách thiết bị</h3>
            <p class="data-card-subtitle">Tổng số thiết bị: <?= count($equipmentList ?? []) ?></p>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mã thiết bị</th>
                        <th>Tên thiết bị</th>
                        <th>Loại</th>
                        <th>Phòng</th>
                        <th>Hãng sản xuất</th>
                        <th>Ngày mua</th>
                        <th>Trạng thái</th>
                        <?php if ($canManage): ?>
                            <th>Thao tác</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($equipmentList)): ?>
                    <tr>
                        <td colspan="<?= $canManage ? 8 : 7 ?>" style="text-align: center; padding: 24px; color: var(--text-sub);">
                            Không tìm thấy thiết bị!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipmentList as $item): ?>
                        <?php 
                            $status = $item['status'] ?? 'available';
                            $statusClass = 'status-' . $status;
                        ?>
                        <tr>
                            <td><strong><?= equipmentEscape($item['equipment_code'] ?? '') ?></strong></td>
                            <td><?= equipmentEscape($item['equipment_name'] ?? '') ?></td>
                            <td><?= equipmentEscape($item['type_name'] ?? 'Chưa phân loại') ?></td>
                            <td>
                                <?= equipmentEscape(
                                    ($item['room_code'] ?? '') . ' - ' . ($item['room_name'] ?? 'Chưa phân phòng')
                                ) ?>
                            </td>
                            <td><?= equipmentEscape($item['brand'] ?? '') ?></td>
                            <td><?= equipmentEscape($item['purchase_date'] ?? '') ?></td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= equipmentEscape($statusLabels[$status] ?? $status) ?>
                                </span>
                            </td>
                            <?php if ($canManage): ?>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;" href="index.php?action=equipment_form&id=<?= (int)$item['equipment_id'] ?>">Sửa</a>
                                        <form method="post" action="index.php?action=equipment_delete" onsubmit="return confirm('Bạn có chắc muốn ngừng sử dụng thiết bị này?')">
                                            <input type="hidden" name="id" value="<?= (int)$item['equipment_id'] ?>">
                                            <?= CSRF::field() ?>
                                            <button class="btn btn-danger" style="padding: 6px 12px; font-size: 13px;" type="submit">Ngừng</button>
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