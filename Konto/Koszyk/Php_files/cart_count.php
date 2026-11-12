<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключение к базе через PDO (SQL Server)
$config = require __DIR__ . '/../../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $total_items_in_cart = 0;

    if (isset($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];

        // Подсчёт общего количества товаров в корзине
        $stmt = $conn->prepare("
            SELECT SUM([Ilość]) AS total_quantity
            FROM [koszyk]
            WHERE [Id_klienta] = :user_id
        ");
        $stmt->execute(['user_id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $total_items_in_cart = $row['total_quantity'] !== null ? (int)$row['total_quantity'] : 0;
    }

} catch (PDOException $e) {
    die("Ошибка подключения к SQL Server: " . $e->getMessage());
}
?>
