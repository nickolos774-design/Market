<?php
header('Content-Type: application/json');
$config = require __DIR__ . '/../../secure/config.php';

if (!isset($_GET['id'])) {
    echo json_encode(["error" => "ID товара не передан"]);
    exit;
}

$productId = (int)$_GET['id'];

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT TOP 1 * FROM Produkty WHERE id = :id");
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(["error" => "Товар не найден"]);
        exit;
    }

    echo json_encode([
        'id' => $product['id'],
        'name' => $product['Nazwa'],
        'description' => $product['Opis'],
        'price' => $product['Cena'],
        'image' => $product['Foto'],
        'category' => $product['id_kategoria']
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
