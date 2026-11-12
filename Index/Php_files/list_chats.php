<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

$config = require __DIR__ . '/../../secure/config.php';

$userId = (int) ($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(["chats" => [], "active_id" => null], JSON_UNESCAPED_UNICODE);
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
        SELECT
            s.id,
            CONVERT(varchar(16), s.Data_utworzenia, 120) AS created_at,
            LEFT(CAST(firstMsg.Tekst AS NVARCHAR(MAX)), 80) AS preview
        FROM SesjeCzatu s
        OUTER APPLY (
            SELECT TOP 1 Tekst
            FROM Wiadomości w
            WHERE w.sesja_id = s.id
              AND w.Nadawca = N'użytkownik'
            ORDER BY w.id ASC
        ) firstMsg
        WHERE s.user_id = ?
        ORDER BY s.id DESC
    ");
    $stmt->execute([$userId]);
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "chats" => $chats,
        "active_id" => isset($_SESSION['chat_session_id']) ? (int) $_SESSION['chat_session_id'] : null
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(["chats" => [], "active_id" => null], JSON_UNESCAPED_UNICODE);
}
