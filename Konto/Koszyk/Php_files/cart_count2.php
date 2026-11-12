<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require __DIR__ . '/../../../secure/config.php';

$total_items_in_cart = 0;

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];

        $stmt = $conn->prepare("SELECT SUM(Ilość) AS total_quantity FROM koszyk WHERE Id_klienta = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $total_items_in_cart = (int)($row['total_quantity'] ?? 0);
    }

} catch (PDOException $e) {
    if (ob_get_length() !== false) {
        ob_clean();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

// 🔑 Чистый вывод JSON
if (ob_get_length() !== false) {
    ob_clean();
}
header('Content-Type: application/json');
echo json_encode(['success' => true, 'count' => $total_items_in_cart]);
exit;
