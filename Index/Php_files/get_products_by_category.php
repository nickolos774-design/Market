<?php
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../secure/config.php';

try {
    // Подключение через PDO (SQL Server)
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Получаем ID товара из GET
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo json_encode(["error" => "No product ID provided"]);
        exit;
    }
    $productId = (int)$_GET['id'];

    // Получаем категорию этого товара
    $stmt = $conn->prepare("SELECT id_kategoria FROM Produkty WHERE id = :id");
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        echo json_encode(["error" => "Товар не найден"]);
        exit;
    }

    $categoryId = $category['id_kategoria'];

    // Получаем максимум 4 товара из той же категории
    $stmt = $conn->prepare("
        SELECT TOP 4 id, Nazwa AS name, Cena AS price, Opis AS description, Foto AS image, id_kategoria
        FROM Produkty
        WHERE id_kategoria = :categoryId AND id != :productId
        ORDER BY id DESC
    ");
    $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
    $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Server error"], JSON_UNESCAPED_UNICODE);
}
?>
