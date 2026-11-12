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
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    // Сводка
    $stmtSummary = $pdo->prepare("SELECT AVG(ilosc_gwiazdek) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE id_products = ?");
    $stmtSummary->execute([$product_id]);
    $summary = $stmtSummary->fetch(PDO::FETCH_ASSOC);

    $average = $summary['avg_rating'] ? round($summary['avg_rating'], 2) : 0;
    $count = (int)$summary['cnt'];

    // Отзывы
    $stmtReviews = $pdo->prepare("
        SELECT r.text, r.ilosc_gwiazdek, k.first_name, k.last_name, k.photo
        FROM reviews r
        JOIN users k ON r.id_klienta = k.id
        WHERE r.id_products = ?
    ");
    $stmtReviews->execute([$product_id]);
    $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'average' => $average,
        'count' => $count,
        'reviews' => $reviews
    ]);
} else {
    echo json_encode(['average' => 0, 'count' => 0, 'reviews' => []]);
}
?>
