<?php
require_once __DIR__ . '/../core/CSRF.php';
function userEscape($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
$flash = $flash ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý tài khoản - Smart Campus</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
body{background:#f5f7f5}.users-wrap{max-width:1180px;margin:28px auto;padding:0 20px 40px}.panel{background:#fff;border-radius:12px;padding:22px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.03)}.head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.head h1{margin:0 0 5px;font-size:25px;color:#23352d}.muted{margin:0;color:#78867e;font-size:14px}.user-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;align-items:end}.user-form label{display:block;font-size:13px;margin-bottom:6px;color:#53635a}.user-form input,.user-form select{width:100%;padding:10px 11px;border:1px solid #dce5df;border-radius:8px;font:inherit}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:760px}th,td{padding:13px 14px;border-bottom:1px solid #edf1ee;text-align:left;font-size:14px}th{background:#f8faf9;color:#66736c;font-size:12px;text-transform:uppercase}.flash{padding:12px 16px;border-radius:8px;margin-bottom:18px}.flash.success{background:#e8f5ee;color:#216b42}.flash.error{background:#fdf2f1;color:#a12e2a}.actions{display:flex;gap:8px;align-items:center}.status{font-weight:600}.active{color:#287a45}.inactive{color:#a12e2a}
</style>
</head>
<body>
<div class="users-wrap">
    <div class="panel head">
        <div><h1>Quản lý tài khoản người dùng</h1><p class="muted">Chỉ Admin được tạo tài khoản, gán vai trò và khóa/mở khóa tài khoản.</p></div>
        <a class="btn btn-secondary" href="index.php?action=dashboard">← Dashboard</a>
    </div>
    <?php if ($flash): ?><div class="flash <?= userEscape($flash['type'] ?? 'success') ?>"><?= userEscape($flash['message'] ?? '') ?></div><?php endif; ?>

    <div class="panel">
        <h3><?= !empty($editUser) ? 'Cập nhật tài khoản' : 'Tạo tài khoản mới' ?></h3>
        <form class="user-form" method="post" action="index.php?action=<?= !empty($editUser) ? 'user_update' : 'user_store' ?>">
            <?php if (!empty($editUser)): ?><input type="hidden" name="user_id" value="<?= (int)$editUser['user_id'] ?>"><?php endif; ?>
            <div><label>Họ và tên</label><input name="full_name" maxlength="100" required value="<?= userEscape($editUser['full_name'] ?? '') ?>"></div>
            <div><label>Email</label><input type="email" name="email" required value="<?= userEscape($editUser['email'] ?? '') ?>"></div>
            <div><label><?= !empty($editUser) ? 'Mật khẩu mới (để trống nếu giữ nguyên)' : 'Mật khẩu (tối thiểu 8 ký tự)' ?></label><input type="password" name="password" <?= empty($editUser) ? 'required minlength="8"' : 'minlength="8"' ?> autocomplete="new-password"></div>
            <div><label>Vai trò (có thể chọn nhiều)</label><select name="role_ids[]" multiple required size="4">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int)$role['role_id'] ?>" <?= in_array((int)$role['role_id'], $editUser['role_ids'] ?? [], true) ? 'selected' : '' ?>><?= userEscape(ucfirst($role['role_name'])) ?></option>
                <?php endforeach; ?>
            </select></div>
            <div><label>Trạng thái</label><select name="status"><option value="active" <?= ($editUser['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Hoạt động</option><option value="inactive" <?= ($editUser['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Đã khóa</option></select></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?= CSRF::field() ?>
                <button class="btn btn-primary" type="submit"><?= !empty($editUser) ? 'Lưu thay đổi' : 'Tạo tài khoản' ?></button>
                <?php if (!empty($editUser)): ?><a class="btn btn-secondary" href="index.php?action=users">Hủy</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="panel">
        <h3>Danh sách tài khoản (<?= count($users ?? []) ?>)</h3>
        <div class="table-wrap"><table>
            <thead><tr><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php if (empty($users)): ?><tr><td colspan="6" style="text-align:center;">Chưa có tài khoản.</td></tr>
            <?php else: foreach ($users as $user): ?>
                <tr>
                    <td><strong><?= userEscape($user['full_name']) ?></strong></td>
                    <td><?= userEscape($user['email']) ?></td>
                    <td><?= userEscape($user['role_names'] ?: 'Chưa phân quyền') ?></td>
                    <td><span class="status <?= $user['status'] === 'active' ? 'active' : 'inactive' ?>"><?= $user['status'] === 'active' ? 'Hoạt động' : 'Đã khóa' ?></span></td>
                    <td><?= userEscape($user['created_at']) ?></td>
                    <td><a class="btn btn-secondary" href="index.php?action=users&id=<?= (int)$user['user_id'] ?>">Sửa</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table></div>
    </div>
</div>
</body>
</html>
