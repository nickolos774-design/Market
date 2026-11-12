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

$user_id = $_SESSION['user']['id'] ?? null;
$last_address_id = $_SESSION['last_address_id'] ?? null;
$delivery_type_id = $_SESSION['delivery_type_id'] ?? null;
$payment_method_id = $_SESSION['payment_method_id'] ?? null;
$total_price = $_SESSION['total_price'] ?? 0;

if (!$user_id) {
    die("Brak wymaganych danych do złożenia zamówienia.");
}

// Если часть данных не попала в сессию (пользователь пропустил шаг/не сработал AJAX),
// пытаемся восстановить её из БД.

// 1) Адрес доставки: берём последний/дефолтный адрес пользователя
if (!$last_address_id) {
    $stmt = $pdo->prepare("
        SELECT TOP 1 id
        FROM Adresy_dostawy
        WHERE Id_klienta = ?
        ORDER BY Domyślny DESC, Data_utworzenia DESC
    ");
    $stmt->execute([$user_id]);
    $last_address_id = $stmt->fetchColumn() ?: null;
    if ($last_address_id) {
        $_SESSION['last_address_id'] = $last_address_id;
    }
}

// 2) Тип доставки: по last_delivery_type -> Typ_dostawy.Id (и если записи нет — создаём)
if (!$delivery_type_id) {
    $last_delivery_type = $_SESSION['last_delivery_type'] ?? null;
    if ($last_delivery_type) {
        $stmt = $pdo->prepare("SELECT Id FROM Typ_dostawy WHERE Nazwa = ?");
        $stmt->execute([$last_delivery_type]);
        $delivery_type_id = $stmt->fetchColumn();

        if (!$delivery_type_id) {
            $ins = $pdo->prepare("INSERT INTO Typ_dostawy (Nazwa, Cena, Aktywna) VALUES (?, 0, 1)");
            $ins->execute([$last_delivery_type]);
            $stmt->execute([$last_delivery_type]);
            $delivery_type_id = $stmt->fetchColumn();
        }

        if ($delivery_type_id) {
            $_SESSION['delivery_type_id'] = $delivery_type_id;
        }
    }
}

// 3) Метод оплаты: по payment_method -> Metoda_platnosci.Id (и если записи нет — создаём)
if (!$payment_method_id) {
    $payment_method = $_SESSION['payment_method'] ?? null; // 'blik' / 'transfer'
    if ($payment_method) {
        $stmt = $pdo->prepare("SELECT Id FROM Metoda_platnosci WHERE Nazwa = ? AND Aktywna = 1");
        $stmt->execute([$payment_method]);
        $payment_method_id = $stmt->fetchColumn();

        if (!$payment_method_id) {
            $ins = $pdo->prepare("INSERT INTO Metoda_platnosci (Nazwa, Aktywna) VALUES (?, 1)");
            $ins->execute([$payment_method]);
            $stmt->execute([$payment_method]);
            $payment_method_id = $stmt->fetchColumn();
        }

        if ($payment_method_id) {
            $_SESSION['payment_method_id'] = $payment_method_id;
        }
    }
}

// 4) Сумма: пересчитываем из корзины, если в сессии пусто
if (!$total_price || $total_price <= 0) {
    $stmt = $pdo->prepare("
        SELECT SUM(CAST(k.Ilość AS DECIMAL(18,2)) * p.Cena) AS total
        FROM koszyk k
        JOIN Produkty p ON k.Id_produkta = p.id
        WHERE k.Id_klienta = ?
    ");
    $stmt->execute([$user_id]);
    $total_price = (float)($stmt->fetchColumn() ?? 0);
    $_SESSION['total_price'] = $total_price;
}

// Финальная проверка
if (!$last_address_id || !$delivery_type_id || !$payment_method_id || $total_price <= 0) {
    die("Brak wymaganych danych do złożenia zamówienia.");
}

require_once dirname(__DIR__, 3) . '/secure/grywalizacja.php';
dw_ensure($pdo);

try {
    $pdo->beginTransaction();

    
    $stmt = $pdo->prepare("
        INSERT INTO Zamowienia 
        (Id_klienta, Laczna_cena, Data_utworzenia, Status, Id_metoda_platnosci, Id_typ_dostawy, Id_adres_dostawy)
        OUTPUT INSERTED.Id
        VALUES (?, ?, GETDATE(), 'nowe', ?, ?, ?)
    ");
    $stmt->execute([$user_id, $total_price, $payment_method_id, $delivery_type_id, $last_address_id]);
    $zamowienie_id = $stmt->fetchColumn();

    // --- Перенос товаров из корзины ---
    $stmtProducts = $pdo->prepare("
        INSERT INTO zamowienia_produkty (Id_zamówienia, Id_produktu, Ilość, Cena)
        SELECT ?, k.Id_produkta, k.Ilość, p.Cena
        FROM koszyk k
        JOIN Produkty p ON k.Id_produkta = p.id
        WHERE k.Id_klienta = ?
    ");
    $stmtProducts->execute([$zamowienie_id, $user_id]);

    // --- Очистка корзины ---
    $stmtClearCart = $pdo->prepare("DELETE FROM koszyk WHERE Id_klienta = ?");
    $stmtClearCart->execute([$user_id]);

    // --- Создание фактуры в базе ---
    $invoice_stmt = $pdo->prepare("SELECT Numer FROM Faktury WHERE Id_zamówienia = ?");
    $invoice_stmt->execute([$zamowienie_id]);
    $invoice_number = $invoice_stmt->fetchColumn();

    if (!$invoice_number) {
        $last_id_stmt = $pdo->query("SELECT MAX(Id) AS last_id FROM Faktury");
        $last_id = $last_id_stmt->fetchColumn() ?? 0;
        $invoice_number = "F-" . ($last_id + 1);

        $insert_invoice = $pdo->prepare("
            INSERT INTO Faktury (Id_zamówienia, Numer, Data_utworzenia)
            VALUES (?, ?, GETDATE())
        ");
        $insert_invoice->execute([$zamowienie_id, $invoice_number]);
    }

    // чтобы dodaj_produkty.php мог использовать это значение
    $_SESSION['last_order_id'] = $zamowienie_id;
    dw_award_xp($pdo, (int) $user_id, 'zamowienie', (int) $zamowienie_id, 100);

    $pdo->commit();

header("Location: ../../Zamowienia/Php_files/Zamowienia.php");
exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Błąd przy składaniu zamówienia: " . $e->getMessage());
}
