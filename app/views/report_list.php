
<?php
require_once __DIR__ . '/../core/CSRF.php';
// ==========================================
// XÁC ĐỊNH QUYỀN NGƯỜI DÙNG
// ==========================================

$isManager = $isManager ?? false;
$isTechnician = $isTechnician ?? false;
$isTeacher = $isTeacher ?? false;
$isAdmin = $isAdmin ?? false;

if ($isAdmin) {
    // Admin kế thừa toàn bộ thao tác nghiệp vụ của Manager.
    $isManager = true;
    $isTechnician = false;
    $isTeacher = false;
} elseif ($isTechnician) {
    $isManager = false;
    $isTeacher = false;
}

$reports = $reports ?? [];
$technicians = $technicians ?? [];

$currentUserId = (int)(
    $_SESSION['user']['user_id']
    ?? $_SESSION['user']['id']
    ?? 0
);

// Kỹ thuật viên chỉ xem phiếu được giao cho mình
if ($isTechnician) {
    $reports = array_values(array_filter(
        $reports,
        function ($report) use ($currentUserId) {
            return (int)($report['assigned_to'] ?? 0) === $currentUserId;
        }
    ));
}

// ==========================================
// NHÃN TRẠNG THÁI
// ==========================================

$statusLabels = [
    'reported' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'resolved' => 'Đã hoàn thành',
    'cancelled' => 'Đã hủy'
];

// ==========================================
// HÀM LÀM SẠCH DỮ LIỆU
// ==========================================

