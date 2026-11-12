<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $config = require __DIR__ . '/../../secure/config.php';
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $data = json_decode(file_get_contents("php://input"), true);
    $email = trim($data['email'] ?? '');

    if (empty($email)) {
        echo json_encode(["success" => false, "message" => "Brak adresu email."]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM Użytkownicy WHERE email = :email");
    $stmt->execute([":email" => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($user['id'])) {
        echo json_encode(["success" => false, "message" => "Ten email jest już zarejestrowany."]);
    } else {
        echo json_encode(["success" => true, "message" => "Email dostępny."]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Błąd serwera"]);
}
?>
