<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::connect();
    echo "KET NOI MYSQL THANH CONG!";
    echo PHP_EOL;
    echo "Database: " . $db->query("SELECT DATABASE()")->fetchColumn();
} catch (Throwable $e) {
    echo "KET NOI THAT BAI: " . $e->getMessage();
}
