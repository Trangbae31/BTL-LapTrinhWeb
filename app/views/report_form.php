
<?php
// Co $report thi la sua phieu, nguoc lai la tao phieu moi
$isEdit = isset($report) && !empty($report);

$pageTitle = $isEdit
    ? 'Sửa phiếu báo hỏng'
    : 'Tạo phiếu báo hỏng';

$formAction = $isEdit
    ? 'index.php?action=report_update_info&id=' . (int)$report['report_id']
    : 'index.php?action=report_store';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> - Smart Campus</title>

    <style>
        :root {
            --green: #176b4d;
            --green-dark: #104d39;
            --green-light: #e8f5ee;
            --bg: #f4f7f5;
            --text: #26372e;
            --muted: #78867e;
            --border: #e1e9e3;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        /* Thanh dau trang */
        .topbar {
            min-height: 72px;
            padding: 14px 5%;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            background: var(--green-light);
            display: grid;
            place-items: center;
            font-size: 23px;
        }

        .brand-name {
            font-size: 18px;
            font-weight: 750;
            color: var(--green-dark);
        }

        .brand-sub {
            margin-top: 4px;
            font-size: 10px;
            color: var(--muted);
            letter-spacing: .5px;
        }

        .back-link {
            color: var(--green);
            font-size: 13px;
            font-weight: 700;
        }

        .back-link:hover {
            color: var(--green-dark);
        }

        /* Khu vuc form */
        .container {
            width: min(100% - 32px, 760px);
            margin: 42px auto;
        }

        .breadcrumb {
            margin-bottom: 18px;
            color: var(--muted);
            font-size: 13px;
        }

        .breadcrumb a {
            color: var(--green);
        }

        .form-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 17px;
            overflow: hidden;
            box-shadow: 0 8px 28px rgba(25, 70, 48, .07);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 26px 30px;
            background: linear-gradient(135deg, #e8f5ee, #f8fbf9);
            border-bottom: 1px solid var(--border);
        }

        .form-icon {
            width: 54px;
            height: 54px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 15px;
            background: white;
            font-size: 27px;
            box-shadow: 0 3px 12px rgba(23, 107, 77, .08);
        }

        .form-header h1 {
            margin: 0;
            color: var(--green-dark);
            font-size: 23px;
        }

        .form-header p {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .form-body {
            padding: 30px;
        }

        .notice {
            margin-bottom: 25px;
            padding: 13px 15px;
            background: #f4f9f5;
            border: 1px solid #e1eee4;
            border-radius: 10px;
            color: #50675a;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 23px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            font-size: 14px;
            font-weight: 700;
        }

        .required {
            color: #d04444;
        }

        .form-control {
            display: block;
            width: 100%;
            min-height: 46px;
            padding: 12px 14px;
            border: 1px solid #d7e1d9;
            border-radius: 9px;
            background: white;
            color: var(--text);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(23, 107, 77, .10);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            min-height: 145px;
            line-height: 1.7;
            resize: vertical;
        }

        .hint {
            display: block;
            margin-top: 7px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        /* Nut thao tac */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 23px;
            margin-top: 28px;
            border-top: 1px solid #edf1ed;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 11px 19px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .2s;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--green);
            color: white;
        }

        .btn-primary:hover {
            background: var(--green-dark);
        }

        .btn-secondary {
            background: #f2f5f3;
            color: #526258;
            border-color: var(--border);
        }

        .btn-secondary:hover {
            background: #e7ede9;
        }

        .footer {
            padding: 22px 10px;
            text-align: center;
            color: #929d96;
            font-size: 11px;
        }

        @media (max-width: 600px) {
            .topbar {
                padding: 14px 17px;
            }

            .brand-name {
                font-size: 16px;
            }

            .brand-sub {
                font-size: 9px;
            }

            .container {
                width: calc(100% - 24px);
                margin: 24px auto;
            }

            .form-header {
                padding: 22px 18px;
                align-items: flex-start;
            }

            .form-header h1 {
                font-size: 19px;
            }

            .form-icon {
                width: 45px;
                height: 45px;
                font-size: 22px;
            }

            .form-body {
                padding: 22px 18px;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .btn {
                width: 100%;
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

    <a class="back-link" href="index.php?action=reports">
        ← Danh sách phiếu
    </a>
</header>

<main class="container">

    <div class="breadcrumb">
        <a href="index.php?action=dashboard">Trang chủ</a>
        &nbsp; / &nbsp;
        <a href="index.php?action=reports">Quản lý báo hỏng</a>
        &nbsp; / &nbsp;
        <?= $isEdit ? 'Sửa phiếu' : 'Tạo phiếu mới' ?>
    </div>

    <section class="form-card">

        <div class="form-header">
            <div class="form-icon">
                <?= $isEdit ? '✏️' : '📋' ?>
            </div>

            <div>
                <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> thiết bị</h1>
                <p>
                    <?= $isEdit
                        ? 'Cập nhật thông tin của phiếu báo hỏng đang chọn.'
                        : 'Điền thông tin để gửi yêu cầu kiểm tra và bảo trì thiết bị.' ?>
                </p>
            </div>
        </div>

        <div class="form-body">

            <div class="notice">
                💡 Hãy kiểm tra thông tin trước khi lưu để đảm bảo phiếu báo hỏng chính xác.
            </div>

            <form action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>"
                  method="POST">

                <div class="form-group">
                    <label for="equipment_id">
                        Thiết bị <span class="required">*</span>
                    </label>

                    <select
                        class="form-control"
                        id="equipment_id"
                        name="equipment_id"
                        required
                    >
                        <option value="">-- Chọn thiết bị --</option>

                        <?php foreach ($equipment as $item): ?>
                            <option
                                value="<?= (int)$item['equipment_id'] ?>"
                                <?= $isEdit
                                    && (int)$report['equipment_id'] === (int)$item['equipment_id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    (string)$item['equipment_code'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                                -
                                <?= htmlspecialchars(
                                    (string)$item['equipment_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <span class="hint">
                        Chọn thiết bị cần báo hỏng hoặc cập nhật.
                    </span>
                </div>

                <div class="form-group">
                    <label for="title">
                        Tiêu đề lỗi <span class="required">*</span>
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="title"
                        name="title"
                        maxlength="200"
                        placeholder="Ví dụ: Máy tính không khởi động"
                        required
                        value="<?= htmlspecialchars(
                            (string)($isEdit ? ($report['title'] ?? '') : ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <span class="hint">
                        Nhập tiêu đề ngắn gọn về sự cố thiết bị.
                    </span>
                </div>

                <div class="form-group">
                    <label for="description">
                        Mô tả lỗi <span class="required">*</span>
                    </label>

                    <textarea
                        class="form-control"
                        id="description"
                        name="description"
                        rows="5"
                        placeholder="Mô tả tình trạng thiết bị và sự cố đang gặp phải..."
                        required
                    ><?= htmlspecialchars(
                        (string)($isEdit ? ($report['description'] ?? '') : ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                    <span class="hint">
                        Mô tả cụ thể sẽ giúp việc kiểm tra và bảo trì thuận tiện hơn.
                    </span>
                </div>

                <div class="form-actions">
                    <a class="btn btn-secondary"
                       href="index.php?action=reports">
                        ← Quay lại
                    </a>

                    <button class="btn btn-primary" type="submit">
                        <?= $isEdit ? '✓ Lưu thay đổi' : '＋ Gửi phiếu báo hỏng' ?>
                    </button>
                </div>

            </form>
        </div>
    </section>

    <footer class="footer">
        © Smart Campus Management · Quản lý phòng thực hành và thiết bị
    </footer>

</main>

</body>
</html>
