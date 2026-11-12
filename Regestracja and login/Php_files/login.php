<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '../../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'] ,
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Brakuje danych logowania.']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM Użytkownicy WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['Hasło'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'first_name' => $user['Imię'],
                    'last_name' => $user['Nazwisko'],
                    'role' => $user['Rola'] ?? 'client',
                    'foto' => $user['Foto'] ?? null
                ];

                if (($user['Rola'] ?? '') === 'employee') {
                    echo json_encode([
                        'success' => true,
                        'role' => 'employee',
                        'redirect' => '../Pracownik/edcjaprodukta.php'
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'role' => $user['Rola'] ?? 'client'
                    ]);
                }

            } else {
                echo json_encode(['success' => false, 'message' => 'Niepoprawnie hasło']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Użytkownik nie istnieje.']);
        }
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
