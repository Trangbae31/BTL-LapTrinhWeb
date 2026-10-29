
<?php

class ReportController
{
    // 1. DANH SACH PHIEU BAO HONG
    public function index()
    {
        global $pdo;

        try {
            $sql = "SELECT
                        mr.report_id,
                        mr.equipment_id,
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

            $stmt = $pdo->query($sql);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

            require __DIR__ . '/../views/report_list.php';

        } catch (Throwable $e) {
            exit(
                'Loi tai danh sach bao hong: '
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            );
        }
    }

    // 2. HIEN THI FORM TAO PHIEU BAO HONG
    public function create()
    {
        global $pdo;

        try {
            $sql = "SELECT equipment_id, equipment_code, equipment_name
                    FROM equipment
                    WHERE status <> 'retired' OR status IS NULL
                    ORDER BY equipment_name";

            $equipment = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            require __DIR__ . '/../views/report_form.php';

        } catch (Throwable $e) {
            exit(
                'Loi tai danh sach thiet bi: '
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            );
        }
    }

    // 3. LUU PHIEU BAO HONG MOI
    public function store()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=report_form');
            exit();
        }

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

        try {
            $check = $pdo->prepare(
                "SELECT equipment_id
                 FROM equipment
                 WHERE equipment_id = ?
                   AND (status <> 'retired' OR status IS NULL)"
            );
            $check->execute([$equipmentId]);

            if (!$check->fetch()) {
                exit('Thiet bi khong ton tai hoac da ngung su dung.');
            }

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

            header('Location: index.php?action=report_list');
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            exit(
                'Loi luu phieu bao hong: '
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            );
        }
    }

    // 4. HIEN THI FORM SUA PHIEU BAO HONG
    public function edit()
    {
        global $pdo;

        $reportId = (int)($_GET['id'] ?? 0);

        if ($reportId <= 0) {
            header('Location: index.php?action=report_list');
            exit();
        }

        try {
            $stmt = $pdo->prepare(
                "SELECT report_id, equipment_id, title, description
                 FROM maintenance_reports
                 WHERE report_id = ?"
            );
            $stmt->execute([$reportId]);

            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                exit('Khong tim thay phieu bao hong.');
            }

            // Van hien thi thiet bi cua phieu neu thiet bi da ngung su dung
            $stmt = $pdo->prepare(
                "SELECT equipment_id, equipment_code, equipment_name
                 FROM equipment
                 WHERE status <> 'retired'
                    OR status IS NULL
                    OR equipment_id = ?
                 ORDER BY equipment_name"
            );
            $stmt->execute([(int)$report['equipment_id']]);

            $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

            require __DIR__ . '/../views/report_form.php';

        } catch (Throwable $e) {
            exit(
                'Loi tai form sua phieu: '
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            );
        }
    }

    // 5. CAP NHAT THONG TIN PHIEU DA SUA
    public function update()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=report_list');
            exit();
        }

        $user = $_SESSION['user'] ?? [];
        $userId = $user['user_id'] ?? $user['id'] ?? null;

        if (!$userId) {
            exit('Khong tim thay ma nguoi dung dang dang nhap.');
        }

        $reportId = (int)($_GET['id'] ?? 0);
        $equipmentId = (int)($_POST['equipment_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (
            $reportId <= 0 ||
            $equipmentId <= 0 ||
            $title === '' ||
            $description === ''
        ) {
            exit('Vui long nhap day du thong tin.');
        }

        try {
            $pdo->beginTransaction();

            // Kiem tra phieu ton tai
            $stmt = $pdo->prepare(
                "SELECT status
                 FROM maintenance_reports
                 WHERE report_id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$reportId]);

            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                throw new RuntimeException('Khong tim thay phieu bao hong.');
            }

            // Kiem tra thiet bi hop le
            $stmt = $pdo->prepare(
                "SELECT equipment_id
                 FROM equipment
                 WHERE equipment_id = ?
                   AND (status <> 'retired' OR status IS NULL)"
            );
            $stmt->execute([$equipmentId]);

            // Neu thiet bi hien tai da ngung su dung,
            // van cho phep giu nguyen thiet bi do
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare(
                    "SELECT equipment_id
                     FROM maintenance_reports
                     WHERE report_id = ? AND equipment_id = ?"
                );
                $stmt->execute([$reportId, $equipmentId]);

                if (!$stmt->fetch()) {
                    throw new RuntimeException(
                        'Thiet bi khong ton tai hoac da ngung su dung.'
                    );
                }
            }

            $stmt = $pdo->prepare(
                "UPDATE maintenance_reports
                 SET equipment_id = ?, title = ?, description = ?
                 WHERE report_id = ?"
            );

            $stmt->execute([
                $equipmentId,
                $title,
                $description,
                $reportId
            ]);

            // Ghi lich su chinh sua, khong thay doi trang thai
            $status = $report['status'];

            $history = $pdo->prepare(
                "INSERT INTO maintenance_history
                    (report_id, user_id, old_status, new_status, action_note)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $history->execute([
                $reportId,
                $userId,
                $status,
                $status,
                'Chinh sua thong tin phieu bao hong'
            ]);

            $pdo->commit();

            header('Location: index.php?action=report_list');
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            exit(
                'Loi cap nhat phieu: '
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            );
        }
    }

    // 6. XEM LICH SU BAO TRI CUA MOT PHIEU
    public function history()
    {
        global $pdo;

        $reportId = (int)($_GET['id'] ?? 0);

        if ($reportId <= 0) {
            header('Location: index.php?action=report_list');
            exit();
        }

        try {
            $check = $pdo->prepare(
                "SELECT report_id
                 FROM maintenance_reports
                 WHERE report_id = ?"
            );
            $check->execute([$reportId]);

            if (!$check->fetch()) {
                exit('Khong tim thay phieu bao hong.');
            }

            $sql = "SELECT
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
                    ORDER BY mh.changed_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$reportId]);

            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            require __DIR__ . '/../views/report_history.php';

        } catch (Throwable $e) {
            exit(
                'Loi tai lich su bao tri: '
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            );
        }
    }

    // 7. CAP NHAT TRANG THAI VA GHI LICH SU
    public function updateStatus()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=report_list');
            exit();
        }

        $user = $_SESSION['user'] ?? [];
        $userId = $user['user_id'] ?? $user['id'] ?? null;

        if (!$userId) {
            exit('Khong tim thay ma nguoi dung dang dang nhap.');
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $note = trim($_POST['resolution_note'] ?? '');

        $allowed = [
            'reported',
            'processing',
            'resolved',
            'cancelled'
        ];

        if (
            $reportId <= 0 ||
            !in_array($newStatus, $allowed, true)
        ) {
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

            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                throw new RuntimeException('Khong tim thay phieu bao hong.');
            }

            $oldStatus = $report['status'];

            $sql = "UPDATE maintenance_reports
                    SET status = ?,
                        resolved_at = CASE
                            WHEN ? = 'resolved' THEN NOW()
                            ELSE NULL
                        END,
                        resolution_note = ?
                    WHERE report_id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $newStatus,
                $newStatus,
                $note !== '' ? $note : null,
                $reportId
            ]);

            if ($oldStatus !== $newStatus || $note !== '') {
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
            }

            $pdo->commit();

            header('Location: index.php?action=report_list');
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            exit(
                'Loi cap nhat phieu: '
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            );
        }
    }
}
