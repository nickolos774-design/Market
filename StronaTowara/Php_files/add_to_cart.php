<?php
session_start();
header('Content-Type: application/json');

// Подключение конфигурации
$config = require __DIR__ . '../../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка подключения к базе данных: ' . $e->getMessage()
    ]);
    exit;
}

// Проверка авторизации
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Пользователь не авторизован.']);
    exit;
}
$userId = $_SESSION['user']['id'];

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

// Получаем ID товара
$productId = isset($_POST['productId']) ? (int)$_POST['productId'] : 0;
if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Не передан ID товара']);
    exit;
}

try {
    // Проверяем, есть ли товар в корзине
    $stmt = $conn->prepare("
        SELECT k.[Ilość], p.Cena 
        FROM koszyk k 
        JOIN Produkty p ON k.Id_produkta = p.id 
        WHERE k.Id_klienta = :Id_klienta AND k.Id_produkta = :Id_produkta
    ");
    $stmt->execute([
        ':Id_klienta' => $userId,
        ':Id_produkta' => $productId
    ]);

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        // Товар уже есть → увеличиваем на 1
        $stmt = $conn->prepare("
            UPDATE koszyk 
            SET [Ilość] = [Ilość] + 1 
            WHERE Id_klienta = :Id_klienta AND Id_produkta = :Id_produkta
        ");
        $stmt->execute([
            ':Id_klienta' => $userId,
            ':Id_produkta' => $productId
        ]);

        $newQuantity = $item['Ilość'] + 1;
        $price = $item['Cena'];
    } else {
        // Товара нет → вставляем новый с количеством = 1
        $stmt = $conn->prepare("
            INSERT INTO koszyk (Id_klienta, Id_produkta, [Ilość], Data_utworzenia) 
            VALUES (:Id_klienta, :Id_produkta, 1, GETDATE())
        ");
        $stmt->execute([
            ':Id_klienta' => $userId,
            ':Id_produkta' => $productId
        ]);

        $newQuantity = 1;

        // Получаем цену товара
        $stmt = $conn->prepare("SELECT Cena FROM Produkty WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        $price = $stmt->fetchColumn();
    }

    // Возвращаем JSON с обновлёнными данными
    echo json_encode([
        'success' => true,
        'quantity' => $newQuantity,
        'total_price' => $price * $newQuantity
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при добавлении товара',
        'error' => $e->getMessage()
    ]);
}
