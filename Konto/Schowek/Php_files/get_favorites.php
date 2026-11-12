<?php
session_start();

if (!isset($_SESSION['user']['id'])) {
    die(json_encode(['error' => 'Пользователь не авторизован']));
}

$user_id = $_SESSION['user']['id'];

/* Подключение к SQL Server */
$config = require __DIR__ . '/../../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Ошибка подключения: ' . $e->getMessage()]));
}

/* Избранные товары пользователя */
$sql = "
    SELECT
        p.id,
        p.Nazwa       AS name,
        p.Cena        AS price,
        p.Opis        AS description,
        p.Foto        AS image,
        p.id_kategoria
    FROM [dragon_world].[dbo].[Produkty] p
    INNER JOIN [dragon_world].[dbo].[Ulubione_towary] u
        ON p.id = u.product_id
    WHERE u.Id_klienta = :user_id
";

$stmt = $conn->prepare($sql);
$stmt->execute(['user_id' => $user_id]);

$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

function productImageUrl($path) {
    $path = str_replace('\\', '/', trim((string) $path));

    if ($path === '' || preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    if (preg_match('#FOTO/(.+)$#i', $path, $matches)) {
        return '../../../FOTO/' . ltrim($matches[1], '/');
    }

    return '../../../' . ltrim($path, '/');
}

/* Путь к изображениям */
foreach ($favorites as &$item) {
    $item['image'] = productImageUrl($item['image']);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($favorites);
