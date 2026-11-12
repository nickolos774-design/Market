<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Musisz być zalogowany, aby zobaczyć swoje opinie.']);
    exit;
}

$user_id = $_SESSION['user']['id'];

// Подключение к SQL Server
$config = require __DIR__ . '../../../../secure/config.php';

try {
    $pdo = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => "Błąd połączenia z bazą: " . $e->getMessage()]);
    exit;
}

// Параметры пагинации
$reviewsPerPage = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $reviewsPerPage;

// Общее количество отзывов
$stmtTotal = $pdo->prepare("SELECT COUNT(*) AS total FROM opinie WHERE id_klienta = :user_id");
$stmtTotal->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmtTotal->execute();
$totalReviews = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalReviews / $reviewsPerPage);

// Получение отзывов
$sql = "
    SELECT o.id, o.Tekst, o.Ilosc_gwiazdek, o.Status,
           p.Nazwa AS product_name, p.Foto AS product_photo,
           u.Imię AS first_name, u.Nazwisko AS last_name, u.Foto AS user_photo
    FROM opinie o
    JOIN Użytkownicy u ON o.id_klienta = u.id
    JOIN Produkty p ON o.id_produkta = p.id
    WHERE o.id_klienta = :user_id
    ORDER BY o.Data_utworzenia DESC
    OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $reviewsPerPage, PDO::PARAM_INT);
$stmt->execute();

$avatarFolder = '../Moje dane/';
$productPhotoFolder = '../../FOTO/';

$reviews = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $reviews[] = [
        'id' => $row['id'],
        'user_name' => htmlspecialchars($row['first_name'] . ' ' . $row['last_name']),
        'user_photo' => !empty($row['user_photo']) ? $avatarFolder . $row['user_photo'] : $avatarFolder . 'default_avatar.jpg',
        'product' => htmlspecialchars($row['product_name']),
        'product_photo' => !empty($row['product_photo']) ? $productPhotoFolder . $row['product_photo'] : $productPhotoFolder . 'default_product.jpg',
        'text' => nl2br(htmlspecialchars($row['Tekst'])),
        'stars' => (int)$row['Ilosc_gwiazdek'],
        'status' => $row['Status']
    ];
}

echo json_encode([
    'reviews' => $reviews,
    'totalPages' => $totalPages,
    'page' => $page
], JSON_UNESCAPED_UNICODE);
