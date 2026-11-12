<?php
session_start();

if (
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'admin'
) {
    header("Location: ../Index/Index.php");
    exit;
}

$config = require __DIR__ . '/../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        die("Nieprawidłowy identyfikator opinii.");
    }

    $review_id = (int)$_GET['id'];

    $stmt = $conn->prepare("
        SELECT TOP 1 *
        FROM opinie
        WHERE id = :id
    ");
    $stmt->execute(['id' => $review_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        die("Nie znaleziono opinii.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $text = trim($_POST['text']);
        $stars = (int)$_POST['stars'];
        $status = $_POST['status'];

        if ($stars < 1) $stars = 1;
        if ($stars > 5) $stars = 5;
        if (strlen($text) > 500) $text = substr($text, 0, 500);

        $stmtUpdate = $conn->prepare("
            UPDATE opinie
            SET Tekst = :text, Ilosc_gwiazdek = :stars, Status = :status
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            'text' => $text,
            'stars' => $stars,
            'status' => $status,
            'id' => $review_id
        ]);

        header("Location: opinie.php");
        exit();
    }

} catch (PDOException $e) {
    die("Błąd połączenia z SQL Server: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Edytuj Opinię</title>
<style>
    body { background-color: #AA656A; color: white; font-family: Arial, sans-serif; }
    form { background-color: #4C7B68; padding: 30px; max-width: 600px; margin: 50px auto; border-radius: 10px; }
    input, textarea, select { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; }
    input[type="submit"] { background-color: #4CAF50; color: white; border: none; cursor: pointer; }
    label { font-weight: bold; }
</style>
</head>
<body>
<script src="../runes-bg.js"></script>

<h2 style="text-align:center;">Edytuj Opinię</h2>

<form method="post">
    <label>Tekst opinii (max 500 znaków):</label>
    <textarea name="text" required><?= htmlspecialchars($review['Tekst'], ENT_QUOTES) ?></textarea>

    <label>Ilość gwiazdek (1-5):</label>
    <input type="number" name="stars" min="1" max="5" value="<?= $review['Ilosc_gwiazdek'] ?>" required>

    <label>Status:</label>
<select name="status" required>
    <option value="oczekujacy" <?= $review['Status'] == 'oczekujacy' ? 'selected' : '' ?>>Oczekujący</option>
    <option value="zatwierdzony" <?= $review['Status'] == 'zatwierdzony' ? 'selected' : '' ?>>Zatwierdzony</option>
    <option value="odrzucony" <?= $review['Status'] == 'odrzucony' ? 'selected' : '' ?>>Odrzucony</option>
</select>


    <input type="submit" value="Zapisz zmiany">
</form>

</body>
</html>
