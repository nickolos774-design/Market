<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

$config = require __DIR__ . '/../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([]);
    exit;
}

$sessionId = $_SESSION['chat_session_id'] ?? null;

if (!$sessionId) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT Nadawca, Tekst, Produkty
    FROM Wiadomości
    WHERE sesja_id = ?
    ORDER BY id ASC
");

$stmt->execute([$sessionId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
