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

    // Удаление продукта
 if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    // Проверяем, существует ли продукт
    $stmtCheck = $conn->prepare("SELECT id FROM Produkty WHERE id = :id");
    $stmtCheck->execute(['id' => $delete_id]);
    $productExists = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$productExists) {
        echo "<script>
            alert('Produkt o podanym ID nie istnieje.');
            window.location.href='Edycja.php';
        </script>";
        exit();
    }

    // Пытаемся удалить продукт
    try {
        $stmtDelete = $conn->prepare("DELETE FROM Produkty WHERE id = :id");
        $stmtDelete->execute(['id' => $delete_id]);

        echo "<script>
            alert('Produkt został usunięty!');
            window.location.href='Edycja.php';
        </script>";
        exit();

    } catch (PDOException $e) {

        // Ошибка связи по FOREIGN KEY (товар в избранных)
        if ($e->getCode() == '23000') {
            echo "<script>
                alert('Nie można usunąć produktu, ponieważ znajduje się on w ulubionych u klienta.');
                window.location.href='Edycja.php';
            </script>";
            exit();
        }

        // Любая другая ошибка
        echo "<script>
            alert('Wystąpił błąd podczas usuwania produktu.');
            window.location.href='Edycja.php';
        </script>";
        exit();
    }
}



    // Получение всех продуктов
    $stmt = $conn->query("SELECT TOP (1000) id, Nazwa, Cena, Opis, Foto, id_kategoria FROM Produkty");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Błąd połączenia z SQL Server: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="styleindex.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Produkty - Admin</title>
    <style>

        .Odstep
        {
            text-align: center;
        }
        .hhh {
            margin-top: 200px;
        }

        table {
            width: 100%;
            background-color: #4C7B68;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #333;
            text-align: left;
        }

        th {
            background-color: #8D242E;
        }

        .imgg {
            max-width: 60px;
            height: auto;
        }

        .btn {
            padding: 5px 10px;
            margin-right: 5px;
            text-decoration: none;
            color: white;
            border-radius: 4px;
        }

        .edit-btn {
            background-color: #4CAF50;
        }

        .delete-btn {
            background-color: #f44336;
        }

        .ggg {
            margin-top: 0px;
        }

        .menu {
            display: flex;
            gap: 32px;
            margin-top: 300px;
            padding: 20px;
            border-radius: 10px;
            justify-content: center;
        }

        .menu-button {
            padding: 10px 20px;
            width: 200px;
            border: none;
            border-radius: 20px;
            background-color: #4C7B68;
            color: black;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        .menu-button.active {
            background-color: #7D121A;
            color: white;
        }

        .menu-button:hover {
            background-color: #3E614F;
        }
    </style>
</head>
<body>
<script src="../runes-bg.js"></script>

<header>
    <a href="../Index/Index.php" class="logo-link">
        <div class="logo header-item">
            <img src="../FOTO/12.png" alt="Логотип">
            <span><span class="red">Dragon</span> <span class="green">World</span></span>
        </div>
    </a>

    <div class="dropdown header-item">
        <button class="transparent-button">Kategorie</button>
        <div class="dropdown-content">
            <a href="../Kategoria/kategoria.php?id=1">Figurki smoków</a>
            <a href="../Kategoria/kategoria.php?id=2">Biżuteria</a>
            <a href="../Kategoria/kategoria.php?id=3">Plakaty</a>
            <a href="../Kategoria/kategoria.php?id=4">Odzież</a>
            <a href="../Kategoria/kategoria.php?id=5">książki</a>
        </div>
    </div>

    <div class="search-container header-item">
        <input type="text" id="searchInput" placeholder="Wyszukaj w sklepie">
        <button id="searchButton" class="search-btn"><i class="fas fa-search"></i></button>
        <div id="search-dropdown" class="dropdown-content"></div>
    </div>

    <?php if (isset($_SESSION['user'])): ?>
        <div class="user-info header-item">
            <div class="profile-drop">
            <a href="AdminPanel.php">
                <?php echo htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']); ?>
            </a>
            <?php
              require_once dirname(__DIR__) . '/secure/profile_menu.php';
              dw_profile_menu('../Konto/Moje dane/Php_files');
            ?>
            </div>
            <a href="logout.php">Wyloguj się</a>
        </div>
    <?php else: ?>
        <a href="../../Regestracja and login/Php_files/logout.php" class="konto header-item">Konto</a>
    <?php endif; ?>

    <a href="../../Konto/Schowek/Schowek.php" class="serdce header-item"><i class="fas fa-heart" style="color: #8D242E;"></i></a>
    <a href="../../Konto/Koszyk/Koszyk.php" class="koszyk header-item">Koszyk</a>
</header>

<div class="menu ggg">
    <a href="Edycja.php" class="menu-button active">Edycja produktu</a>
    <a href="AdminPanel.php" class="menu-button">Zamówienia</a>
    <a href="Pracowniki.php" class="menu-button ">Zarządzania użytkownikami</a>
    <a href="opinie.php" class="menu-button ">Opiniei</a>
</div>

<h2 class="Odstep">Panel Administratora — Lista produktów</h2>

<div class="hhhh">
   <table>
    <tr>
        <th>ID</th>
        <th>Nazwa</th>
        <th>Cena</th>
        <th>Opis</th>
        <th>Obraz</th>
        <th>ID Kategorii</th>
        <th>Akcje</th>
    </tr>
    <?php if ($products): ?>
        <?php foreach ($products as $row): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['Nazwa']) ?></td>
                <td><?= $row['Cena'] ?></td>
                <td><?= htmlspecialchars($row['Opis']) ?></td>
                <td><img class="imgg" src="../path_to_product_images/<?= htmlspecialchars($row['Foto']) ?>" alt=""></td>
                <td><?= $row['id_kategoria'] ?></td>
                <td>
                    <a href="edycja_produktu.php?id=<?= $row['id'] ?>" class="btn edit-btn">Edytuj</a>
                    <a href="?delete_id=<?= $row['id'] ?>" class="btn delete-btn" onclick="return confirm('Czy na pewno chcesz usunąć ten produkt?');">Usuń</a>

                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="7" style="text-align:center;">Brak produktów.</td></tr>
    <?php endif; ?>
</table>
</div>

<?php $conn = null; ?>
<script src="../header-scroll.js"></script>
</body>
</html>
