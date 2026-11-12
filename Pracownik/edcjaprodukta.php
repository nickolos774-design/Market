<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employee') {
    header("Location: ../Regestracja and login/Html_files/Login.html");
    exit();
}

// Подключение к базе SQL Server через PDO
$config = require __DIR__ . '/../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        require __DIR__ . '/../Index/generate_products.php';
    

    // Проверка ID продукта
    if (!isset($_GET['id'])) {
        die("Brak ID produktu.");
    }

    $id = (int)$_GET['id'];

    // Получение всех категорий
    $stmtCategories = $conn->query("SELECT id, Nazwa FROM kategoria");
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

    // Получение данных продукта
    $stmtProduct = $conn->prepare("SELECT TOP 1 id, Nazwa, Cena, Opis, Foto, id_kategoria FROM Produkty WHERE id = :id");
    $stmtProduct->execute(['id' => $id]);
    $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Nie znaleziono produktu.");
    }

    // Обработка формы обновления
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $price = floatval($_POST['price']);
        $description = $_POST['description'];
        $id_kategoria = (int)$_POST['id_kategoria'];

        // Обработка изображения
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_name = basename($_FILES['image']['name']);
            $target_dir = "../FOTO/";
            $target_file = $target_dir . $image_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = "../FOTO/" . $image_name;
            } else {
                die("Błąd przesyłania pliku.");
            }
        } else {
            $image_path = $product['Foto']; // оставляем старое изображение
        }

        // Обновление продукта
        $stmtUpdate = $conn->prepare("
            UPDATE Produkty
            SET Nazwa = :name, Cena = :price, Opis = :description, Foto = :foto, id_kategoria = :id_kategoria
            WHERE id = :id
        ");

        $stmtUpdate->execute([
            'name' => $name,
            'price' => $price,
            'description' => $description,
            'foto' => $image_path,
            'id_kategoria' => $id_kategoria,
            'id' => $id
        ]);

            updateProductsJson($conn);

        header("Location: ../Pracownik/pracownikpanel.php");
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edytuj Produkt</title>
<style>
    body { background-color: #AA656A; color: white; font-family: Arial, sans-serif; }
    form { background-color: #4C7B68; padding: 40px; max-width: 600px; margin: 50px auto; border-radius: 10px; }
    input[type="text"], input[type="number"], textarea, input[type="file"], select { width: 100%; padding: 10px; margin: 10px 0; }
    input[type="submit"] { background-color: #4CAF50; color: white; padding: 12px; border: none; width: 100%; cursor: pointer; }
</style>
</head>
<body>
<script src="../runes-bg.js"></script>

<h2 style="text-align:center;">Edytuj Produkt</h2>

<form method="post" enctype="multipart/form-data">
    <label>Nazwa:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($product['Nazwa']) ?>" required>

    <label>Cena:</label>
    <input type="number" name="price" value="<?= $product['Cena'] ?>" step="0.01" required>

    <label>Opis:</label>
    <textarea name="description" rows="6" required><?= htmlspecialchars($product['Opis']) ?></textarea>

    <label>Obraz (opcjonalnie):</label>
    <input type="file" name="image">
    <p>Obecny obraz: <?= htmlspecialchars($product['Foto']) ?></p>

    <label>Kategoria:</label>
    <select name="id_kategoria" required>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $product['id_kategoria'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['Nazwa']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="submit" value="Zapisz zmiany">
</form>

</body>
</html>
