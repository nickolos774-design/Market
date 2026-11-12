<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Musisz być zalogowany, aby edytować opinie.']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$config = require __DIR__ . '../../../../secure/config.php';

try {
    $pdo = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => "Błąd połączenia z bazą: " . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id'], $_POST['text'])) {
        echo json_encode(['success' => false, 'error' => 'Brak wymaganych danych.']);
        exit;
    }

    $review_id = intval($_POST['id']);
    $review_text = substr(trim($_POST['text']), 0, 500);

    $stmt = $pdo->prepare("
        UPDATE [dragon_world].[dbo].[opinie]
        SET [Tekst] = :text
        WHERE [id] = :id AND [id_klienta] = :user_id
    ");

    $stmt->bindParam(':text', $review_text, PDO::PARAM_STR);
    $stmt->bindParam(':id', $review_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    try {
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Nie udało się zaktualizować opinii.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Błąd bazy danych: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Niepoprawna metoda żądania.']);
}
?>
