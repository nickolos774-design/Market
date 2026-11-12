<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Brak logowania'
    ]);
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$source = $_POST['source'] ?? $_GET['source'] ?? 'unknown';

if ($product_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Nieprawidłowy produkt'
    ]);
    exit;
}

$config = require __DIR__ . '../../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Лог событий: каждый просмотр — новая запись (без UPDATE)
    $insert = $conn->prepare("
        INSERT INTO viewed_products (user_id, product_id, viewed_at, source)
        VALUES (:user_id, :product_id, GETDATE(), :source)
    ");

    $insert->execute([
        'user_id' => $user_id,
        'product_id' => $product_id,
        'source' => $source
    ]);

    echo json_encode([
        'success' => true
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}