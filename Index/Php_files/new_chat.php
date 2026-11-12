<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

$config = require __DIR__ . '/../../secure/config.php';

$conn = new PDO(
    "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
    $config['username'],
    $config['password']
);

$userId = $_SESSION['user']['id'] ?? null;

$stmt = $conn->prepare("
    INSERT INTO SesjeCzatu (user_id)
    VALUES (?)
");

$stmt->execute([$userId]);

$sessionId = (int) $conn->lastInsertId();
$_SESSION['chat_session_id'] = $sessionId;

echo json_encode(["ok" => true, "id" => $sessionId]);
