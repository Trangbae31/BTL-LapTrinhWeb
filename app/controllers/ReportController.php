<?php

require_once __DIR__ . '/../core/CSRF.php';

class ReportController
{
    private ?array $rolesCache = null;

    private function db(): PDO
    {
        global $pdo;

        if (!$pdo instanceof PDO) {
            throw new RuntimeException('Kết nối cơ sở dữ liệu chưa sẵn sàng.');
        }

        return $pdo;
    }

    private function userId(): int
    {
        return (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
    }

    private function roles(): array
    {
        if ($this->rolesCache !== null) {
            return $this->rolesCache;
        }

        $stmt = $this->db()->prepare("
            SELECT r.role_name
            FROM user_roles ur
            INNER JOIN roles r ON r.role_id = ur.role_id
            WHERE ur.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $this->userId()]);

        $this->rolesCache = array_values(array_unique(
            array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN))
        ));

        return $this->rolesCache;
    }

    private function hasRole(string $role): bool
    {
        return in_array($role, $this->roles(), true);
    }

    private function isManager(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('manager');
    }

    private function requireRoles(array $allowedRoles): void
    {
        if ($this->userId() <= 0) {
            header('Location: index.php?action=login');
            exit();
        }

        if ($this->hasRole('admin') || count(array_intersect($allowedRoles, $this->roles())) > 0) {
            return;
        }

        http_response_code(403);
        exit('Bạn không có quyền thực hiện chức năng này.');
    }

    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }

        CSRF::verifyOrFail();
    }

    private function postInt(string $key): int
    {
        $value = $_POST[$key] ?? null;
        if (!is_string($value) && !is_int($value)) {
            return 0;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT);
        return $id !== false ? (int)$id : 0;
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    private function redirectToList(): void
    {
        header('Location: index.php?action=report_list');
        exit();
    }

    private function fail(string $message): void
    {
        http_response_code(400);
        exit(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    }

    private function addEquipmentHistory(
        PDO $db,
        int $equipmentId,
        int $userId,
        ?string $oldStatus,
        string $newStatus,
        string $note
    ): void {
        if ($oldStatus === $newStatus) {
            return;
        }

        $stmt = $db->prepare("
            INSERT INTO equipment_history (
                equipment_id, user_id, old_status, new_status, change_note
            )
            VALUES (:equipment_id, :user_id, :old_status, :new_status, :note)
        ");
        $stmt->execute([
            'equipment_id' => $equipmentId,
            'user_id' => $userId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note
        ]);
    }

    private function uploadReportImages(PDO $db, int $reportId): array
    {
        if (
            !isset($_FILES['images'])
            || !is_array($_FILES['images']['name'] ?? null)
        ) {
            return [];
        }

        $files = $_FILES['images'];
        $count = count($files['name']);

        if ($count > 5) {
            throw new RuntimeException('Bạn chỉ được tải lên tối đa 5 ảnh.');
        }

        $uploadDir = dirname(__DIR__, 2)
            . '/public/uploads/maintenance_reports/';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
        }

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        $savedPaths = [];
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        try {
            for ($i = 0; $i < $count; $i++) {
                $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;

                if ($error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($error !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Có ảnh tải lên không thành công.');
                }

                $tmpName = $files['tmp_name'][$i] ?? '';

                if (!is_uploaded_file($tmpName)) {
                    throw new RuntimeException('Tệp ảnh không hợp lệ.');
                }

                if (($files['size'][$i] ?? 0) > 5 * 1024 * 1024) {
                    throw new RuntimeException('Mỗi ảnh không được vượt quá 5 MB.');
                }

                $mime = $finfo->file($tmpName);

                if (!isset($allowedTypes[$mime])) {
                    throw new RuntimeException(
                        'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.'
                    );
                }

                $extension = $allowedTypes[$mime];
                $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
                $destination = $uploadDir . $fileName;

                if (!move_uploaded_file($tmpName, $destination)) {
                    throw new RuntimeException('Không thể lưu ảnh tải lên.');
                }

                $relativePath = 'uploads/maintenance_reports/' . $fileName;
                $savedPaths[] = $relativePath;

                $stmt = $db->prepare("
                    INSERT INTO maintenance_report_images
                        (report_id, image_path, original_name)
                    VALUES
                        (:report_id, :image_path, :original_name)
                ");

                $originalName = basename(
                    (string)($files['name'][$i] ?? 'image')
                );

                $stmt->execute([
                    'report_id' => $reportId,
                    'image_path' => $relativePath,
                    'original_name' => $originalName
                ]);
            }

            return $savedPaths;
        } catch (Throwable $e) {
            foreach ($savedPaths as $path) {
                $fullPath = dirname(__DIR__, 2) . '/public/' . $path;
                if (is_file($fullPath)) {
                    unlink($fullPath);
                }
            }

            throw $e;
        }
    }

    private function deleteUploadedFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $fullPath = dirname(__DIR__, 2) . '/public/' . $path;

            if (is_file($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    public function index(): void
    {
        $this->requireRoles(['admin', 'manager', 'teacher', 'technician']);

        $db = $this->db();
        $userId = $this->userId();
        $roles = $this->roles();

        $isAdmin = in_array('admin', $roles, true);
        $isManager = $this->isManager();
        $isTechnician = in_array('technician', $roles, true);
        $isTeacher = in_array('teacher', $roles, true);

        // Nhận từ khóa tìm kiếm
        $keyword = isset($_GET['keyword']) && is_string($_GET['keyword'])
            ? trim($_GET['keyword'])
            : '';

        // Bỏ dấu # ở đầu mã phiếu: #1 -> 1
        $searchKeyword = preg_replace('/^#\s*/u', '', $keyword);
        $searchKeyword = trim($searchKeyword);

        // Nhận trạng thái
        $status = isset($_GET['status']) && is_string($_GET['status'])
            ? trim($_GET['status'])
            : '';

        $allowedStatuses = [
            'reported',
            'processing',
            'resolved',
            'cancelled'
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        // Bộ lọc chỉ dành cho Manager/Admin
        $reporterId = isset($_GET['reporter_id'])
            ? (int)$_GET['reporter_id']
            : 0;

        $technicianId = isset($_GET['technician_id'])
            ? (int)$_GET['technician_id']
            : 0;

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
            INNER JOIN equipment e
                ON e.equipment_id = mr.equipment_id
            LEFT JOIN users reporter
                ON reporter.user_id = mr.reporter_id
            LEFT JOIN users technician
                ON technician.user_id = mr.assigned_to
        ";

        $conditions = [];
        $params = [];

        // Giữ giới hạn dữ liệu theo quyền
        if (!$isManager) {
            if ($isTechnician) {
                $conditions[] = "mr.assigned_to = :scope_user_id";
                $params['scope_user_id'] = $userId;
            } elseif ($isTeacher) {
                $conditions[] = "mr.reporter_id = :scope_user_id";
                $params['scope_user_id'] = $userId;
            } else {
                http_response_code(403);
                exit('Bạn không có quyền xem danh sách báo hỏng.');
            }
        }

        // Tìm kiếm
        if ($searchKeyword !== '') {

            // Nếu từ khóa chỉ chứa số (có thể đã bỏ dấu #)
            // thì chỉ tìm chính xác mã phiếu
            if (ctype_digit($searchKeyword)) {

                $conditions[] = "mr.report_id = :exact_report_id";
                $params['exact_report_id'] = (int)$searchKeyword;

            } else {

                // Nếu là chữ thì tìm trong thông tin thiết bị và nội dung báo hỏng
                $conditions[] = "(
                    e.equipment_code LIKE :search_code
                    OR e.equipment_name LIKE :search_name
                    OR mr.title LIKE :search_title
                    OR mr.description LIKE :search_description
                )";

                $searchValue = '%' . $searchKeyword . '%';

                $params['search_code'] = $searchValue;
                $params['search_name'] = $searchValue;
                $params['search_title'] = $searchValue;
                $params['search_description'] = $searchValue;
            }
        }

        // Lọc trạng thái
        if ($status !== '') {
            $conditions[] = "mr.status = :filter_status";
            $params['filter_status'] = $status;
        }

        // Manager/Admin được lọc người báo và kỹ thuật viên
        if ($isManager) {
            if ($reporterId > 0) {
                $conditions[] = "mr.reporter_id = :filter_reporter";
                $params['filter_reporter'] = $reporterId;
            }

            if ($technicianId === -1) {
                $conditions[] = "mr.assigned_to IS NULL";
            } elseif ($technicianId > 0) {
                $conditions[] = "mr.assigned_to = :filter_technician";
                $params['filter_technician'] = $technicianId;
            }
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY mr.reported_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Danh sách kỹ thuật viên
        $technicians = [];

        // Danh sách người báo hỏng
        $reporters = [];

        if ($isManager) {
            $technicians = $db->query("
                SELECT DISTINCT u.user_id, u.full_name
                FROM users u
                INNER JOIN user_roles ur ON ur.user_id = u.user_id
                INNER JOIN roles r ON r.role_id = ur.role_id
                WHERE r.role_name = 'technician'
                AND u.status = 'active'
                ORDER BY u.full_name
            ")->fetchAll(PDO::FETCH_ASSOC);

            $reporters = $db->query("
                SELECT DISTINCT u.user_id, u.full_name
                FROM users u
                INNER JOIN maintenance_reports mr
                    ON mr.reporter_id = u.user_id
                ORDER BY u.full_name
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        // Admin kế thừa quyền quản lý của Manager
        if ($isAdmin) {
            $isManager = true;
            $isAdmin = false;
        }

        $filters = [
            'keyword' => $keyword,
            'status' => $status,
            'reporter_id' => $reporterId,
            'technician_id' => $technicianId
        ];

        $currentUserId = $userId;

        require __DIR__ . '/../views/report_list.php';
    }

    public function create(): void
    {
        $this->requireRoles(['teacher', 'manager', 'admin']);

        $db = $this->db();
        $equipment = $db->query("
            SELECT e.equipment_id, e.equipment_code, e.equipment_name
            FROM equipment e
            WHERE e.status <> 'retired'
              AND NOT EXISTS (
                  SELECT 1
                  FROM maintenance_reports mr
                  WHERE mr.equipment_id = e.equipment_id
                    AND mr.status IN ('reported', 'processing')
              )
            ORDER BY e.equipment_name
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/report_form.php';
    }

    public function store(): void
    {
        $this->requireRoles(['teacher', 'manager', 'admin']);
        $this->requirePost();

        $db = $this->db();
        $userId = $this->userId();
        $equipmentId = $this->postInt('equipment_id');
        $title = $this->postString('title');
        $description = $this->postString('description');

        if (
            $equipmentId <= 0
            || $title === ''
            || mb_strlen($title, 'UTF-8') > 200
            || $description === ''
        ) {
            $this->fail('Vui lòng nhập đầy đủ và đúng thông tin báo hỏng.');
        }

        $uploadedPaths = [];

        try {
            $db->beginTransaction();

            $lock = $db->prepare("
                SELECT equipment_id, status
                FROM equipment
                WHERE equipment_id = :equipment_id
                FOR UPDATE
            ");
            $lock->execute(['equipment_id' => $equipmentId]);
            $equipment = $lock->fetch(PDO::FETCH_ASSOC);

            if (!$equipment || $equipment['status'] === 'retired') {
                throw new RuntimeException('Thiết bị không tồn tại hoặc đã ngừng sử dụng.');
            }

            $active = $db->prepare("
                SELECT report_id
                FROM maintenance_reports
                WHERE equipment_id = :equipment_id
                  AND status IN ('reported', 'processing')
                LIMIT 1
            ");
            $active->execute(['equipment_id' => $equipmentId]);

            if ($active->fetchColumn() !== false) {
                throw new RuntimeException('Thiết bị đã có phiếu bảo trì chưa hoàn tất.');
            }

            $insert = $db->prepare("
                INSERT INTO maintenance_reports (
                    equipment_id, reporter_id, title, description, status
                )
                VALUES (
                    :equipment_id, :reporter_id, :title, :description, 'reported'
                )
            ");
            $insert->execute([
                'equipment_id' => $equipmentId,
                'reporter_id' => $userId,
                'title' => $title,
                'description' => $description
            ]);

            $reportId = (int)$db->lastInsertId();

            // Lưu ảnh đính kèm của phiếu báo hỏng
            $uploadedPaths = $this->uploadReportImages($db, $reportId);

            $history = $db->prepare("
                INSERT INTO maintenance_history (
                    report_id, user_id, old_status, new_status, action_note
                )
                VALUES (:report_id, :user_id, NULL, 'reported', :note)
            ");
            $history->execute([
                'report_id' => $reportId,
                'user_id' => $userId,
                'note' => 'Tạo phiếu báo hỏng mới.'
            ]);

            $updateEquipment = $db->prepare("
                UPDATE equipment
                SET status = 'broken'
                WHERE equipment_id = :equipment_id
                  AND status <> 'retired'
            ");
            $updateEquipment->execute(['equipment_id' => $equipmentId]);

            $this->addEquipmentHistory(
                $db,
                $equipmentId,
                $userId,
                $equipment['status'],
                'broken',
                'Thiết bị được chuyển sang trạng thái hỏng do phát sinh phiếu báo hỏng.'
            );

            $db->commit();
            $this->redirectToList();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            // Xóa các file đã tải lên nếu lưu phiếu thất bại
            $this->deleteUploadedFiles($uploadedPaths);

            error_log('Report store error: ' . $e->getMessage());
            $this->fail($e instanceof RuntimeException
                ? $e->getMessage()
                : 'Không thể lưu phiếu báo hỏng.');
        }
    }

    public function assign(): void
    {
        $this->requireRoles(['manager', 'admin']);
        $this->requirePost();

        $db = $this->db();
        $userId = $this->userId();
        $reportId = $this->postInt('report_id');
        $technicianId = $this->postInt('technician_id');

        if ($reportId <= 0 || $technicianId <= 0) {
            $this->fail('Thông tin phân công không hợp lệ.');
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                SELECT report_id, status, assigned_to
                FROM maintenance_reports
                WHERE report_id = :report_id
                FOR UPDATE
            ");
            $stmt->execute(['report_id' => $reportId]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                throw new RuntimeException('Không tìm thấy phiếu báo hỏng.');
            }

            if (in_array($report['status'], ['resolved', 'cancelled'], true)) {
                throw new RuntimeException('Không thể phân công phiếu đã kết thúc.');
            }

            $tech = $db->prepare("
                SELECT u.user_id
                FROM users u
                INNER JOIN user_roles ur ON ur.user_id = u.user_id
                INNER JOIN roles r ON r.role_id = ur.role_id
                WHERE u.user_id = :technician_id
                  AND u.status = 'active'
                  AND r.role_name = 'technician'
                LIMIT 1
            ");
            $tech->execute(['technician_id' => $technicianId]);

            if ($tech->fetchColumn() === false) {
                throw new RuntimeException('Kỹ thuật viên không hợp lệ hoặc đã bị khóa.');
            }

            $update = $db->prepare("
                UPDATE maintenance_reports
                SET assigned_to = :technician_id
                WHERE report_id = :report_id
            ");
            $update->execute([
                'technician_id' => $technicianId,
                'report_id' => $reportId
            ]);

            $note = !empty($report['assigned_to'])
                ? 'Quản lý thay đổi kỹ thuật viên được phân công.'
                : 'Quản lý phân công kỹ thuật viên xử lý.';

            $history = $db->prepare("
                INSERT INTO maintenance_history (
                    report_id, user_id, old_status, new_status, action_note
                )
                VALUES (:report_id, :user_id, :old_status, :new_status, :note)
            ");
            $history->execute([
                'report_id' => $reportId,
                'user_id' => $userId,
                'old_status' => $report['status'],
                'new_status' => $report['status'],
                'note' => $note
            ]);

            $db->commit();
            $this->redirectToList();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Report assign error: ' . $e->getMessage());
            $this->fail($e instanceof RuntimeException
                ? $e->getMessage()
                : 'Không thể phân công kỹ thuật viên.');
        }
    }

    public function history(): void
    {
        $this->requireRoles(['admin', 'manager', 'teacher', 'technician']);

        $db = $this->db();
        $userId = $this->userId();
        $reportId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $reportId = $reportId !== false && $reportId !== null ? (int)$reportId : 0;

        if ($reportId <= 0) {
            $this->redirectToList();
        }

        $sql = "
            SELECT
                mr.report_id,
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
            INNER JOIN equipment e
                ON e.equipment_id = mr.equipment_id
            LEFT JOIN users reporter
                ON reporter.user_id = mr.reporter_id
            LEFT JOIN users technician
                ON technician.user_id = mr.assigned_to
            WHERE mr.report_id = :report_id
        ";

        $params = ['report_id' => $reportId];

        if (!$this->isManager()) {
            if ($this->hasRole('technician')) {
                $sql .= " AND mr.assigned_to = :user_id";
            } elseif ($this->hasRole('teacher')) {
                $sql .= " AND mr.reporter_id = :user_id";
            } else {
                http_response_code(403);
                exit('Bạn không có quyền xem lịch sử phiếu này.');
            }
            $params['user_id'] = $userId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $reportInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reportInfo) {
            http_response_code(404);
            exit('Không tìm thấy phiếu hoặc bạn không có quyền xem.');
        }

        $historyStmt = $db->prepare("
            SELECT mh.history_id, mh.old_status, mh.new_status,
                   mh.action_note, mh.changed_at, u.full_name AS user_name
            FROM maintenance_history mh
            LEFT JOIN users u ON u.user_id = mh.user_id
            WHERE mh.report_id = :report_id
            ORDER BY mh.changed_at DESC, mh.history_id DESC
        ");
        $historyStmt->execute(['report_id' => $reportId]);
        $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy toàn bộ ảnh đính kèm của phiếu
        $imageStmt = $db->prepare("
            SELECT image_id, image_path, original_name
            FROM maintenance_report_images
            WHERE report_id = :report_id
            ORDER BY image_id ASC
        ");

        $imageStmt->execute(['report_id' => $reportId]);
        $reportImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/report_history.php';
    }

    public function updateStatus(): void
    {
        $this->requireRoles(['technician']);
        $this->requirePost();

        $db = $this->db();
        $userId = $this->userId();
        $reportId = $this->postInt('report_id');
        $newStatus = $this->postString('status');
        $note = $this->postString('resolution_note');

        if (
            $reportId <= 0
            || !in_array($newStatus, ['processing', 'resolved'], true)
        ) {
            $this->fail('Thông tin cập nhật trạng thái không hợp lệ.');
        }

        if ($newStatus === 'resolved' && $note === '') {
            $this->fail('Vui lòng nhập kết quả xử lý trước khi hoàn thành phiếu.');
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                SELECT report_id, equipment_id, assigned_to, status
                FROM maintenance_reports
                WHERE report_id = :report_id
                  AND assigned_to = :user_id
                FOR UPDATE
            ");
            $stmt->execute([
                'report_id' => $reportId,
                'user_id' => $userId
            ]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                throw new RuntimeException('Phiếu không tồn tại hoặc không được giao cho bạn.');
            }

            $oldStatus = $report['status'];
            $equipmentId = (int)$report['equipment_id'];

            $validTransition = (
                $oldStatus === 'reported' && $newStatus === 'processing'
            ) || (
                $oldStatus === 'processing' && $newStatus === 'resolved'
            );

            if (!$validTransition) {
                throw new RuntimeException('Không thể chuyển trạng thái theo trình tự này.');
            }

            // Khóa thiết bị để việc cập nhật phiếu và trạng thái thiết bị đồng bộ.
            $equipmentLock = $db->prepare("
                SELECT status
                FROM equipment
                WHERE equipment_id = :equipment_id
                FOR UPDATE
            ");
            $equipmentLock->execute(['equipment_id' => $equipmentId]);
            $equipment = $equipmentLock->fetch(PDO::FETCH_ASSOC);

            if (!$equipment) {
                throw new RuntimeException('Không tìm thấy thiết bị của phiếu.');
            }

            $updateReport = $db->prepare("
                UPDATE maintenance_reports
                SET status = :status,
                    resolved_at = CASE
                        WHEN :resolved_status = 'resolved' THEN CURRENT_TIMESTAMP
                        ELSE NULL
                    END,
                    resolution_note = :resolution_note
                WHERE report_id = :report_id
            ");
            $updateReport->execute([
                'status' => $newStatus,
                'resolved_status' => $newStatus,
                'resolution_note' => $newStatus === 'resolved' ? $note : null,
                'report_id' => $reportId
            ]);

            $history = $db->prepare("
                INSERT INTO maintenance_history (
                    report_id, user_id, old_status, new_status, action_note
                )
                VALUES (:report_id, :user_id, :old_status, :new_status, :note)
            ");
            $history->execute([
                'report_id' => $reportId,
                'user_id' => $userId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $newStatus === 'resolved'
                    ? $note
                    : 'Kỹ thuật viên bắt đầu xử lý phiếu.'
            ]);

            if ($equipment['status'] !== 'retired') {
                $open = $db->prepare("
                    SELECT
                        SUM(status = 'processing') AS processing_count,
                        SUM(status = 'reported') AS reported_count
                    FROM maintenance_reports
                    WHERE equipment_id = :equipment_id
                      AND status IN ('reported', 'processing')
                ");
                $open->execute(['equipment_id' => $equipmentId]);
                $counts = $open->fetch(PDO::FETCH_ASSOC);

                $equipmentStatus = ((int)$counts['processing_count'] > 0)
                    ? 'maintenance'
                    : (((int)$counts['reported_count'] > 0) ? 'broken' : 'available');

                $updateEquipment = $db->prepare("
                    UPDATE equipment
                    SET status = :status
                    WHERE equipment_id = :equipment_id
                      AND status <> 'retired'
                ");
                $updateEquipment->execute([
                    'status' => $equipmentStatus,
                    'equipment_id' => $equipmentId
                ]);

                $this->addEquipmentHistory(
                    $db,
                    $equipmentId,
                    $userId,
                    $equipment['status'],
                    $equipmentStatus,
                    'Đồng bộ trạng thái thiết bị sau khi cập nhật phiếu bảo trì.'
                );
            }

            $db->commit();
            $this->redirectToList();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Report status update error: ' . $e->getMessage());
            $this->fail($e instanceof RuntimeException
                ? $e->getMessage()
                : 'Không thể cập nhật trạng thái phiếu.');
        }
    }
}
