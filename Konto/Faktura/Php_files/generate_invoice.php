<?php
session_start();
require __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;

// --- Подключение к базе через конфиг ---
$config = require __DIR__ . '/../../secure/config.php';

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

// --- Проверка доступа ---
if (!isset($_GET['order_id']) || !isset($_SESSION['user']['id'])) {
    die("Brak dostępu.");
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user']['id'];

// --- Получаем заказ и товары ---
$sql = "
    SELECT z.Id AS zamowienie_id, z.Data_utworzenia, z.Status,
           p.Nazwa, p.Cena, zp.Ilość
    FROM Zamowienia z
    JOIN zamowienia_produkty zp ON z.Id = zp.Id_zamówienia
    JOIN Produkty p ON p.id = zp.Id_produktu
    WHERE z.Id = ? AND z.Id_klienta = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id, $user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    die("Nie znaleziono zamówienia.");
}

$items = [];
$total = 0;
$status = $rows[0]['Status'];
$created_at = $rows[0]['Data_utworzenia'];

foreach ($rows as $row) {
    $items[] = $row;
    $total += $row['Cena'] * $row['Ilość'];
}

// --- Получаем или создаём номер фактуры ---
$invoice_stmt = $pdo->prepare("SELECT Numer FROM Faktury WHERE Id_zamówienia = ?");
$invoice_stmt->execute([$order_id]);
$invoice_number = $invoice_stmt->fetchColumn();

if (!$invoice_number) {
    // Генерируем новый номер
    $last_id_stmt = $pdo->query("SELECT MAX(Id) AS last_id FROM Faktury");
    $last_id = $last_id_stmt->fetchColumn() ?? 0;
    $invoice_number = "F-" . ($last_id + 1);

    $insert_stmt = $pdo->prepare("
        INSERT INTO Faktury (Id_zamówienia, Numer, Data_urworzenia)
        VALUES (?, ?, GETDATE())
    ");
    $insert_stmt->execute([$order_id, $invoice_number]);
}

// --- HTML фактуры ---
$html = "
<h2>Faktura</h2>
<p><strong>Numer:</strong> $invoice_number</p>
<p><strong>Data:</strong> $created_at</p>
<p><strong>Status zamówienia:</strong> $status</p>

<hr>
<h3>Sprzedawca:</h3>
<p>Twoje imię i nazwisko / Nick<br>
Adres (opcjonalnie)</p>

<h3>Klient:</h3>
<p>{$_SESSION['user']['first_name']} {$_SESSION['user']['last_name']}<br>
{$_SESSION['user']['email']}</p>

<hr>
<h3>Produkty:</h3>
<table border='1' cellspacing='0' cellpadding='5' width='100%'>
<tr>
<th>Nazwa</th>
<th>Ilość</th>
<th>Cena</th>
<th>Suma</th>
</tr>";

foreach ($items as $item) {
    $line = $item['Cena'] * $item['Ilość'];
    $html .= "
    <tr>
        <td>{$item['Nazwa']}</td>
        <td>{$item['Ilość']}</td>
        <td>{$item['Cena']} PLN</td>
        <td>{$line} PLN</td>
    </tr>";
}

$html .= "</table>
<hr>
<h3>Razem do zapłaty: <strong>$total PLN</strong></h3>
";

// --- Генерация PDF ---
$dompdf = new Dompdf();
$dompdf->loadHtml(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// --- Скачивание PDF ---
$dompdf->stream("Faktura-$invoice_number.pdf", ["Attachment" => true]);
