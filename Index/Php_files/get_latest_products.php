<?php
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Запрос — последние 5 товаров
    $sql = "SELECT TOP 5 id, Nazwa AS name, Foto AS image, Cena AS price, Opis AS description
            FROM [dbo].[Produkty]
            ORDER BY id DESC";

    $stmt = $conn->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$products) {
        echo json_encode(["message" => "Нет данных"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
