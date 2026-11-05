<?php

require_once __DIR__ . '/../../config/database.php';

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getRoles(): array
    {
        return $this->db->query("
            SELECT role_id, role_name, description
            FROM roles
            ORDER BY FIELD(role_name, 'admin', 'manager', 'teacher', 'technician'), role_name
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll(): array
    {
        return $this->db->query("
            SELECT
                u.user_id, u.full_name, u.email, u.status, u.created_at,
                GROUP_CONCAT(r.role_name ORDER BY r.role_name SEPARATOR ', ') AS role_names
            FROM users u
            LEFT JOIN user_roles ur ON ur.user_id = u.user_id
            LEFT JOIN roles r ON r.role_id = ur.role_id
            GROUP BY u.user_id, u.full_name, u.email, u.status, u.created_at
            ORDER BY u.user_id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.full_name, u.email, u.status
            FROM users u
            WHERE u.user_id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $roles = $this->db->prepare("SELECT role_id FROM user_roles WHERE user_id = :id");
        $roles->execute(['id' => $id]);
        $row['role_ids'] = array_map('intval', $roles->fetchAll(PDO::FETCH_COLUMN));
        return $row;
    }

    private function validRoleIds(array $roleIds): bool
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds))));
        if (!$roleIds) return false;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM roles WHERE role_id IN (" . implode(',', array_fill(0, count($roleIds), '?')) . ")");
        $stmt->execute($roleIds);
        return (int)$stmt->fetchColumn() === count($roleIds);
    }

    private function roleExists(int $roleId): bool
    {
        $stmt = $this->db->prepare("SELECT role_id FROM roles WHERE role_id = :id");
        $stmt->execute(['id' => $roleId]);
        return $stmt->fetchColumn() !== false;
    }

    public function create(array $data): bool
    {
        if (
            trim($data['full_name'] ?? '') === ''
            || !filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)
            || strlen($data['password'] ?? '') < 8
            || !$this->validRoleIds((array)($data['role_ids'] ?? []))
            || !in_array($data['status'] ?? '', ['active', 'inactive'], true)
        ) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (full_name, email, password_hash, status)
                VALUES (:full_name, :email, :password_hash, :status)
            ");
            $stmt->execute([
                'full_name' => trim($data['full_name']),
                'email' => strtolower(trim($data['email'])),
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'status' => $data['status']
            ]);
            $userId = (int)$this->db->lastInsertId();

            $role = $this->db->prepare("
                INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)
            ");
            foreach (array_unique(array_map('intval', $data['role_ids'])) as $roleId) {
                $role->execute(['user_id' => $userId, 'role_id' => $roleId]);
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        if (
            $id <= 0
            || trim($data['full_name'] ?? '') === ''
            || !filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)
            || !in_array($data['status'] ?? '', ['active', 'inactive'], true)
            || !$this->validRoleIds((array)($data['role_ids'] ?? []))
        ) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $current = $this->db->prepare("
                SELECT status
                FROM users
                WHERE user_id = :id
                FOR UPDATE
            ");
            $current->execute(['id' => $id]);
            $existing = $current->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                $this->db->rollBack();
                return false;
            }
            $oldRoleStmt = $this->db->prepare("
                SELECT r.role_name
                FROM user_roles ur
                INNER JOIN roles r ON r.role_id = ur.role_id
                WHERE ur.user_id = :id
            ");
            $oldRoleStmt->execute(['id' => $id]);
            $oldRoles = $oldRoleStmt->fetchAll(PDO::FETCH_COLUMN);

            $newRoleIds = array_unique(array_map('intval', $data['role_ids']));
            $rolePlaceholders = implode(',', array_fill(0, count($newRoleIds), '?'));
            $newRoleStmt = $this->db->prepare("SELECT role_name FROM roles WHERE role_id IN ($rolePlaceholders)");
            $newRoleStmt->execute($newRoleIds);
            $newRoleNames = $newRoleStmt->fetchAll(PDO::FETCH_COLUMN);

            // Không cho vô hiệu hóa hoặc gỡ vai trò admin cuối cùng đang hoạt động.
            if (
                in_array('admin', $oldRoles, true)
                && (!in_array('admin', $newRoleNames, true) || $data['status'] !== 'active')
            ) {
                $count = (int)$this->db->query("
                    SELECT COUNT(DISTINCT u.user_id)
                    FROM users u
                    INNER JOIN user_roles ur ON ur.user_id = u.user_id
                    INNER JOIN roles r ON r.role_id = ur.role_id
                    WHERE u.status = 'active' AND r.role_name = 'admin'
                ")->fetchColumn();
                if ($count <= 1) {
                    $this->db->rollBack();
                    return false;
                }
            }

            $update = $this->db->prepare("
                UPDATE users
                SET full_name = :full_name, email = :email, status = :status
                WHERE user_id = :id
            ");
            $update->execute([
                'full_name' => trim($data['full_name']),
                'email' => strtolower(trim($data['email'])),
                'status' => $data['status'],
                'id' => $id
            ]);

            if (($data['password'] ?? '') !== '') {
                if (strlen($data['password']) < 8) {
                    $this->db->rollBack();
                    return false;
                }
                $password = $this->db->prepare("
                    UPDATE users SET password_hash = :password_hash WHERE user_id = :id
                ");
                $password->execute([
                    'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                    'id' => $id
                ]);
            }

            $deleteRoles = $this->db->prepare("DELETE FROM user_roles WHERE user_id = :id");
            $deleteRoles->execute(['id' => $id]);
            $insertRole = $this->db->prepare("
                INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)
            ");
            foreach ($newRoleIds as $roleId) {
                $insertRole->execute(['user_id' => $id, 'role_id' => $roleId]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
