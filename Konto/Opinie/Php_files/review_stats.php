<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'dragon world';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения к базе: " . $e->getMessage());
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count, AVG(rating) AS average FROM reviews WHERE product_id = :id");
    $stmt->execute(['id' => $product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'count' => (int)$result['count'],
        'average' => $result['average'] !== null ? round((float)$result['average'], 1) : 0
    ]);
} else {
    echo json_encode([
        'count' => 0,
        'average' => 0
    ]);
}
?>
