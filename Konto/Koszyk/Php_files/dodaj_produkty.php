<?php
session_start();

$config = require __DIR__ . '/../../../secure/config.php';

try {
    $pdo = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Błąd połączenia z bazą: " . $e->getMessage());
}

// Получаем данные пользователя
$user_id = $_SESSION['user']['id'] ?? 0;
if (!$user_id) die("Brak użytkownika w sesji.");

// Получаем ID последнего заказа
$zamowienie_id = $_SESSION['last_order_id'] ?? null;
if (!$zamowienie_id) die("Nie udało się pobrać ID zamówienia");

// Получаем товары из корзины
$stmt = $pdo->prepare("
    SELECT k.Id_produkta, k.Ilość, p.Cena
    FROM koszyk k
    JOIN Produkty p ON k.Id_produkta = p.id
    WHERE k.Id_klienta = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) die("Koszyk jest pusty");

// Вставка товаров в zamowienia_produkty
$stmt_insert = $pdo->prepare("
    INSERT INTO zamowienia_produkty (Id_zamówienia, Id_produktu, Ilość, Cena)
    VALUES (?, ?, ?, ?)
");

try {
    $pdo->beginTransaction();

    foreach ($cart_items as $item) {
        $stmt_insert->execute([
            $zamowienie_id,
            $item['Id_produkta'],
            $item['Ilość'],
            $item['Cena']
        ]);
    }

    // Очистка корзины после добавления товаров
    $stmt_delete = $pdo->prepare("DELETE FROM koszyk WHERE Id_klienta = ?");
    $stmt_delete->execute([$user_id]);

    $pdo->commit();

    echo "Produkty zostały dodane do zamówienia i koszyk został opróżniony!";
} catch (Exception $e) {
    $pdo->rollBack();
    die("Błąd przy dodawaniu produktów do zamówienia: " . $e->getMessage());
}
?>
