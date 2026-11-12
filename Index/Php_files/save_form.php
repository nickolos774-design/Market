<?php
// Подключение к базе данных
$servername = "localhost"; // ваш сервер
$username = "root";        // ваш логин
$password = "";            // ваш пароль
$dbname = "dragon_world";  // имя базы данных

// Создание соединения
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка соединения
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Получение данных из POST-запроса
$phone = $_POST['phone'];

// Проверка, существует ли уже такой номер в базе данных
$sql_check = "SELECT COUNT(*) FROM klientforcall WHERE Numer = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $phone); // Привязка параметра
$stmt_check->execute();
$stmt_check->bind_result($count);
$stmt_check->fetch();
$stmt_check->close();


// Закрытие соединения
$conn->close();
?>
