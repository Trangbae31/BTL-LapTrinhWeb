<?php

require_once __DIR__ . '/../../config/database.php';

class EquipmentModel
{
    private PDO $db;

    private const STATUSES = [
        'available',
        'maintenance',
        'broken',
        'retired'
    ];

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getAll(): array
    {
        $sql = "
            SELECT e.*,
                   CASE
                     WHEN EXISTS (SELECT 1 FROM maintenance_reports mr WHERE mr.equipment_id = e.equipment_id AND mr.status = 'processing') THEN 'maintenance'
                     WHEN EXISTS (SELECT 1 FROM maintenance_reports mr WHERE mr.equipment_id = e.equipment_id AND mr.status = 'reported') THEN 'broken'
                     ELSE e.status
                   END AS availability_status,
                   r.room_code, r.room_name, t.type_name
            FROM equipment e
            LEFT JOIN rooms r ON r.room_id = e.room_id
            LEFT JOIN equipment_types t
                ON t.equipment_type_id = e.equipment_type_id
            ORDER BY e.equipment_id DESC
        ";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search(string $keyword): array
    {
        $like = '%' . trim($keyword) . '%';

        $stmt = $this->db->prepare("
            SELECT e.*,
                   CASE
                     WHEN EXISTS (SELECT 1 FROM maintenance_reports mr WHERE mr.equipment_id = e.equipment_id AND mr.status = 'processing') THEN 'maintenance'
                     WHEN EXISTS (SELECT 1 FROM maintenance_reports mr WHERE mr.equipment_id = e.equipment_id AND mr.status = 'reported') THEN 'broken'
                     ELSE e.status
                   END AS availability_status,
                   r.room_code, r.room_name, t.type_name
            FROM equipment e
            LEFT JOIN rooms r ON r.room_id = e.room_id
            LEFT JOIN equipment_types t
                ON t.equipment_type_id = e.equipment_type_id
            WHERE e.equipment_code LIKE :code
               OR e.equipment_name LIKE :name
               OR e.brand LIKE :brand
               OR r.room_code LIKE :room_code
               OR r.room_name LIKE :room_name
               OR t.type_name LIKE :type_name
            ORDER BY e.equipment_id DESC
        ");

        $stmt->execute([
            'code' => $like,
            'name' => $like,
            'brand' => $like,
            'room_code' => $like,
            'room_name' => $like,
            'type_name' => $like
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM equipment
            WHERE equipment_id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getDetailById(int $id): ?array
    {
        if ($id <= 0) return null;
        $stmt = $this->db->prepare("
            SELECT e.*,
                   CASE
                     WHEN EXISTS (SELECT 1 FROM maintenance_reports mr WHERE mr.equipment_id = e.equipment_id AND mr.status = 'processing') THEN 'maintenance'
                     WHEN EXISTS (SELECT 1 FROM maintenance_reports mr WHERE mr.equipment_id = e.equipment_id AND mr.status = 'reported') THEN 'broken'
                     ELSE e.status
                   END AS availability_status,
                   r.room_code, r.room_name, t.type_name
            FROM equipment e
            LEFT JOIN rooms r ON r.room_id = e.room_id
            LEFT JOIN equipment_types t ON t.equipment_type_id = e.equipment_type_id
            WHERE e.equipment_id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getRooms(): array
    {
        return $this->db->query("
            SELECT room_id, room_code, room_name
            FROM rooms
            WHERE status = 'active'
            ORDER BY room_code
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilterRooms(): array
    {
        return $this->db->query("
            SELECT room_id, room_code, room_name
            FROM rooms
            ORDER BY room_code
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTypes(): array
    {
        return $this->db->query("
            SELECT equipment_type_id, type_name
            FROM equipment_types
            ORDER BY type_name
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function lockActiveRoom(int $roomId): bool
    {
        $stmt = $this->db->prepare("
            SELECT room_id
            FROM rooms
            WHERE room_id = :room_id
              AND status = 'active'
            FOR UPDATE
        ");
        $stmt->execute(['room_id' => $roomId]);

        return $stmt->fetchColumn() !== false;
    }

    private function typeExists(int $typeId): bool
    {
        $stmt = $this->db->prepare("
            SELECT equipment_type_id
            FROM equipment_types
            WHERE equipment_type_id = :type_id
        ");
        $stmt->execute(['type_id' => $typeId]);

        return $stmt->fetchColumn() !== false;
    }

    private function hasOpenReport(int $equipmentId): bool
    {
        $stmt = $this->db->prepare("
            SELECT report_id
            FROM maintenance_reports
            WHERE equipment_id = :equipment_id
              AND status IN ('reported', 'processing')
            LIMIT 1
        ");
        $stmt->execute(['equipment_id' => $equipmentId]);

        return $stmt->fetchColumn() !== false;
    }

    private function recordStatusHistory(
        int $equipmentId,
        int $userId,
        ?string $oldStatus,
        string $newStatus,
        string $note
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO equipment_history (
                equipment_id,
                user_id,
                old_status,
                new_status,
                change_note
            )
            VALUES (
                :equipment_id,
                :user_id,
                :old_status,
                :new_status,
                :change_note
            )
        ");

        $stmt->execute([
            'equipment_id' => $equipmentId,
            'user_id' => $userId > 0 ? $userId : null,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'change_note' => $note
        ]);
    }

    private function validateData(array $data): bool
    {
        return (int)($data['room_id'] ?? 0) > 0
            && (int)($data['equipment_type_id'] ?? 0) > 0
            && trim((string)($data['equipment_code'] ?? '')) !== ''
            && mb_strlen(trim((string)$data['equipment_code']), 'UTF-8') <= 50
            && trim((string)($data['equipment_name'] ?? '')) !== ''
            && mb_strlen(trim((string)$data['equipment_name']), 'UTF-8') <= 150
            && mb_strlen(trim((string)($data['brand'] ?? '')), 'UTF-8') <= 100
            && in_array((string)($data['status'] ?? ''), self::STATUSES, true);
    }

    public function create(array $data, int $userId): bool
    {
        if (!$this->validateData($data) || $userId <= 0) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            if (
                !$this->lockActiveRoom((int)$data['room_id'])
                || !$this->typeExists((int)$data['equipment_type_id'])
            ) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                INSERT INTO equipment (
                    room_id,
                    equipment_type_id,
                    equipment_code,
                    equipment_name,
                    brand,
                    purchase_date,
                    status,
                    description
                )
                VALUES (
                    :room_id,
                    :type_id,
                    :code,
                    :name,
                    :brand,
                    :purchase_date,
                    :status,
                    :description
                )
            ");

            $stmt->execute([
                'room_id' => (int)$data['room_id'],
                'type_id' => (int)$data['equipment_type_id'],
                'code' => trim($data['equipment_code']),
                'name' => trim($data['equipment_name']),
                'brand' => trim((string)($data['brand'] ?? '')) ?: null,
                'purchase_date' => ($data['purchase_date'] ?? '') ?: null,
                'status' => $data['status'],
                'description' => trim((string)($data['description'] ?? '')) ?: null
            ]);

            $equipmentId = (int)$this->db->lastInsertId();

            $this->recordStatusHistory(
                $equipmentId,
                $userId,
                null,
                $data['status'],
                'Tạo thiết bị mới.'
            );

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Equipment create error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $data, int $userId): bool
    {
        if (
            $id <= 0
            || $userId <= 0
            || !$this->validateData($data)
        ) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $lock = $this->db->prepare("
                SELECT status
                FROM equipment
                WHERE equipment_id = :id
                FOR UPDATE
            ");
            $lock->execute(['id' => $id]);
            $current = $lock->fetch(PDO::FETCH_ASSOC);

            if (!$current) {
                $this->db->rollBack();
                return false;
            }

            if (
                !$this->lockActiveRoom((int)$data['room_id'])
                || !$this->typeExists((int)$data['equipment_type_id'])
            ) {
                $this->db->rollBack();
                return false;
            }

            // Không được đánh dấu sẵn sàng hoặc thanh lý khi còn phiếu bảo trì mở.
            if (
                in_array($data['status'], ['available', 'retired'], true)
                && $this->hasOpenReport($id)
            ) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE equipment
                SET room_id = :room_id,
                    equipment_type_id = :type_id,
                    equipment_code = :code,
                    equipment_name = :name,
                    brand = :brand,
                    purchase_date = :purchase_date,
                    status = :status,
                    description = :description
                WHERE equipment_id = :id
            ");

            $stmt->execute([
                'room_id' => (int)$data['room_id'],
                'type_id' => (int)$data['equipment_type_id'],
                'code' => trim($data['equipment_code']),
                'name' => trim($data['equipment_name']),
                'brand' => trim((string)($data['brand'] ?? '')) ?: null,
                'purchase_date' => ($data['purchase_date'] ?? '') ?: null,
                'status' => $data['status'],
                'description' => trim((string)($data['description'] ?? '')) ?: null,
                'id' => $id
            ]);

            if ($current['status'] !== $data['status']) {
                $this->recordStatusHistory(
                    $id,
                    $userId,
                    $current['status'],
                    $data['status'],
                    'Cập nhật trạng thái thiết bị.'
                );
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Equipment update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Không xóa cứng vì thiết bị có thể đang được lịch sử/phiếu bảo trì tham chiếu.
     * Chuyển trạng thái sang retired để giữ toàn bộ dữ liệu truy vết.
     */
    public function delete(int $id, int $userId): bool
    {
        if ($id <= 0 || $userId <= 0) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT status
                FROM equipment
                WHERE equipment_id = :id
                FOR UPDATE
            ");
            $stmt->execute(['id' => $id]);
            $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$equipment || $equipment['status'] === 'retired') {
                $this->db->rollBack();
                return false;
            }

            if ($this->hasOpenReport($id)) {
                $this->db->rollBack();
                return false;
            }

            $update = $this->db->prepare("
                UPDATE equipment
                SET status = 'retired'
                WHERE equipment_id = :id
            ");
            $update->execute(['id' => $id]);

            $this->recordStatusHistory(
                $id,
                $userId,
                $equipment['status'],
                'retired',
                'Ngừng sử dụng thiết bị từ chức năng quản lý thiết bị.'
            );

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Equipment retire error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lọc thiết bị theo từ khóa, phòng, loại, trạng thái và khoảng ngày mua.
     */
    public function getFiltered(array $filters = []): array
    {
        $sql = "
            SELECT e.*,
                   CASE
                     WHEN EXISTS (SELECT 1 FROM maintenance_reports mr WHERE mr.equipment_id = e.equipment_id AND mr.status = 'processing') THEN 'maintenance'
                     WHEN EXISTS (SELECT 1 FROM maintenance_reports mr WHERE mr.equipment_id = e.equipment_id AND mr.status = 'reported') THEN 'broken'
                     ELSE e.status
                   END AS availability_status,
                   r.room_code, r.room_name, t.type_name
            FROM equipment e
            LEFT JOIN rooms r ON r.room_id = e.room_id
            LEFT JOIN equipment_types t
                ON t.equipment_type_id = e.equipment_type_id
            WHERE 1 = 1
        ";
        $params = [];

        $keyword = trim((string)($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $sql .= " AND (
                e.equipment_code LIKE :code
                OR e.equipment_name LIKE :name
                OR e.brand LIKE :brand
                OR r.room_code LIKE :room_code
                OR r.room_name LIKE :room_name
                OR t.type_name LIKE :type_name
            )";
            $like = '%' . $keyword . '%';
            foreach (['code', 'name', 'brand', 'room_code', 'room_name', 'type_name'] as $key) {
                $params[$key] = $like;
            }
        }

        $roomId = (int)($filters['room_id'] ?? 0);
        if ($roomId > 0) {
            $sql .= " AND e.room_id = :room_id";
            $params['room_id'] = $roomId;
        }

        $typeId = (int)($filters['equipment_type_id'] ?? 0);
        if ($typeId > 0) {
            $sql .= " AND e.equipment_type_id = :type_id";
            $params['type_id'] = $typeId;
        }

        $status = (string)($filters['status'] ?? '');
        if (in_array($status, self::STATUSES, true)) {
            $sql .= " AND e.status = :status";
            $params['status'] = $status;
        }

        $dateFrom = (string)($filters['date_from'] ?? '');
        if ($dateFrom !== '' && $this->isValidDate($dateFrom)) {
            $sql .= " AND e.purchase_date >= :date_from";
            $params['date_from'] = $dateFrom;
        }

        $dateTo = (string)($filters['date_to'] ?? '');
        if ($dateTo !== '' && $this->isValidDate($dateTo)) {
            $sql .= " AND e.purchase_date <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $sql .= " ORDER BY e.equipment_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        $errors = DateTime::getLastErrors();
        return $parsed !== false
            && $parsed->format('Y-m-d') === $date
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }

    public function getEquipmentTypes(): array
    {
        return $this->db->query("
            SELECT
                t.equipment_type_id,
                t.type_name,
                t.description,
                t.created_at,
                COUNT(e.equipment_id) AS equipment_count
            FROM equipment_types t
            LEFT JOIN equipment e
                ON e.equipment_type_id = t.equipment_type_id
            GROUP BY t.equipment_type_id, t.type_name, t.description, t.created_at
            ORDER BY t.type_name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTypeById(int $typeId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT equipment_type_id, type_name, description
            FROM equipment_types
            WHERE equipment_type_id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $typeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createType(string $typeName, string $description = ''): bool
    {
        $typeName = trim($typeName);
        $description = trim($description);
        if ($typeName === '' || mb_strlen($typeName, 'UTF-8') > 100) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO equipment_types (type_name, description)
            VALUES (:type_name, :description)
        ");
        return $stmt->execute([
            'type_name' => $typeName,
            'description' => $description !== '' ? $description : null
        ]);
    }

    public function updateType(int $typeId, string $typeName, string $description = ''): bool
    {
        $typeName = trim($typeName);
        $description = trim($description);
        if ($typeId <= 0 || $typeName === '' || mb_strlen($typeName, 'UTF-8') > 100) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE equipment_types
            SET type_name = :type_name,
                description = :description
            WHERE equipment_type_id = :id
        ");
        $stmt->execute([
            'type_name' => $typeName,
            'description' => $description !== '' ? $description : null,
            'id' => $typeId
        ]);
        if ($stmt->rowCount() > 0) return true;
        return $this->getTypeById($typeId) !== null;
    }

    /**
     * Chỉ xóa loại thiết bị chưa được thiết bị nào tham chiếu.
     */
    public function deleteType(int $typeId): bool
    {
        if ($typeId <= 0) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare("
                SELECT equipment_type_id
                FROM equipment_types
                WHERE equipment_type_id = :id
                FOR UPDATE
            ");
            $lock->execute(['id' => $typeId]);
            if (!$lock->fetchColumn()) {
                $this->db->rollBack();
                return false;
            }

            $check = $this->db->prepare("
                SELECT COUNT(*)
                FROM equipment
                WHERE equipment_type_id = :id
            ");
            $check->execute(['id' => $typeId]);
            if ((int)$check->fetchColumn() > 0) {
                $this->db->rollBack();
                return false;
            }

            $delete = $this->db->prepare("
                DELETE FROM equipment_types
                WHERE equipment_type_id = :id
            ");
            $delete->execute(['id' => $typeId]);
            $success = $delete->rowCount() === 1;
            $this->db->commit();
            return $success;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Trả về trạng thái phòng và lịch đã duyệt trong khoảng thời gian yêu cầu.
     */
    public function checkRoomAvailability(int $roomId, string $startTime, string $endTime): array
    {
        $roomStmt = $this->db->prepare("
            SELECT room_id, room_code, room_name, status
            FROM rooms
            WHERE room_id = :room_id
            LIMIT 1
        ");
        $roomStmt->execute(['room_id' => $roomId]);
        $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            return ['exists' => false, 'available' => false, 'conflicts' => []];
        }

        if ($room['status'] !== 'active') {
            return ['exists' => true, 'available' => false, 'conflicts' => [], 'reason' => 'inactive'];
        }

        $stmt = $this->db->prepare("
            SELECT booking_id, title, start_time, end_time, status
            FROM bookings
            WHERE room_id = :room_id
              AND status = 'approved'
              AND start_time < :end_time
              AND end_time > :start_time
            ORDER BY start_time ASC
        ");
        $stmt->execute([
            'room_id' => $roomId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
        $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'exists' => true,
            'available' => count($conflicts) === 0,
            'room' => $room,
            'conflicts' => $conflicts
        ];
    }

}
