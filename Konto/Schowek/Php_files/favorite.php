<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode([
        'redirect' => '/Dragon%20World1111111111(clean%20for%20next%20work)/Regestracja%20and%20login/Html_files/Login.html'
    ]);
    exit;
}

$user_id = $_SESSION['user']['id'];

$config = require __DIR__ . '/../../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB error']);
    exit;
}

$product_id = $_POST['product_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$product_id || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Проверяем, есть ли уже в избранном
$check = $conn->prepare("
    SELECT 1 FROM [dragon_world].[dbo].[Ulubione_towary]
    WHERE Id_klienta = :user_id AND product_id = :product_id
");
$check->execute([
    'user_id' => $user_id,
    'product_id' => $product_id
]);

if (!$check->fetch()) {
    $insert = $conn->prepare("
        INSERT INTO [dragon_world].[dbo].[Ulubione_towary] (Id_klienta, product_id)
        VALUES (:user_id, :product_id)
    ");
    $insert->execute([
        'user_id' => $user_id,
        'product_id' => $product_id
    ]);
}



if ($action === 'remove') {
    $stmt = $conn->prepare("
        DELETE FROM [dragon_world].[dbo].[Ulubione_towary]
        WHERE Id_klienta = :user_id AND product_id = :product_id
    ");
    $stmt->execute(['user_id' => $user_id, 'product_id' => $product_id]);
}

echo json_encode(['success' => true]);
exit;
