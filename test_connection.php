<?php
header('Content-Type: application/json');

$serverName = "DESKTOP-QOQP51D\\SQLEXPRESS";
$database = "dragon_world";
$username = "admin";
$password = "1111";

try {
    // Подключение к SQL Server без проверки сертификата
    $pdo = new PDO(
        "sqlsrv:Server=$serverName;Database=$database;Encrypt=no",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo json_encode(["status" => "✅ Connection successful!111"]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
