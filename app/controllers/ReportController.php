
<?php

class ReportController
{
    // Danh sach phieu bao hong
    public function index()
    {
        global $pdo;

        $sql = "SELECT
                    mr.report_id,
                    mr.title,
                    mr.description,
                    mr.status,
                    mr.reported_at,
                    e.equipment_code,
                    e.equipment_name,
                    u.full_name AS reporter_name
                FROM maintenance_reports mr
                JOIN equipment e
                    ON mr.equipment_id = e.equipment_id
                LEFT JOIN users u
                    ON mr.reporter_id = u.user_id
                ORDER BY mr.reported_at DESC";

        $reports = $pdo->query($sql)->fetchAll();

        require __DIR__ . '/../views/report_list.php';
    }

    // Hien thi form tao phieu
    public function create()
    {
        global $pdo;

        $equipment = $pdo->query(
            "SELECT equipment_id, equipment_code, equipment_name
             FROM equipment
             WHERE status <> 'retired'
             ORDER BY equipment_name"
        )->fetchAll();

        require __DIR__ . '/../views/report_form.php';
    }

    // Luu phieu bao hong
    public function store()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=report_form');
            exit();
        }

        // Kiem tra nguoi dung da dang nhap
        $user = $_SESSION['user'] ?? [];
        $userId = $user['user_id'] ?? $user['id'] ?? null;

        if (!$userId) {
            exit('Khong tim thay ma nguoi dung dang dang nhap.');
        }

        $equipmentId = (int)($_POST['equipment_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($equipmentId <= 0 || $title === '' || $description === '') {
            exit('Vui long nhap day du thong tin bao hong.');
        }

        // Kiem tra thiet bi co ton tai khong
        $check = $pdo->prepare(
            "SELECT equipment_id
             FROM equipment
             WHERE equipment_id = ?
               AND status <> 'retired'"
        );
        $check->execute([$equipmentId]);

        if (!$check->fetch()) {
            exit('Thiet bi khong ton tai hoac da ngung su dung.');
        }

        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO maintenance_reports
                        (equipment_id, reporter_id, title, description, status)
                    VALUES (?, ?, ?, ?, 'reported')";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $equipmentId,
                $userId,
                $title,
                $description
            ]);

            $reportId = $pdo->lastInsertId();

            // Ghi nhan lich su tiep nhan phieu
            $history = $pdo->prepare(
                "INSERT INTO maintenance_history
                    (report_id, user_id, old_status, new_status, action_note)
                 VALUES (?, ?, NULL, 'reported', ?)"
            );

            $history->execute([
                $reportId,
                $userId,
                'Tao phieu bao hong'
            ]);

            $pdo->commit();

            header('Location: index.php?action=reports');
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            exit('Khong the luu phieu bao hong. Vui long kiem tra database.');
        }
    }

    // Xem lich su cua mot phieu
    public function history()
    {
        global $pdo;

        $reportId = (int)($_GET['id'] ?? 0);

        $stmt = $pdo->prepare(
            "SELECT
                mh.history_id,
                mh.old_status,
                mh.new_status,
                mh.action_note,
                mh.changed_at,
                u.full_name AS user_name
             FROM maintenance_history mh
             LEFT JOIN users u
                ON mh.user_id = u.user_id
             WHERE mh.report_id = ?
             ORDER BY mh.changed_at DESC"
        );

        $stmt->execute([$reportId]);
        $history = $stmt->fetchAll();

        require __DIR__ . '/../views/report_history.php';
    }

    // Cap nhat trang thai xu ly
    public function updateStatus()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=reports');
            exit();
        }

        $user = $_SESSION['user'] ?? [];
        $userId = $user['user_id'] ?? $user['id'] ?? null;

        if (!$userId) {
            exit('Khong tim thay ma nguoi dung.');
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        $note = trim($_POST['resolution_note'] ?? '');

        $allowed = ['reported', 'processing', 'resolved', 'cancelled'];

        if ($reportId <= 0 || !in_array($newStatus, $allowed, true)) {
            exit('Thong tin cap nhat khong hop le.');
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT status
                 FROM maintenance_reports
                 WHERE report_id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$reportId]);
            $report = $stmt->fetch();

            if (!$report) {
                throw new RuntimeException('Khong tim thay phieu bao hong.');
            }

            $oldStatus = $report['status'];

            $stmt = $pdo->prepare(
                "UPDATE maintenance_reports
                 SET status = ?,
                     resolved_at = CASE
                         WHEN ? = 'resolved' THEN NOW()
                         ELSE NULL
                     END,
                     resolution_note = ?
                 WHERE report_id = ?"
            );

            $stmt->execute([
                $newStatus,
                $newStatus,
                $note !== '' ? $note : null,
                $reportId
            ]);

            $history = $pdo->prepare(
                "INSERT INTO maintenance_history
                    (report_id, user_id, old_status, new_status, action_note)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $history->execute([
                $reportId,
                $userId,
                $oldStatus,
                $newStatus,
                $note !== '' ? $note : 'Cap nhat trang thai'
            ]);

            $pdo->commit();

            header('Location: index.php?action=reports');
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            exit('Khong the cap nhat phieu. Vui long kiem tra lai.');
        }
    }
}
