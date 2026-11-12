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
} catch (PDOException $e) {
    echo json_encode(['products' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int) ($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['products' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$boost = [];
$sessionId = $_SESSION['chat_session_id'] ?? null;
if ($sessionId) {
    try {
        $stmt = $conn->prepare("
            SELECT TOP 20 Tekst
            FROM Wiadomości
            WHERE sesja_id = ?
            ORDER BY id DESC
        ");
        $stmt->execute([$sessionId]);
        $messages = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $boost = dw_chat_category_boost($messages);
    } catch (Throwable $e) {
        error_log($e->getMessage());
    }
}

$products = dw_rank_products($conn, $userId, [
    'mode' => 'recommend',
    'limit' => 5,
    'categoryBoost' => $boost,
    'exploreTail' => 1,
]);

echo json_encode([
    'products' => $products,
    'categories' => array_keys($boost),
    'updated' => time(),
], JSON_UNESCAPED_UNICODE);
