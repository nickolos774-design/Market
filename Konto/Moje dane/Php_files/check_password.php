<?php
session_start();
error_reporting(0);
header('Content-Type: application/json');

// Подключение к базе
$config = require __DIR__ . '/../../secure/config.php';
$user_id = $_SESSION['user']['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id && isset($_POST['password'])) {
    $password = trim($_POST['password']);

    try {
        $pdo = new PDO(
            "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $stmt = $pdo->prepare('SELECT [Hasło] FROM [Użytkownicy] WHERE id = ?');
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Обрезаем возможные лишние пробелы из SQL Server
        $hash = rtrim($row['Hasło'] ?? '');

        $valid = password_verify($password, $hash);

        echo json_encode(['valid' => $valid]);

    } catch (PDOException $e) {
        echo json_encode(['valid' => false]);
    }
} else {
    echo json_encode(['valid' => false]);
}
