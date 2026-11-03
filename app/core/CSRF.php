<?php

class CSRF
{
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' .
            htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') .
            '">';
    }

    public static function verify(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        $submittedToken = $_POST['_csrf_token'] ?? '';

        return is_string($sessionToken)
            && is_string($submittedToken)
            && $sessionToken !== ''
            && $submittedToken !== ''
            && hash_equals($sessionToken, $submittedToken);
    }

    public static function verifyOrFail(): void
    {
        if (!self::verify()) {
            http_response_code(403);
            exit('Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
        }
    }
}