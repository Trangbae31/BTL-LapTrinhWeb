
<?php

class ReportController
{
    private ?array $rolesCache = null;

    // ==========================================
    // 1. CAC HAM HO TRO TAI KHOAN VA PHAN QUYEN
    // ==========================================

    private function getUser(): array
    {
        return $_SESSION['user'] ?? [];
    }

    private function getUserId(): int
    {
        $user = $this->getUser();
        return (int)($user['user_id'] ?? $user['id'] ?? 0);
    }

    // Lay role tu database, database la nguon phan quyen chinh
    private function getRoles(): array
    {
        global $pdo;

        if ($this->rolesCache !== null) {
            return $this->rolesCache;
        }

        $roles = [];
        $userId = $this->getUserId();

        if ($userId > 0 && isset($pdo) && $pdo instanceof PDO) {
            try {
                $stmt = $pdo->prepare("
                    SELECT r.role_name
                    FROM user_roles ur
                    JOIN roles r ON ur.role_id = r.role_id
                    WHERE ur.user_id = ?
                ");

                $stmt->execute([$userId]);
                $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

            } catch (Throwable $e) {
                error_log('Get roles error: ' . $e->getMessage());
            }
        }

        // Neu database khong tra ve role thi moi dung session cu
        if (empty($roles)) {
            $user = $this->getUser();

            if (!empty($user['roles']) && is_array($user['roles'])) {
                $roles = $user['roles'];
            }

            if (!empty($user['role'])) {
                $roles[] = $user['role'];
            }
        }

        $this->rolesCache = array_values(
            array_unique(array_map('strval', $roles))
        );

        return $this->rolesCache;
    }

    private function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    private function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    private function isManager(): bool
    {
        return $this->hasRole('maintenance_manager');
    }

    private function isTechnician(): bool
    {
        return $this->hasRole('technician');
    }

    private function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    private function requireLogin(): void
    {
        if ($this->getUserId() <= 0) {
            header('Location: index.php?action=login');
            exit();
        }
    }

    private function requireRoles(array $allowedRoles): void
    {
        $this->requireLogin();

        foreach ($allowedRoles as $role) {
            if ($this->hasRole($role)) {
                return;
            }
        }

        http_response_code(403);
        exit('Ban khong co quyen thuc hien chuc nang nay.');
    }

    private function redirectToList(): void
    {
        header('Location: index.php?action=report_list');
        exit();
    }

    private function escapeError(Throwable $e): string
    {
        return htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }

    // ==========================================
    // 2. DANH SACH PHIEU BAO HONG
    // ==========================================

    public function index()
    {
        global $pdo;

        $this->requireRoles([
            'admin',
            'maintenance_manager',
            'teacher',
            'technician'
        ]);

        $userId = $this->getUserId();

        $isAdmin = $this->isAdmin();
        $isManager = $this->isManager();
        $isTechnician = $this->isTechnician();
        $isTeacher = $this->isTeacher();

        // Admin va quan ly bao tri xem tat ca
        $canViewAll = $isAdmin || $isManager;

        try {
            $sql = "
                SELECT
                    mr.report_id,
                    mr.equipment_id,
                    mr.reporter_id,
                    mr.assigned_to,
                    mr.title,
                    mr.description,
                    mr.status,
                    mr.reported_at,
                    mr.resolved_at,
                    mr.resolution_note,
                    e.equipment_code,
                    e.equipment_name,
                    reporter.full_name AS reporter_name,
                    technician.full_name AS technician_name
                FROM maintenance_reports mr
                JOIN equipment e
                    ON mr.equipment_id = e.equipment_id
                LEFT JOIN users reporter
                    ON mr.reporter_id = reporter.user_id
                LEFT JOIN users technician
                    ON mr.assigned_to = technician.user_id
            ";

            $params = [];

            if ($canViewAll) {
                // Admin va quan ly xem tat ca

            } elseif ($isTechnician) {
                $sql .= " WHERE mr.assigned_to = ?";
                $params[] = $userId;

            } elseif ($isTeacher) {
                $sql .= " WHERE mr.reporter_id = ?";
                $params[] = $userId;

            } else {
                http_response_code(403);
                exit('Ban khong co quyen xem danh sach bao hong.');
            }

            $sql .= " ORDER BY mr.reported_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Chi quan ly moi duoc lay danh sach de phan cong
            $technicians = [];

            if ($isManager) {
                $technicianSql = "
                    SELECT DISTINCT
                        u.user_id,
                        u.full_name
                    FROM users u
                    JOIN user_roles ur
                        ON u.user_id = ur.user_id
                    JOIN roles r
                        ON ur.role_id = r.role_id
                    WHERE r.role_name = 'technician'
                      AND u.status = 'active'
                    ORDER BY u.full_name
                ";

                $technicians = $pdo->query($technicianSql)
                    ->fetchAll(PDO::FETCH_ASSOC);
            }

            $roles = $this->getRoles();

            require __DIR__ . '/../views/report_list.php';

        } catch (Throwable $e) {
            error_log('Report index error: ' . $e->getMessage());
            exit('Loi tai danh sach bao hong: ' . $this->escapeError($e));
        }
    }

    // ==========================================
    // 3. TAO PHIEU BAO HONG
    // Teacher va maintenance_manager
    // ==========================================

    public function create()
    {
        global $pdo;

        $this->requireRoles(['teacher', 'maintenance_manager']);

        try {
            $sql = "
                SELECT
                    equipment_id,
                    equipment_code,
                    equipment_name
                FROM equipment
                WHERE status <> 'retired'
                   OR status IS NULL
                ORDER BY equipment_name
            ";

            $equipment = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $report = null;

            require __DIR__ . '/../views/report_form.php';

        } catch (Throwable $e) {
            error_log('Report create error: ' . $e->getMessage());
            exit('Loi tai danh sach thiet bi: ' . $this->escapeError($e));
        }
    }

    // ==========================================
    // 4. LUU PHIEU BAO HONG MOI
    // ==========================================

    public function store()
    {
        global $pdo;

        $this->requireRoles(['teacher', 'maintenance_manager']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=report_form');
            exit();
        }

        $userId = $this->getUserId();
        $equipmentId = (int)($_POST['equipment_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($equipmentId <= 0 || $title === '' || $description === '') {
            exit('Vui long nhap day du thong tin bao hong.');
        }

        try {
            $pdo->beginTransaction();

            $check = $pdo->prepare("
                SELECT equipment_id
                FROM equipment
                WHERE equipment_id = ?
                  AND (status <> 'retired' OR status IS NULL)
                FOR UPDATE
            ");

            $check->execute([$equipmentId]);

            if (!$check->fetch()) {
                throw new RuntimeException(
                    'Thiet bi khong ton tai hoac da ngung su dung.'
                );
            }

            $stmt = $pdo->prepare("
                INSERT INTO maintenance_reports
                    (equipment_id, reporter_id, title, description, status)
                VALUES (?, ?, ?, ?, 'reported')
            ");

            $stmt->execute([
                $equipmentId,
                $userId,
                $title,
                $description
            ]);

            $reportId = (int)$pdo->lastInsertId();

            $history = $pdo->prepare("
                INSERT INTO maintenance_history
                    (report_id, user_id, old_status, new_status, action_note)
                VALUES (?, ?, NULL, 'reported', ?)
            ");

            $history->execute([
                $reportId,
                $userId,
                'Tao phieu bao hong moi.'
            ]);

            $pdo->commit();
            $this->redirectToList();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Report store error: ' . $e->getMessage());
            exit('Loi luu phieu bao hong: ' . $this->escapeError($e));
        }
    }

    // ==========================================
    // 5. SUA THONG TIN PHIEU
    // Chi maintenance_manager
    // ==========================================

    public function edit()
    {
        global $pdo;

        $this->requireRoles(['maintenance_manager']);

        $reportId = (int)($_GET['id'] ?? 0);

        if ($reportId <= 0) {
            $this->redirectToList();
        }

        try {
            $stmt = $pdo->prepare("
                SELECT
                    report_id,
                    equipment_id,
                    title,
                    description,
                    status
                FROM maintenance_reports
                WHERE report_id = ?
            ");

            $stmt->execute([$reportId]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                exit('Khong tim thay phieu bao hong.');
            }

            if (in_array($report['status'], ['resolved', 'cancelled'], true)) {
                exit('Phieu da ket thuc, khong the chinh sua.');
            }

            $stmt = $pdo->prepare("
                SELECT
                    equipment_id,
                    equipment_code,
                    equipment_name
                FROM equipment
                WHERE status <> 'retired'
                   OR status IS NULL
                   OR equipment_id = ?
                ORDER BY equipment_name
            ");

            $stmt->execute([(int)$report['equipment_id']]);
            $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

            require __DIR__ . '/../views/report_form.php';

        } catch (Throwable $e) {
            error_log('Report edit error: ' . $e->getMessage());
            exit('Loi tai form sua phieu: ' . $this->escapeError($e));
        }
    }

    // ==========================================
    // 6. CAP NHAT THONG TIN PHIEU
    // Chi maintenance_manager
    // ==========================================

    public function update()
    {
        global $pdo;

        $this->requireRoles(['maintenance_manager']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToList();
        }

        $userId = $this->getUserId();

        $reportId = (int)($_GET['id'] ?? $_POST['report_id'] ?? 0);
        $equipmentId = (int)($_POST['equipment_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($reportId <= 0 || $equipmentId <= 0 || $title === '' || $description === '') {
            exit('Vui long nhap day du thong tin.');
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT status, equipment_id
                FROM maintenance_reports
                WHERE report_id = ?
                FOR UPDATE
            ");

            $stmt->execute([$reportId]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                throw new RuntimeException('Khong tim thay phieu bao hong.');
            }

            if (in_array($report['status'], ['resolved', 'cancelled'], true)) {
                throw new RuntimeException('Phieu da ket thuc, khong the chinh sua.');
            }

            $stmt = $pdo->prepare("
                SELECT equipment_id
                FROM equipment
                WHERE equipment_id = ?
                  AND (status <> 'retired' OR status IS NULL)
            ");

            $stmt->execute([$equipmentId]);

            if (!$stmt->fetch()) {
                throw new RuntimeException(
                    'Thiet bi khong ton tai hoac da ngung su dung.'
                );
            }

            $stmt = $pdo->prepare("
                UPDATE maintenance_reports
                SET equipment_id = ?, title = ?, description = ?
                WHERE report_id = ?
            ");

            $stmt->execute([$equipmentId, $title, $description, $reportId]);

            $history = $pdo->prepare("
                INSERT INTO maintenance_history
                    (report_id, user_id, old_status, new_status, action_note)
                VALUES (?, ?, ?, ?, ?)
            ");

            $history->execute([
                $reportId,
                $userId,
                $report['status'],
                $report['status'],
                'Quan ly chinh sua thong tin phieu bao hong.'
            ]);

            $pdo->commit();
            $this->redirectToList();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Report update error: ' . $e->getMessage());
            exit('Loi cap nhat phieu: ' . $this->escapeError($e));
        }
    }

    // ==========================================
    // 7. PHAN CONG KY THUAT VIEN
    // Chi maintenance_manager
    // ==========================================

    public function assign()
    {
        global $pdo;

        $this->requireRoles(['maintenance_manager']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToList();
        }

        $userId = $this->getUserId();
        $reportId = (int)($_POST['report_id'] ?? 0);
        $technicianId = (int)($_POST['technician_id'] ?? 0);

        if ($reportId <= 0 || $technicianId <= 0) {
            exit('Thong tin phan cong khong hop le.');
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT report_id, status, assigned_to
                FROM maintenance_reports
                WHERE report_id = ?
                FOR UPDATE
            ");

            $stmt->execute([$reportId]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                throw new RuntimeException('Khong tim thay phieu bao hong.');
            }

            if (in_array($report['status'], ['resolved', 'cancelled'], true)) {
                throw new RuntimeException('Khong the phan cong phieu da ket thuc.');
            }

            $stmt = $pdo->prepare("
                SELECT u.user_id
                FROM users u
                JOIN user_roles ur ON u.user_id = ur.user_id
                JOIN roles r ON ur.role_id = r.role_id
                WHERE u.user_id = ?
                  AND u.status = 'active'
                  AND r.role_name = 'technician'
                LIMIT 1
            ");

            $stmt->execute([$technicianId]);

            if (!$stmt->fetch()) {
                throw new RuntimeException(
                    'Ky thuat vien khong hop le hoac tai khoan da bi khoa.'
                );
            }

            $oldTechnicianId = $report['assigned_to'];

            $stmt = $pdo->prepare("
                UPDATE maintenance_reports
                SET assigned_to = ?
                WHERE report_id = ?
            ");

            $stmt->execute([$technicianId, $reportId]);

            $note = $oldTechnicianId
                ? 'Quan ly thay doi ky thuat vien duoc phan cong.'
                : 'Quan ly phan cong ky thuat vien xu ly.';

            $stmt = $pdo->prepare("
                INSERT INTO maintenance_history
                    (report_id, user_id, old_status, new_status, action_note)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $reportId,
                $userId,
                $report['status'],
                $report['status'],
                $note
            ]);

            $pdo->commit();
            $this->redirectToList();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Report assign error: ' . $e->getMessage());
            exit('Loi phan cong ky thuat vien: ' . $this->escapeError($e));
        }
    }

    // ==========================================
    // 8. XEM LICH SU BAO TRI
    // ==========================================

    public function history()
    {
        global $pdo;

        $this->requireRoles([
            'admin',
            'maintenance_manager',
            'teacher',
            'technician'
        ]);

        $userId = $this->getUserId();
        $reportId = (int)($_GET['id'] ?? 0);

        if ($reportId <= 0) {
            $this->redirectToList();
        }

        $isAdmin = $this->isAdmin();
        $isManager = $this->isManager();
        $isTechnician = $this->isTechnician();
        $isTeacher = $this->isTeacher();

        $canViewAll = $isAdmin || $isManager;

        try {
            $checkSql = "
                SELECT report_id
                FROM maintenance_reports
                WHERE report_id = ?
            ";

            $params = [$reportId];

            if (!$canViewAll) {
                if ($isTechnician) {
                    $checkSql .= " AND assigned_to = ?";
                } elseif ($isTeacher) {
                    $checkSql .= " AND reporter_id = ?";
                } else {
                    http_response_code(403);
                    exit('Ban khong co quyen xem lich su phieu nay.');
                }

                $params[] = $userId;
            }

            $check = $pdo->prepare($checkSql);
            $check->execute($params);

            if (!$check->fetch()) {
                http_response_code(403);
                exit('Khong tim thay phieu hoac ban khong co quyen xem.');
            }

            $sql = "
                SELECT
                    mh.history_id,
                    mh.old_status,
                    mh.new_status,
                    mh.action_note,
                    mh.changed_at,
                    u.full_name AS user_name
                FROM maintenance_history mh
                LEFT JOIN users u ON mh.user_id = u.user_id
                WHERE mh.report_id = ?
                ORDER BY mh.changed_at DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$reportId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $report = $pdo->prepare("
                SELECT
                    mr.report_id,
                    mr.title,
                    mr.status,
                    e.equipment_code,
                    e.equipment_name
                FROM maintenance_reports mr
                JOIN equipment e ON mr.equipment_id = e.equipment_id
                WHERE mr.report_id = ?
            ");

            $report->execute([$reportId]);
            $reportInfo = $report->fetch(PDO::FETCH_ASSOC);

            require __DIR__ . '/../views/report_history.php';

        } catch (Throwable $e) {
            error_log('Report history error: ' . $e->getMessage());
            exit('Loi tai lich su bao tri: ' . $this->escapeError($e));
        }
    }

    // ==========================================
    // 9. CAP NHAT TRANG THAI PHIEU
    // Manager: tat ca phieu
    // Technician: chi phieu duoc giao
    // ==========================================

    public function updateStatus()
    {
        global $pdo;

        $this->requireRoles(['maintenance_manager', 'technician']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToList();
        }

        $userId = $this->getUserId();
        $isManager = $this->isManager();

        $reportId = (int)($_POST['report_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $note = trim($_POST['resolution_note'] ?? '');

        if ($reportId <= 0 || !in_array($newStatus, ['processing', 'resolved'], true)) {
            exit('Thong tin cap nhat khong hop le.');
        }

        if ($newStatus === 'resolved' && $note === '') {
            exit('Vui long nhap ghi chu truoc khi hoan thanh phieu.');
        }

        try {
            $pdo->beginTransaction();

            $sql = "
                SELECT report_id, assigned_to, status
                FROM maintenance_reports
                WHERE report_id = ?
            ";

            $params = [$reportId];

            if (!$isManager) {
                $sql .= " AND assigned_to = ?";
                $params[] = $userId;
            }

            $sql .= " FOR UPDATE";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                throw new RuntimeException(
                    'Phieu khong ton tai hoac ban khong co quyen xu ly.'
                );
            }

            $oldStatus = $report['status'];

            if (in_array($oldStatus, ['resolved', 'cancelled'], true)) {
                throw new RuntimeException('Phieu da ket thuc, khong the cap nhat tiep.');
            }

            if (
                ($oldStatus === 'reported' && $newStatus !== 'processing') ||
                ($oldStatus === 'processing' && $newStatus !== 'resolved')
            ) {
                throw new RuntimeException('Khong the chuyen trang thai theo trinh tu nay.');
            }

            $stmt = $pdo->prepare("
                UPDATE maintenance_reports
                SET
                    status = ?,
                    resolved_at = CASE
                        WHEN ? = 'resolved' THEN NOW()
                        ELSE NULL
                    END,
                    resolution_note = ?
                WHERE report_id = ?
            ");

            $stmt->execute([
                $newStatus,
                $newStatus,
                $note !== '' ? $note : null,
                $reportId
            ]);

            $historyNote = $note !== ''
                ? $note
                : 'Bat dau xu ly phieu bao hong.';

            $stmt = $pdo->prepare("
                INSERT INTO maintenance_history
                    (report_id, user_id, old_status, new_status, action_note)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $reportId,
                $userId,
                $oldStatus,
                $newStatus,
                $historyNote
            ]);

            $pdo->commit();
            $this->redirectToList();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Report status update error: ' . $e->getMessage());
            exit('Loi cap nhat trang thai: ' . $this->escapeError($e));
        }
    }

    // ==========================================
    // 10. XOA PHIEU BAO HONG
    // Chi maintenance_manager
    // ==========================================

    public function delete()
    {
        global $pdo;

        $this->requireRoles(['maintenance_manager']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToList();
        }

        $reportId = (int)($_POST['report_id'] ?? 0);

        if ($reportId <= 0) {
            $this->redirectToList();
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                DELETE FROM maintenance_history
                WHERE report_id = ?
            ");

            $stmt->execute([$reportId]);

            $stmt = $pdo->prepare("
                DELETE FROM maintenance_reports
                WHERE report_id = ?
            ");

            $stmt->execute([$reportId]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Khong tim thay phieu can xoa.');
            }

            $pdo->commit();
            $this->redirectToList();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Report delete error: ' . $e->getMessage());
            exit('Loi xoa phieu: ' . $this->escapeError($e));
        }
    }
}
