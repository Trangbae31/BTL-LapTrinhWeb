<?php
require_once __DIR__ . '/../core/CSRF.php';

function equipmentTypeEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
$flash = $flash ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý loại thiết bị - Smart Campus</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
body{background:#f5f7f5}.type-container{max-width:1100px;margin:28px auto;padding:0 20px 40px}
.type-header,.type-card{background:#fff;border-radius:12px;padding:22px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.03)}
.type-header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.type-header h1{margin:0 0 6px;color:#23352d;font-size:25px}.muted{color:#78867e;font-size:14px;margin:0}
.type-form{display:grid;gap:12px}.type-form input,.type-form textarea{width:100%;padding:11px 12px;border:1px solid #dce5df;border-radius:8px;font:inherit}
.type-table{width:100%;border-collapse:collapse;min-width:650px}.type-table th,.type-table td{padding:13px 14px;border-bottom:1px solid #edf1ee;text-align:left;font-size:14px}.type-table th{background:#f8faf9;color:#66736c;font-size:12px;text-transform:uppercase}
.type-actions{display:flex;gap:8px;align-items:center}.type-actions form{margin:0}.flash{padding:12px 16px;border-radius:8px;margin-bottom:18px}.flash.success{background:#e8f5ee;color:#216b42}.flash.error{background:#fdf2f1;color:#a12e2a}
@media(max-width:650px){.type-header{align-items:flex-start}.type-table{min-width:600px}}
</style>
</head>
<body>
<div class="type-container">
    <div class="type-header">
        <div>
            <h1>Quản lý loại thiết bị</h1>
            <p class="muted">Danh mục loại thiết bị thuộc chức năng Quản lý phòng và thiết bị.</p>
        </div>
        <a class="btn btn-secondary" href="index.php?action=equipment">← Quay lại quản lý thiết bị</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= equipmentTypeEscape($flash['type'] ?? 'success') ?>">
            <?= equipmentTypeEscape($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <div class="type-card">
        <h3><?= !empty($editType) ? 'Cập nhật loại thiết bị' : 'Thêm loại thiết bị mới' ?></h3>
        <form class="type-form" method="post" action="index.php?action=<?= !empty($editType) ? 'equipment_type_update' : 'equipment_type_store' ?>">
            <?php if (!empty($editType)): ?>
                <input type="hidden" name="equipment_type_id" value="<?= (int)$editType['equipment_type_id'] ?>">
            <?php endif; ?>
            <input type="text" name="type_name" maxlength="100" required placeholder="Tên loại thiết bị"
                   value="<?= equipmentTypeEscape($editType['type_name'] ?? '') ?>">
            <textarea name="description" rows="3" placeholder="Mô tả loại thiết bị (không bắt buộc)"><?= equipmentTypeEscape($editType['description'] ?? '') ?></textarea>
            <?= CSRF::field() ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn btn-primary" type="submit"><?= !empty($editType) ? 'Lưu thay đổi' : 'Thêm loại thiết bị' ?></button>
                <?php if (!empty($editType)): ?>
                    <a class="btn btn-secondary" href="index.php?action=equipment_type">Hủy sửa</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="type-card">
        <h3>Danh sách loại thiết bị (<?= count($equipmentTypes ?? []) ?>)</h3>
        <div style="overflow-x:auto;">
            <table class="type-table">
                <thead><tr><th>Tên loại</th><th>Mô tả</th><th>Số thiết bị</th><th>Thao tác</th></tr></thead>
                <tbody>
                <?php if (empty($equipmentTypes)): ?>
                    <tr><td colspan="4" style="text-align:center;">Chưa có loại thiết bị.</td></tr>
                <?php else: ?>
                    <?php foreach ($equipmentTypes as $type): ?>
                        <tr>
                            <td><strong><?= equipmentTypeEscape($type['type_name']) ?></strong></td>
                            <td><?= nl2br(equipmentTypeEscape($type['description'] ?? '')) ?></td>
                            <td><?= (int)$type['equipment_count'] ?></td>
                            <td>
                                <div class="type-actions">
                                    <a class="btn btn-secondary" href="index.php?action=equipment_type&id=<?= (int)$type['equipment_type_id'] ?>">Sửa</a>
                                    <form method="post" action="index.php?action=equipment_type_delete" onsubmit="return confirm('Xóa loại thiết bị này? Chỉ xóa được khi chưa có thiết bị sử dụng.')">
                                        <input type="hidden" name="equipment_type_id" value="<?= (int)$type['equipment_type_id'] ?>">
                                        <?= CSRF::field() ?>
                                        <button class="btn btn-danger" type="submit">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
