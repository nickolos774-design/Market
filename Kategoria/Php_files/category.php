<?php
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../secure/config.php';

$categoryId = $_GET['id'] ?? null;

// Проверка ID категории
if (!$categoryId || !is_numeric($categoryId)) {
    http_response_code(400);
    echo json_encode(["error" => "Некорректный ID категории"]);
    exit;
}

try {
    // Подключение к базе через PDO
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Подготовленный запрос для выборки товаров по категории
    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.Nazwa AS name,
            p.Cena AS price,
            p.Opis AS description,
            p.Foto AS image,
            p.id_kategoria,
            ISNULL((
                SELECT AVG(CAST(o.Ilosc_gwiazdek AS FLOAT))
                FROM opinie o
                WHERE o.id_produkta = p.id AND o.Status = 'zatwierdzony'
            ), 0) AS rating,
            ISNULL((
                SELECT SUM(zp.[Ilość])
                FROM Zamowienia_produkty zp
                WHERE zp.id_produktu = p.id
            ), 0) AS popularity
        FROM Produkty p
        WHERE p.id_kategoria = :categoryId
    ");
    $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($products)) {
        http_response_code(404);
        echo json_encode(["error" => "Продукты не найдены"]);
        exit;
    }

    echo json_encode($products, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Server error"]);
}
?>
