<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'dragon world';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['total' => 0]));
}

function getCartCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT SUM(quantity) AS total_quantity FROM Koszyk WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = 0;
    if ($row = $result->fetch_assoc()) {
        $total = (int)$row['total_quantity'];
    }
    $stmt->close();
    return $total;
}

$cart_count = 0;
if (isset($_SESSION['user']['id'])) {
    $user_id = $_SESSION['user']['id'];
    $cart_count = getCartCount($conn, $user_id);
}

$conn->close();

// Отправляем JSON
header('Content-Type: application/json');
echo json_encode(['total' => $cart_count]);
