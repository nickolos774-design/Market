<?php
session_start();

// Проверка роли
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employee') {
    header("Location: ../Regestracja and login/Html_files/Login.html");
    exit();
}

$config = require __DIR__ . '../../../secure/config.php';


try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    require __DIR__ . '../../../Index/generate_products.php';


    // Получаем список категорий
    $stmtCategories = $conn->query("SELECT id, Nazwa FROM kategoria");
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $price = floatval($_POST['price']);
        $description = $_POST['description'];
        $id_kategoria = (int)$_POST['id_kategoria'];

        // Загрузка изображения
        $image_path = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_name = basename($_FILES['image']['name']);
            $target_dir = "../FOTO/";
            $target_file = $target_dir . $image_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = "../FOTO/" . $image_name;
            } else {
                die("Błąd przesyłania pliku.");
            }
        }

        // Вставка нового продукта
        $stmtInsert = $conn->prepare("
            INSERT INTO Produkty (Nazwa, Cena, Opis, Foto, id_kategoria)
            VALUES (:name, :price, :description, :foto, :id_kategoria)
        ");
        $stmtInsert->execute([
            'name' => $name,
            'price' => $price,
            'description' => $description,
            'foto' => $image_path,
            'id_kategoria' => $id_kategoria
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
<title>Dodaj nowy produkt</title>
<style>
    body { background-color: #AA656A; color: white; font-family: Arial; }
    form { background-color: #4C7B68; padding: 30px; max-width: 500px; margin: 50px auto; border-radius: 10px; }
    input, textarea, select { width: 100%; padding: 10px; margin: 10px 0; }
    input[type="submit"] { background-color: #4CAF50; color: white; cursor: pointer; }
</style>
</head>
<body>
<script src="../runes-bg.js"></script>

<h2 style="text-align:center;">Dodaj nowy produkt</h2>

<form method="post" enctype="multipart/form-data">
    <label>Nazwa:</label>
    <input type="text" name="name" required>

    <label>Cena:</label>
    <input type="number" name="price" step="0.01" required>

    <label>Opis:</label>
    <textarea name="description" rows="6" required></textarea>

    <label>Obraz:</label>
    <input type="file" name="image">

    <label>Kategoria:</label>
    <select name="id_kategoria" required>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['Nazwa']) ?></option>
        <?php endforeach; ?>
    </select>

    <input type="submit" value="Dodaj produkt">
</form>

</body>
</html>
