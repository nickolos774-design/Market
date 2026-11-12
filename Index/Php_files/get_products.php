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
        $products = dw_load_products($conn);
        usort($products, function ($a, $b) {
            return $b['id'] <=> $a['id'];
        });
        echo json_encode(array_map('dw_public_product', $products), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $products = dw_rank_products($conn, $userId, [
        'mode' => 'catalog',
    ]);

    echo json_encode($products, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'Server error']);
}
