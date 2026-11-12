<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['logged' => false]);
    exit;
}

$user_id = $_SESSION['user']['id'];
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

$config = require __DIR__ . '../../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM [dragon_world].[dbo].[Ulubione_towary]
        WHERE Id_klienta = :user_id AND product_id = :product_id
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'product_id' => $product_id
    ]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

    echo json_encode([
        'logged' => true,
        'isFav' => $count > 0
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
