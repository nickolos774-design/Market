<?php
session_start();
$host = 'localhost';
$dbname = 'dragon world';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) die("Ошибка подключения: " . $conn->connect_error);

$user_id = $_SESSION['user']['id'];
$product_id = intval($_POST['product_id']);
$new_quantity = intval($_POST['quantity']);

// Обновляем количество
$stmt = $conn->prepare("UPDATE Koszyk SET quantity = ? WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
    exit;
}
$stmt->close();

// Считаем новую сумму
$sql = "SELECT SUM(p.price * k.quantity) as total FROM Koszyk k JOIN Products p ON k.product_id = p.id WHERE k.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_price = number_format($row['total'], 2, ',', ' ');

echo json_encode(['success' => true, 'total_price' => $total_price]);
?>
