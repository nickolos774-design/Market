<?php
$config = require __DIR__ . '/../secure/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exists' => false]);
    exit;
}

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    echo json_encode(['exists' => false]);
    exit;
}

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $conn->prepare(
        "SELECT 1 FROM Użytkownicy WHERE email = :email"
    );
    $stmt->execute([':email' => $email]);

    echo json_encode([
        'exists' => (bool) $stmt->fetchColumn()
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'exists' => false,
        'error' => $e->getMessage() // ← можно временно
    ]);
}
