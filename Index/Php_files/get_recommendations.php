<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/personalization.php';

$config = require __DIR__ . '/../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userId = (int) ($_SESSION['user']['id'] ?? 0);
    if ($userId <= 0) {
        echo json_encode([]);
        exit;
    }

    echo json_encode(dw_rank_products($conn, $userId, [
        'mode' => 'recommend',
        'limit' => 8,
        'exploreTail' => 2,
    ]), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Server error']);
}