function reportEscape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý báo hỏng - Smart Campus</title>

    <style>
        :root {
            --green: #176b4d;
            --green-dark: #104d39;
            --green-light: #e8f5ee;
            --bg: #f5f7f5;
            --text: #24372e;
            --muted: #78867e;
            --border: #e5ebe6;
            --white: #fff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a {
            text-decoration: none;
        }

        .topbar {
            min-height: 76px;
            padding: 15px 35px;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: var(--green-light);
            font-size: 23px;
        }

        .brand-name {
            color: var(--green-dark);
            font-size: 18px;
            font-weight: 750;
        }

        .brand-sub {
            margin-top: 4px;
            color: var(--muted);
            font-size: 11px;
        }

        .back-link {
            color: var(--green);
            font-size: 13px;
            font-weight: 650;
        }

        .container {
            max-width: 1500px;
            padding: 32px;
            margin: auto;
        }

        .page-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: 27px;
            margin: 0;
        }

        .page-heading p {
            margin: 9px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 8px;
            padding: 10px 13px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--green);
            color: white;
        }

        .btn-primary:hover {
            background: var(--green-dark);
        }

        .btn-edit {
            background: #eaf2ff;
            color: #285ca8;
        }

        .btn-edit:hover {
            background: #d8e7ff;
        }

        .btn-success {
            background: #e5f6eb;
            color: #247548;
        }

        .btn-success:hover {
            background: #d2efdc;
        }

        .summary-card {
            display: flex;
            align-items: center;
            gap: 17px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 21px 24px;
            margin-bottom: 24px;
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            background: #fff1df;
            font-size: 24px;
        }

        .summary-title {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .summary-number {
            font-size: 20px;
            font-weight: 750;
        }

        /* BỘ LỌC VÀ TÌM KIẾM PHIẾU */
        .filter-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 22px;
        }

        .filter-title {
            font-size: 15px;
            font-weight: 750;
            margin-bottom: 15px;
            color: var(--green-dark);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(150px, 1fr));
            gap: 13px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .filter-group label {
            font-size: 12px;
            font-weight: 650;
            color: #65766b;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            height: 40px;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: white;
            color: var(--text);
            font-family: inherit;
            font-size: 12px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: 2px solid var(--green-light);
            border-color: var(--green);
        }

        .filter-actions {
            display: flex;
            gap: 9px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .btn-reset {
            background: #edf1ee;
            color: #53645a;
        }

        .btn-reset:hover {
            background: #e0e8e2;
        }

        .filter-result {
            margin-top: 13px;
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 900px) {
            .filter-grid {
                grid-template-columns: repeat(2, minmax(140px, 1fr));
            }
        }

        @media (max-width: 500px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        .table-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 15px;
            overflow: hidden;
        }

        .table-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
            gap: 15px;
        }

        .table-heading h2 {
            font-size: 17px;
            margin: 0;
        }

        .table-heading p {
            font-size: 12px;
            color: var(--muted);
            margin: 6px 0 0;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: <?= $isAdmin ? '1100px' : '1250px' ?>;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: #f5f8f6;
        }

        th {
            color: #65766b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            padding: 15px 14px;
            white-space: nowrap;
        }

        td {
            padding: 16px 14px;
            border-top: 1px solid #edf0ed;
            font-size: 12px;
            line-height: 1.6;
            vertical-align: top;
        }

        tbody tr:hover {
            background: #f9fcfa;
        }

        .report-id {
            color: var(--green);
            font-weight: 750;
        }

        .equipment-name {
            font-weight: 650;
            min-width: 120px;
        }

        .description {
            max-width: 210px;
            min-width: 150px;
            color: #69776f;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            border-radius: 20px;
            padding: 6px 9px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            background: #edf1ee;
            color: #53645a;
        }

        .status.reported {
            background: #fff1df;
            color: #a66514;
        }

        .status.processing {
            background: #eaf2ff;
            color: #3566b3;
        }

        .status.resolved {
            background: #e5f6eb;
            color: #247548;
        }

        .status.cancelled {
            background: #ffebeb;
            color: #b13b3b;
        }

        .history-link {
            color: var(--green);
            font-weight: 700;
            white-space: nowrap;
        }

        .history-link:hover {
            text-decoration: underline;
        }

        .action-cell {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: stretch;
            min-width: 190px;
        }

        .action-section {
            display: flex;
            flex-direction: column;
            gap: 7px;
            padding-bottom: 10px;
            border-bottom: 1px dashed var(--border);
        }

        .action-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .action-title {
            color: var(--green-dark);
            font-size: 11px;
            font-weight: 750;
        }

        .action-form {
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 180px;
        }

        .action-form select,
        .action-form textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 9px;
            font-family: inherit;
            font-size: 12px;
            color: var(--text);
            background: white;
        }

        .action-form textarea {
            min-height: 58px;
            resize: vertical;
        }

        .action-form select:focus,
        .action-form textarea:focus {
            outline: 2px solid var(--green-light);
            border-color: var(--green);
        }

        .assigned-info {
            color: var(--muted);
            font-size: 11px;
            margin-top: 4px;
        }

        .empty-state {
            padding: 55px 20px;
            text-align: center;
            color: var(--muted);
        }

        .empty-icon {
            font-size: 42px;
            margin-bottom: 12px;
        }

        .empty-state strong {
            display: block;
            color: var(--text);
            font-size: 15px;
            margin-bottom: 7px;
        }

        .footer {
            text-align: center;
            padding: 25px 10px;
            color: #929d96;
            font-size: 11px;
        }

        .role-note {
            margin: 0 0 18px;
            padding: 13px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: white;
            color: #65766b;
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 700px) {
            .topbar {
                height: auto;
                padding: 17px;
            }

            .brand-name {
                font-size: 15px;
            }

            .container {
                padding: 22px 14px;
            }

            .page-heading {
                flex-direction: column;
            }

            .page-heading h1 {
                font-size: 23px;
            }

            .table-heading {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="brand">
        <div class="brand-icon">🛠️</div>
        <div>
            <div class="brand-name">Smart Campus</div>
            <div class="brand-sub">QUẢN LÝ BÁO HỎNG &amp; BẢO TRÌ</div>
        </div>
    </div>

    <a class="back-link" href="index.php?action=dashboard">
        ← Về trang chủ
    </a>
</header>

<main class="container">

    <section class="page-heading">
        <div>
            <h1>Quản lý báo hỏng</h1>
            <p>
                Theo dõi các sự cố thiết bị, trạng thái xử lý
                và lịch sử bảo trì trong phòng thực hành.
            </p>
        </div>

        <?php if ($isTeacher || $isManager): ?>
            <a href="index.php?action=report_form" class="btn btn-primary">
                <span>＋</span> Tạo phiếu báo hỏng
            </a>
        <?php endif; ?>
    </section>

    <?php if ($isManager): ?>

        <div class="role-note">
            <strong>Quyền quản lý bảo trì:</strong>
            Bạn có thể xem tất cả phiếu, tạo phiếu và phân công
            kỹ thuật viên xử lý sự cố.
        </div>

    <?php elseif ($isAdmin): ?>

        <div class="role-note">
            <strong>Quyền quản trị:</strong>
            Bạn có thể xem tất cả phiếu báo hỏng và lịch sử bảo trì.
            Bạn không có quyền chỉnh sửa hoặc xử lý phiếu tại chức năng này.
        </div>

    <?php elseif ($isTechnician): ?>

        <div class="role-note">
            <strong>Quyền kỹ thuật viên:</strong>
            Bạn chỉ được xem và xử lý các phiếu báo hỏng
            được phân công cho tài khoản của mình.
        </div>

    <?php elseif ($isTeacher): ?>

        <div class="role-note">
            <strong>Quyền giảng viên:</strong>
            Bạn có thể tạo phiếu báo hỏng và theo dõi tình trạng xử lý
            các phiếu do mình tạo.
        </div>

    <?php endif; ?>
    <?php
    $filters = $filters ?? [];

    $filterKeyword = $filters['keyword'] ?? '';
    $filterStatus = $filters['status'] ?? '';
    $filterReporter = (int)($filters['reporter_id'] ?? 0);
    $filterTechnician = (int)($filters['technician_id'] ?? 0);
    ?>

    <section class="filter-card">

        <div class="filter-title">
            🔎 Tìm kiếm và lọc phiếu báo hỏng
        </div>

        <form method="GET" action="index.php">

            <input type="hidden" name="action" value="report_list">

            <div class="filter-grid">

                <!-- Tìm kiếm -->
                <div class="filter-group">
                    <label for="filter-keyword">
                        Từ khóa tìm kiếm
                    </label>

                    <input
                        type="text"
                        name="keyword"
                        placeholder="Mã phiếu, mã thiết bị, tên thiết bị..."
                        value="<?= reportEscape($filterKeyword) ?>">
                </div>

                <!-- Trạng thái -->
                <div class="filter-group">
                    <label for="filter-status">
                        Trạng thái phiếu
                    </label>

                    <select id="filter-status" name="status">

                        <option value="">Tất cả trạng thái</option>

                        <option value="reported"
                            <?= $filterStatus === 'reported' ? 'selected' : '' ?>>
                            Chờ xử lý
                        </option>

                        <option value="processing"
                            <?= $filterStatus === 'processing' ? 'selected' : '' ?>>
                            Đang xử lý
                        </option>

                        <option value="resolved"
                            <?= $filterStatus === 'resolved' ? 'selected' : '' ?>>
                            Đã hoàn thành
                        </option>

                        <option value="cancelled"
                            <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>
                            Đã hủy
                        </option>

                    </select>
                </div>

                <?php if ($isManager): ?>

                    <!-- Lọc người báo -->
                    <div class="filter-group">
                        <label for="filter-reporter">
                            Người báo hỏng
                        </label>

                        <select id="filter-reporter" name="reporter_id">

                            <option value="0">
                                Tất cả người báo
                            </option>

                            <?php foreach ($reporters as $reporter): ?>

                                <option
                                    value="<?= (int)$reporter['user_id'] ?>"
                                    <?= $filterReporter === (int)$reporter['user_id']
                                        ? 'selected'
                                        : '' ?>>

                                    <?= reportEscape($reporter['full_name']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <!-- Lọc kỹ thuật viên -->
                    <div class="filter-group">
                        <label for="filter-technician">
                            Kỹ thuật viên
                        </label>

                        <select id="filter-technician" name="technician_id">

                            <option value="0">
                                Tất cả kỹ thuật viên
                            </option>

                            <option value="-1"
                                <?= $filterTechnician === -1 ? 'selected' : '' ?>>
                                Chưa phân công
                            </option>

                            <?php foreach ($technicians as $technician): ?>

                                <option
                                    value="<?= (int)$technician['user_id'] ?>"
                                    <?= $filterTechnician === (int)$technician['user_id']
                                        ? 'selected'
                                        : '' ?>>

                                    <?= reportEscape($technician['full_name']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                <?php endif; ?>

            </div>

            <div class="filter-actions">

                <button type="submit" class="btn btn-primary">
                    🔎 Tìm kiếm
                </button>

                <a
                    href="index.php?action=report_list"
                    class="btn btn-reset">
                    ↺ Xóa bộ lọc
                </a>

            </div>

            <div class="filter-result">
                Đang hiển thị
                <strong><?= count($reports) ?></strong>
                phiếu phù hợp với điều kiện tìm kiếm.
            </div>

        </form>

    </section>

    <section class="summary-card">
        <div class="summary-icon">📋</div>
        <div>
            <div class="summary-title">Tổng số phiếu đang hiển thị</div>
            <div class="summary-number">
                <?= count($reports) ?>
                <span style="font-size:12px;font-weight:400;color:#78867e">
                    phiếu báo hỏng
                </span>
            </div>
        </div>
    </section>

    <section class="table-card">
        <div class="table-heading">
            <div>
                <h2>Danh sách phiếu báo hỏng</h2>
                <p>Thông tin sự cố và lịch sử xử lý thiết bị</p>
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Mã thiết bị</th>
                        <th>Tên thiết bị</th>
                        <th>Tiêu đề</th>
                        <th>Mô tả</th>
                        <th>Người báo</th>
                        <th>Người được giao</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th>Lịch sử</th>

                        <?php if (!$isAdmin || $isManager): ?>
                            <th>Thao tác</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($reports)): ?>

                        <tr>
                            <td colspan="11">
                                <div class="empty-state">
                                    <div class="empty-icon">📭</div>

                                    <strong>
                                        <?= $isTechnician
                                            ? 'Chưa có phiếu nào được giao cho bạn'
                                            : 'Chưa có phiếu báo hỏng' ?>
                                    </strong>

                                    <div>
                                        <?= $isTechnician
                                            ? 'Các phiếu được phân công cho bạn sẽ hiển thị tại đây.'
                                            : 'Hiện chưa có dữ liệu để hiển thị.' ?>
                                    </div>
                                </div>
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($reports as $report): ?>

                            <?php
                                $reportId = (int)($report['report_id'] ?? 0);

                                $status = (string)($report['status'] ?? '');

                                $statusLabel = $statusLabels[$status] ?? $status;

                                $assignedName = $report['technician_name'] ?? '';

                                $assignedId = (int)($report['assigned_to'] ?? 0);

                                $isFinished = in_array(
                                    $status,
                                    ['resolved', 'cancelled'],
                                    true
                                );

                                $isAssignedToMe = (
                                    $assignedId > 0 &&
                                    $assignedId === $currentUserId
                                );

                                $canTechnicianUpdate = (
                                    $isTechnician &&
                                    $isAssignedToMe &&
                                    !$isFinished
                                );
                            ?>

                            <tr>
                                <td class="report-id">
                                    #<?= $reportId ?>
                                </td>

                                <td>
                                    <?= reportEscape($report['equipment_code'] ?? '') ?>
                                </td>

                                <td class="equipment-name">
                                    <?= reportEscape($report['equipment_name'] ?? '') ?>
                                </td>

                                <td>
                                    <?= reportEscape($report['title'] ?? '') ?>
                                </td>

                                <td class="description">
                                    <?= reportEscape($report['description'] ?? '') ?>
                                </td>

                                <td>
                                    <?= reportEscape($report['reporter_name'] ?? 'Không rõ') ?>
                                </td>

                                <td>
                                    <?php if ($assignedName !== ''): ?>
                                        <?= reportEscape($assignedName) ?>
                                    <?php else: ?>
                                        <span style="color:#a66514">
                                            Chưa phân công
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="status <?= reportEscape($status) ?>">
                                        <?= reportEscape($statusLabel) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= reportEscape($report['reported_at'] ?? '') ?>
                                </td>

                                <td>
                                    <a
                                        class="history-link"
                                        href="index.php?action=report_history&amp;id=<?= $reportId ?>">
                                        Xem lịch sử →
                                    </a>
                                </td>

                                <?php if (!$isAdmin || $isManager): ?>

                                    <td>
                                        <div class="action-cell">

                                            <?php if ($isManager): ?>

                                                <!-- CHỨC NĂNG DÀNH CHO MANAGER -->

                                                <div class="action-section">

                                                    <div class="action-title">
                                                        PHÂN CÔNG KỸ THUẬT VIÊN
                                                    </div>

                                                    <?php if (!$isFinished): ?>

                                                        <form
                                                            class="action-form"
                                                            method="POST"
                                                            action="index.php?action=report_assign">
                                                            <?= CSRF::field() ?>

                                                            <input
                                                                type="hidden"
                                                                name="report_id"
                                                                value="<?= $reportId ?>">

                                                            <select
                                                                name="technician_id"
                                                                required>

                                                                <option value="">
                                                                    -- Chọn kỹ thuật viên --
                                                                </option>

                                                                <?php foreach ($technicians as $technician): ?>

                                                                    <option
                                                                        value="<?= (int)$technician['user_id'] ?>"
                                                                        <?= $assignedId === (int)$technician['user_id']
                                                                            ? 'selected'
                                                                            : '' ?>>

                                                                        <?= reportEscape($technician['full_name']) ?>

                                                                    </option>

                                                                <?php endforeach; ?>

                                                            </select>

                                                            <button
                                                                type="submit"
                                                                class="btn btn-primary">
                                                                Phân công
                                                            </button>

                                                        </form>

                                                    <?php else: ?>

                                                        <span class="assigned-info">
                                                            Phiếu đã kết thúc, không thể phân công lại.
                                                        </span>

                                                    <?php endif; ?>

                                                </div>

                                            <?php elseif ($isTechnician): ?>

                                                <!-- CHỨC NĂNG DÀNH CHO TECHNICIAN -->

                                                <?php if ($canTechnicianUpdate): ?>

                                                    <div class="action-section">

                                                        <div class="action-title">
                                                            XỬ LÝ KỸ THUẬT
                                                        </div>

                                                        <form
                                                            class="action-form"
                                                            method="POST"
                                                            action="index.php?action=report_update">
                                                            <?= CSRF::field() ?>

                                                            <input
                                                                type="hidden"
                                                                name="report_id"
                                                                value="<?= $reportId ?>">

                                                            <?php if ($status === 'reported'): ?>

                                                                <button
                                                                    type="submit"
                                                                    name="status"
                                                                    value="processing"
                                                                    class="btn btn-edit">

                                                                    Bắt đầu xử lý

                                                                </button>

                                                            <?php endif; ?>

                                                            <?php if ($status === 'processing'): ?>

                                                                <textarea
                                                                    name="resolution_note"
                                                                    placeholder="Nhập kết quả xử lý..."></textarea>

                                                                <button
                                                                    type="submit"
                                                                    name="status"
                                                                    value="resolved"
                                                                    class="btn btn-success"
                                                                    onclick="return confirm('Xác nhận hoàn thành phiếu báo hỏng này?');">

                                                                    Hoàn thành

                                                                </button>

                                                            <?php endif; ?>

                                                        </form>

                                                    </div>

                                                <?php elseif ($isFinished): ?>

                                                    <span class="assigned-info">
                                                        Phiếu đã kết thúc, không thể cập nhật thêm.
                                                    </span>

                                                <?php endif; ?>

                                            <?php else: ?>

                                                <span class="assigned-info">
                                                    Không có thao tác.
                                                </span>

                                            <?php endif; ?>

                                        </div>
                                    </td>

                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer class="footer">
        © Smart Campus Management · Quản lý phòng thực hành và thiết bị
    </footer>

</main>

</body>
</html>
