<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Подключение к базе
$config = require __DIR__ . '/../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $data = json_decode(file_get_contents("php://input"), true);

    $firstName = trim($data['firstName'] ?? '');
    $lastName  = trim($data['lastName'] ?? '');
    $email     = trim($data['email'] ?? '');
    $passwordRaw = trim($data['password'] ?? '');

    if (!$firstName || !$lastName || !$email || !$passwordRaw) {
        echo json_encode(['success' => false, 'message' => 'Brak danych do rejestracji.']);
        exit;
    }

    // Проверка email
    $stmt = $conn->prepare("SELECT id FROM Użytkownicy WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'success' => false,
            'message' => 'Email już istnieje'
        ]);
        exit;
    }

    $passwordHashed = password_hash($passwordRaw, PASSWORD_DEFAULT);
    $role = 'client';
    $foto = null;

    $conn->beginTransaction();

    // Вставка нового пользователя
    $stmt = $conn->prepare("
        INSERT INTO Użytkownicy (Imię, Nazwisko, email, Hasło, Rola, Foto, Data_utworzenia)
        VALUES (:firstName, :lastName, :email, :passwordHashed, :role, :foto, GETDATE())
    ");

    $stmt->execute([
        ':firstName' => $firstName,
        ':lastName' => $lastName,
        ':email' => $email,
        ':passwordHashed' => $passwordHashed,
        ':role' => $role,
        ':foto' => $foto
    ]);

    // Получаем последний вставленный ID
    $userId = $conn->lastInsertId();

    $conn->commit();

    // Сохраняем сессию
    $_SESSION['user'] = [
        'id' => $userId,
        'email' => $email,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'role' => $role,
        'foto' => $foto
    ];

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}