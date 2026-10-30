<?php

class ReportController
{
    private function getUser(): array
    {
        return $_SESSION['user'] ?? [];
    }

    private function getUserId(): int
    {
        $user = $this->getUser();

        return (int)($user['user_id'] ?? $user['id'] ?? 0);
    }

    private function getRoles(): array
    {
        $user = $this->getUser();
        $roles = $user['roles'] ?? [];

        if (!is_array($roles)) {
            $roles = [];
        }

        // Tương thích với session cũ chỉ lưu một role
        if (empty($roles) && !empty($user['role'])) {
            $roles[] = $user['role'];
        }

        return $roles;
    }

    private function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
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

        if ($this->hasRole('admin')) {
            return;
        }

        foreach ($allowedRoles as $role) {
            if ($this->hasRole($role)) {
                return;
            }
        }

        http_response_code(403);
        exit('Bạn không có quyền thực hiện chức năng này.');
    }

    private function redirectToList(): void
    {
        header('Location: index.php?action=report_list');
        exit();
    }

    private function escapeError(Throwable $e): string
    {
        return htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * 1. DANH SÁCH PHIẾU BÁO HỎNG
     *
     * Manager: xem tất cả phiếu.
     * Technician: chỉ xem phiếu được phân công.
     * Teacher: xem phiếu do mình tạo.
     */
    public function index()
    {
        global $pdo;

        $this->requireRoles([
            'admin',
            'manager',
            'teacher',
            'technician'
        ]);

        $userId = $this->getUserId();

        $isManager = $this->hasRole('admin')
            || $this->hasRole('manager');

        $isTechnician = $this->hasRole('technician');
        $isTeacher = $this->hasRole('teacher');

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

            if ($isManager) {
                // Manager được xem tất cả phiếu
            } elseif ($isTechnician) {
                $sql .= " WHERE mr.assigned_to = ?";
                $params[] = $userId;
            } elseif ($isTeacher) {
                $sql .= " WHERE mr.reporter_id = ?";
                $params[] = $userId;
            } else {
                http_response_code(403);
                exit('Bạn không có quyền xem danh sách báo hỏng.');
            }

            $sql .= " ORDER BY mr.reported_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Danh sách kỹ thuật viên chỉ cần cho manager phân công
            $technicians = [];

            if ($isManager) {
                $technicianSql = "
                    SELECT
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

            require __DIR__ . '/../views/report_list.php';

        } catch (Throwable $e) {
            error_log('Report index error: ' . $e->getMessage());

            exit(
                'Lỗi tải danh sách báo hỏng: '
                . $this->escapeError($e)
            );
        }
    }

    /**
     * 2. HIỂN THỊ FORM TẠO PHIẾU
     */
    public function create()
    {
        global $pdo;

        $this->requireRoles(['teacher']);

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

            $equipment = $pdo->query($sql)
                ->fetchAll(PDO::FETCH_ASSOC);

            $report = null;

            require __DIR__ . '/../views/report_form.php';

        } catch (Throwable $e) {
            error_log('Report create error: ' . $e->getMessage());

            exit(
                'Lỗi tải danh sách thiết bị: '
                . $this->escapeError($e)
            );
        }
    }

    /**
     * 3. LƯU PHIẾU BÁO HỎNG MỚI
     */
    public function store()
    {
        global $pdo;

        $this->requireRoles(['teacher']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=report_form');
            exit();
        }

        $userId = $this->getUserId();

        $equipmentId = (int)($_POST['equipment_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (
            $equipmentId <= 0 ||
            $title === '' ||
            $description === ''
        ) {
            exit('Vui lòng nhập đầy đủ thông tin báo hỏng.');
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
                    'Thiết bị không tồn tại hoặc đã ngừng sử dụng.'
                );
            }

            $sql = "
                INSERT INTO maintenance_reports
                    (
                        equipment_id,
                        reporter_id,
                        title,
                        description,
                        status
                    )

                VALUES (?, ?, ?, ?, 'reported')
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $equipmentId,
                $userId,
                $title,
                $description
            ]);

            $reportId = (int)$pdo->lastInsertId();

            $history = $pdo->prepare("
                INSERT INTO maintenance_history
                    (
                        report_id,
                        user_id,
                        old_status,
                        new_status,
                        action_note
                    )

                VALUES (?, ?, NULL, 'reported', ?)
            ");

            $history->execute([
                $reportId,
                $userId,
                'Tạo phiếu báo hỏng mới.'
            ]);

            $pdo->commit();

            $this->redirectToList();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Report store error: ' . $e->getMessage());

            exit(
                'Lỗi lưu phiếu báo hỏng: '
                . $this->escapeError($e)
            );
        }
    }

    /**
     * 4. HIỂN THỊ FORM SỬA PHIẾU
     *
     * Chỉ manager/admin được sửa thông tin phiếu.
     */
    public function edit()
    {
        global $pdo;

        $this->requireRoles(['manager']);

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
                exit('Không tìm thấy phiếu báo hỏng.');
            }

            if (in_array(
                $report['status'],
                ['resolved', 'cancelled'],
                true
            )) {
                exit('Phiếu đã kết thúc, không thể chỉnh sửa.');
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

            $stmt->execute([
                (int)$report['equipment_id']
            ]);

            $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

            require __DIR__ . '/../views/report_form.php';

        } catch (Throwable $e) {
            error_log('Report edit error: ' . $e->getMessage());

            exit(
                'Lỗi tải form sửa phiếu: '
                . $this->escapeError($e)
            );
        }
    }

    /**
     * 5. CẬP NHẬT THÔNG TIN PHIẾU
     */
    public function update()
    {
        global $pdo;

        $this->requireRoles(['manager']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToList();
        }

        $userId = $this->getUserId();

        $reportId = (int)($_GET['id'] ?? $_POST['report_id'] ?? 0);
        $equipmentId = (int)($_POST['equipment_id'] ?? 0);

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (
            $reportId <= 0 ||
            $equipmentId <= 0 ||
            $title === '' ||
            $description === ''
        ) {
            exit('Vui lòng nhập đầy đủ thông tin.');
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
                throw new RuntimeException(
                    'Không tìm thấy phiếu báo hỏng.'
                );
            }

            if (in_array(
                $report['status'],
                ['resolved', 'cancelled'],
                true
            )) {
                throw new RuntimeException(
                    'Phiếu đã kết thúc, không thể chỉnh sửa.'
                );
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
                    'Thiết bị không tồn tại hoặc đã ngừng sử dụng.'
                );
            }

            $stmt = $pdo->prepare("
                UPDATE maintenance_reports

                SET
                    equipment_id = ?,
                    title = ?,
                    description = ?

                WHERE report_id = ?
            ");

            $stmt->execute([
                $equipmentId,
                $title,
                $description,
                $reportId
            ]);

            $history = $pdo->prepare("
                INSERT INTO maintenance_history
                    (
                        report_id,
                        user_id,
                        old_status,
                        new_status,
                        action_note
                    )

                VALUES (?, ?, ?, ?, ?)
            ");

            $history->execute([
                $reportId,
                $userId,
                $report['status'],
                $report['status'],
                'Quản lý chỉnh sửa thông tin phiếu báo hỏng.'
            ]);

            $pdo->commit();

            $this->redirectToList();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Report update error: ' . $e->getMessage());

            exit(
                'Lỗi cập nhật phiếu: '
                . $this->escapeError($e)
            );
        }
    }

    /**
     * 6. PHÂN CÔNG KỸ THUẬT VIÊN
     *
     * Chỉ manager/admin được phân công.
     */
    public function assign()
    {
        global $pdo;

        $this->requireRoles(['manager']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToList();
        }

        $userId = $this->getUserId();

        $reportId = (int)($_POST['report_id'] ?? 0);
        $technicianId = (int)($_POST['technician_id'] ?? 0);

        if ($reportId <= 0 || $technicianId <= 0) {
            exit('Thông tin phân công không hợp lệ.');
        }

        try {
            $pdo->beginTransaction();

            // Khóa phiếu để tránh hai quản lý phân công cùng lúc
            $stmt = $pdo->prepare("
                SELECT
                    report_id,
                    status,
                    assigned_to

                FROM maintenance_reports

                WHERE report_id = ?

                FOR UPDATE
            ");

            $stmt->execute([$reportId]);

            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                throw new RuntimeException(
                    'Không tìm thấy phiếu báo hỏng.'
                );
            }

            if (in_array(
                $report['status'],
                ['resolved', 'cancelled'],
                true
            )) {
                throw new RuntimeException(
                    'Không thể phân công phiếu đã kết thúc.'
                );
            }

            // Chỉ tài khoản đang hoạt động có role technician
            // mới được nhận phiếu
            $stmt = $pdo->prepare("
                SELECT u.user_id

                FROM users u

                JOIN user_roles ur
                    ON u.user_id = ur.user_id

                JOIN roles r
                    ON ur.role_id = r.role_id

                WHERE u.user_id = ?
                  AND u.status = 'active'
                  AND r.role_name = 'technician'

                LIMIT 1
            ");

            $stmt->execute([$technicianId]);

            if (!$stmt->fetch()) {
                throw new RuntimeException(
                    'Kỹ thuật viên không hợp lệ hoặc tài khoản đã bị khóa.'
                );
            }

            $oldTechnicianId = $report['assigned_to'];

            $stmt = $pdo->prepare("
                UPDATE maintenance_reports

                SET assigned_to = ?

                WHERE report_id = ?
            ");

            $stmt->execute([
                $technicianId,
                $reportId
            ]);

            // Ghi nhận việc phân công trong lịch sử
            $stmt = $pdo->prepare("
                INSERT INTO maintenance_history
                    (
                        report_id,
                        user_id,
                        old_status,
                        new_status,
                        action_note
                    )

                VALUES (?, ?, ?, ?, ?)
            ");

            $note = $oldTechnicianId
                ? 'Quản lý thay đổi kỹ thuật viên được phân công.'
                : 'Quản lý phân công kỹ thuật viên xử lý.';

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

            exit(
                'Lỗi phân công kỹ thuật viên: '
                . $this->escapeError($e)
            );
        }
    }

    /**
     * 7. XEM LỊCH SỬ BẢO TRÌ
     *
     * Manager: xem lịch sử tất cả phiếu.
     * Technician: chỉ xem lịch sử phiếu được giao.
     * Teacher: chỉ xem lịch sử phiếu do mình tạo.
     */
    public function history()
    {
        global $pdo;

        $this->requireRoles([
            'manager',
            'teacher',
            'technician'
        ]);

        $userId = $this->getUserId();
        $reportId = (int)($_GET['id'] ?? 0);

        if ($reportId <= 0) {
            $this->redirectToList();
        }

        $isManager = $this->hasRole('admin')
            || $this->hasRole('manager');

        try {
            $checkSql = "
                SELECT report_id
                FROM maintenance_reports
                WHERE report_id = ?
            ";

            $params = [$reportId];

            if (!$isManager) {
                if ($this->hasRole('technician')) {
                    $checkSql .= " AND assigned_to = ?";
                } elseif ($this->hasRole('teacher')) {
                    $checkSql .= " AND reporter_id = ?";
                } else {
                    http_response_code(403);
                    exit('Bạn không có quyền xem lịch sử phiếu này.');
                }

                $params[] = $userId;
            }

            $check = $pdo->prepare($checkSql);
            $check->execute($params);

            if (!$check->fetch()) {
                http_response_code(403);
                exit('Không tìm thấy phiếu hoặc bạn không có quyền xem.');
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

                LEFT JOIN users u
                    ON mh.user_id = u.user_id

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

                JOIN equipment e
                    ON mr.equipment_id = e.equipment_id

                WHERE mr.report_id = ?
            ");

            $report->execute([$reportId]);

            $reportInfo = $report->fetch(PDO::FETCH_ASSOC);

            require __DIR__ . '/../views/report_history.php';

        } catch (Throwable $e) {
            error_log('Report history error: ' . $e->getMessage());

            exit(
                'Lỗi tải lịch sử bảo trì: '
                . $this->escapeError($e)
            );
        }
    }

    /**
     * 8. CẬP NHẬT TRẠNG THÁI PHIẾU
     *
     * Technician chỉ được cập nhật phiếu được giao cho mình.
     */
    public function updateStatus()
    {
        global $pdo;

        $this->requireRoles(['technician']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToList();
        }

        $userId = $this->getUserId();

        $reportId = (int)($_POST['report_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $note = trim($_POST['resolution_note'] ?? '');

        if (
            $reportId <= 0 ||
            !in_array($newStatus, ['processing', 'resolved'], true)
        ) {
            exit('Thông tin cập nhật không hợp lệ.');
        }

        if ($newStatus === 'resolved' && $note === '') {
            exit('Vui lòng nhập ghi chú trước khi hoàn thành phiếu.');
        }

        try {
            $pdo->beginTransaction();

            // Kiểm tra phiếu có được giao cho chính kỹ thuật viên này không
            $stmt = $pdo->prepare("
                SELECT
                    report_id,
                    assigned_to,
                    status

                FROM maintenance_reports

                WHERE report_id = ?
                  AND assigned_to = ?

                FOR UPDATE
            ");

            $stmt->execute([
                $reportId,
                $userId
            ]);

            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                throw new RuntimeException(
                    'Phiếu không tồn tại hoặc không được giao cho bạn.'
                );
            }

            $oldStatus = $report['status'];

            if (in_array(
                $oldStatus,
                ['resolved', 'cancelled'],
                true
            )) {
                throw new RuntimeException(
                    'Phiếu đã kết thúc, không thể cập nhật tiếp.'
                );
            }

            // Quy định chuyển trạng thái:
            // reported -> processing
            // processing -> resolved
            if (
                ($oldStatus === 'reported' && $newStatus !== 'processing') ||
                ($oldStatus === 'processing' && $newStatus !== 'resolved')
            ) {
                throw new RuntimeException(
                    'Không thể chuyển trạng thái theo trình tự này.'
                );
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
                  AND assigned_to = ?
            ");

            $stmt->execute([
                $newStatus,
                $newStatus,
                $note !== '' ? $note : null,
                $reportId,
                $userId
            ]);

            $historyNote = $note !== ''
                ? $note
                : 'Kỹ thuật viên bắt đầu xử lý phiếu.';

            $stmt = $pdo->prepare("
                INSERT INTO maintenance_history
                    (
                        report_id,
                        user_id,
                        old_status,
                        new_status,
                        action_note
                    )

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

            exit(
                'Lỗi cập nhật trạng thái: '
                . $this->escapeError($e)
            );
        }
    }
}