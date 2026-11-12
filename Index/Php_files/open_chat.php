<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

$config = require __DIR__ . '/../../secure/config.php';

$userId = (int) ($_SESSION['user']['id'] ?? 0);
$data = json_decode(file_get_contents("php://input"), true);
$sessionId = (int) ($data['id'] ?? 0);

if ($userId <= 0 || $sessionId <= 0) {
    echo json_encode(["ok" => false]);
    exit;
}

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("
        SELECT id
        FROM SesjeCzatu
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$sessionId, $userId]);

    if (!$stmt->fetchColumn()) {
        echo json_encode(["ok" => false]);
        exit;
    }

    $_SESSION['chat_session_id'] = $sessionId;
    echo json_encode(["ok" => true, "id" => $sessionId]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(["ok" => false]);
}
