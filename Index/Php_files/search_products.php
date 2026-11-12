<?php
header("Content-Type: application/json");

$config = require __DIR__ . '/../../secure/config.php';

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : "";

if ($searchQuery === "") {
    echo json_encode([]);
    exit;
}

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT TOP 100 id, Nazwa, Cena, Opis, Foto FROM Produkty WHERE Nazwa LIKE :query");
    $stmt->execute(['query' => "%$searchQuery%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(function($product) {
        return [
            'id' => $product['id'],
            'name' => $product['Nazwa'],
            'description' => $product['Opis'],
            'price' => $product['Cena'],
            'image' => $product['Foto']
        ];
    }, $products);

    echo json_encode($result);

} catch (PDOException $e) {
    echo json_encode(["error" => "Ошибка базы данных: " . $e->getMessage()]);
}
