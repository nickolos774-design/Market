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
$firstName = $_POST['firstName'];
$lastName = $_POST['lastName'];
$phone = $_POST['phone'];

// Проверка, существует ли уже такой номер в базе данных
$sql_check = "SELECT COUNT(*) FROM klientforcall WHERE Numer = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $phone); // Привязка параметра
$stmt_check->execute();
$stmt_check->bind_result($count);
$stmt_check->fetch();
$stmt_check->close();

if ($count > 0) {
    // Если номер уже существует, выводим ошибку
    echo json_encode(["error" => "Numer telefonu już istnieje w bazie danych."]);
} else {
    // Если номер уникален, продолжаем вставку
    $sql = "INSERT INTO klientforcall (Imię, Nazwisko, Numer) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $firstName, $lastName, $phone);

    if ($stmt->execute()) {
        echo json_encode(["success" => "Nowy rekord został pomyślnie dodany!"]);
    } else {
        echo json_encode(["error" => "Błąd: " . $stmt->error]);
    }
    $stmt->close();
}

// Закрытие соединения
$conn->close();
?>
