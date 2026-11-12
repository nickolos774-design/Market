<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

$config = require __DIR__ . '/../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo json_encode([
        "error" => "DB ERROR",
        "today_sessions" => 0,
        "messages" => 0,
        "ai_recommendations" => 0,
        "clicks" => 0,
        "top_questions" => []
    ]);
    exit;
}

/* =========================
   1. Rozmowy dziś
========================= */
$todaySessions = $conn->query("
    SELECT COUNT(*)
    FROM SesjeCzatu
    WHERE CAST(Data_utworzenia AS DATE) = CAST(GETDATE() AS DATE)
")->fetchColumn();

/* =========================
   2. Wiadomości
========================= */
$messages = $conn->query("
    SELECT COUNT(*)
    FROM Wiadomości
")->fetchColumn();

/* =========================
   3. AI rekomendacje
========================= */
$aiRecommendations = $conn->query("
    SELECT COUNT(*)
    FROM Wiadomości
    WHERE Nadawca = 'AI'
    AND Produkty IS NOT NULL
")->fetchColumn();

/* =========================
   4. Kliknięcia (FIXED)
   берем только source = chat
========================= */
$clicks = $conn->query("
    SELECT COUNT(*)
    FROM viewed_products
    WHERE source = 'chat'
")->fetchColumn();

/* =========================
   5. Top pytania (FIX ERROR FIX)
   TEXT → NVARCHAR(MAX) fix
========================= */
$topQuestions = $conn->query("
    SELECT TOP 10
        CAST(Tekst AS NVARCHAR(MAX)) AS Tekst,
        COUNT(*) AS cnt
    FROM Wiadomości
    WHERE Nadawca = 'użytkownik'
    GROUP BY CAST(Tekst AS NVARCHAR(MAX))
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   RESPONSE
========================= */
echo json_encode([
    "today_sessions" => (int)$todaySessions,
    "messages" => (int)$messages,
    "ai_recommendations" => (int)$aiRecommendations,
    "clicks" => (int)$clicks,
    "top_questions" => $topQuestions
], JSON_UNESCAPED_UNICODE);