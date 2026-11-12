<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user']['id'])) {
    die("Пользователь не авторизован.");
}

$user_id = $_SESSION['user']['id'];

$config = require __DIR__ . '../../../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к SQL Server: " . $e->getMessage());
}

// Проверка POST
if (!isset($_POST['id_products'], $_POST['text'], $_POST['ilosc_gwiazdek'])) {
    die("Не все данные переданы");
}

$id_products = (int)$_POST['id_products'];
$text = trim($_POST['text']);
$ilosc_gwiazdek = (int)$_POST['ilosc_gwiazdek'];

// Проверка длины текста
if (strlen($text) < 10) {
    die("Отзыв должен быть не менее 10 символов.");
}

if (strlen($text) > 500) {
    die("Отзыв не может превышать 500 символов.");
}

try {
    // Вставка отзыва с "zatwierdzony" или "oczekujący" статусом по умолчанию
    $stmt = $conn->prepare("
        INSERT INTO [dragon_world].[dbo].[opinie]
            ([id_klienta], [id_produkta], [Tekst], [Ilosc_gwiazdek], [Data_utworzenia], [Status])
        VALUES
            (:id_klienta, :id_produkta, :tekst, :ilosc_gwiazdek, GETDATE(), 'oczekujacy')
    ");

    $stmt->execute([
        ':id_klienta' => $user_id,
        ':id_produkta' => $id_products,
        ':tekst' => $text,
        ':ilosc_gwiazdek' => $ilosc_gwiazdek
    ]);

    echo "success";
} catch (PDOException $e) {
    echo "Ошибка при добавлении в БД: " . $e->getMessage();
}
?>
