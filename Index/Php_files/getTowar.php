<?php
header('Content-Type: application/json');
$config = require __DIR__ . '/../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['error' => 'Nieprawidłowe ID produktu']);
        exit;
    }

    $stmt = $conn->prepare("SELECT TOP 1 * FROM Produkty WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['error' => 'Produkt nie znaleziony']);
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
    echo json_encode(['error' => $e->getMessage()]);
}
?>
