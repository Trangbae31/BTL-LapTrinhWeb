
<?php

class Database
{
    public static function connect(): PDO
    {
        $envPath = dirname(__DIR__) . '/.env';

        if (!file_exists($envPath)) {
            throw new RuntimeException(
                'Chưa có file .env. Hãy sao chép .env.example thành .env và cấu hình MySQL.'
            );
        }

        $config = parse_ini_file($envPath);

        if ($config === false) {
            throw new RuntimeException('Không đọc được file .env.');
        }

        $host = $config['DB_HOST'] ?? '127.0.0.1';
        $port = $config['DB_PORT'] ?? '3306';
        $dbname = $config['DB_NAME'] ?? '';
        $username = $config['DB_USER'] ?? '';
        $password = $config['DB_PASS'] ?? '';

        if ($dbname === '' || $username === '') {
            throw new RuntimeException(
                'Vui lòng cấu hình DB_NAME và DB_USER trong file .env.'
            );
        }

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
