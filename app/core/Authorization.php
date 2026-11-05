
<?php

require_once __DIR__ . '/../../config/database.php';

/**
 * Quản lý xác thực và phân quyền tập trung.
 *
 * Các vai trò hợp lệ:
 * admin, manager, teacher, technician
 */

class Authorization
{
    private static bool $accountChecked = false;
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user']['user_id']);
    }

    public static function user(): array
    {
        return $_SESSION['user'] ?? [];
    }

    public static function userId(): int
    {
        return (int) (self::user()['user_id'] ?? 0);
    }

    public static function roles(): array
    {
        $roles = self::user()['roles'] ?? [];

        if (!is_array($roles)) {
            return [];
        }

        return array_values(array_unique($roles));
    }

    public static function hasRole(string $role): bool
    {
        return in_array($role, self::roles(), true);
    }

    public static function hasAnyRole(array $roles): bool
    {
        return count(array_intersect($roles, self::roles())) > 0;
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: index.php?action=login');
            exit();
        }

        // Kiểm tra trạng thái tài khoản trên CSDL ở request đầu tiên cần xác thực.
        if (!self::$accountChecked) {
            self::$accountChecked = true;

            try {
                $stmt = Database::connect()->prepare(
                    "SELECT status FROM users WHERE user_id = :user_id LIMIT 1"
                );
                $stmt->execute(['user_id' => self::userId()]);
                $status = $stmt->fetchColumn();

                if ($status !== 'active') {
                    self::destroySession();
                    header('Location: index.php?action=login&account_locked=1');
                    exit();
                }
            } catch (Throwable $e) {
                error_log('Account status check error: ' . $e->getMessage());
                http_response_code(503);
                exit('Không thể xác thực trạng thái tài khoản. Vui lòng thử lại.');
            }
        }
    }

    private static function destroySession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function requireRole(array $allowedRoles): void
    {
        self::requireLogin();

        if (self::hasRole('admin')) {
            return;
        }

        if (!self::hasAnyRole($allowedRoles)) {
            http_response_code(403);
            exit('Bạn không có quyền truy cập chức năng này.');
        }
    }

    public static function roleLabel(): string
    {
        $labels = [
            'admin' => 'Quản trị hệ thống',
            'manager' => 'Quản lý phòng và thiết bị',
            'teacher' => 'Giảng viên',
            'technician' => 'Nhân viên kỹ thuật'
        ];

        foreach (self::roles() as $role) {
            if (isset($labels[$role])) {
                return $labels[$role];
            }
        }

        return 'Người dùng';
    }
}
