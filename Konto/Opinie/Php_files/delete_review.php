<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Musisz być zalogowany.']);
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
    echo json_encode(['success' => false, 'error' => 'Błąd połączenia z bazą: ' . $e->getMessage()]);
    exit;
}

// Проверка параметра id
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'Nieprawidłowy identyfikator recenzji.']);
    exit;
}

$review_id = (int)$_POST['id'];

// Проверка принадлежности отзыва пользователю
$stmt = $pdo->prepare("
    SELECT [id] FROM [dragon_world].[dbo].[opinie]
    WHERE [id] = :id AND [id_klienta] = :user_id
");
$stmt->bindParam(':id', $review_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$review = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$review) {
    echo json_encode(['success' => false, 'error' => 'Brak uprawnień do usunięcia tego komentarza.']);
    exit;
}

// Удаление отзыва
$stmt = $pdo->prepare("
    DELETE FROM [dragon_world].[dbo].[opinie]
    WHERE [id] = :id AND [id_klienta] = :user_id
");
$stmt->bindParam(':id', $review_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

try {
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Nie udało się usunąć komentarza.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?>
