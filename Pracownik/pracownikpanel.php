<?php
session_start();

if (
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'employee'
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

        require __DIR__ . '/../Index/generate_products.php';
        updateProductsJson($conn);

        echo "<script>
            alert('Produkt został usunięty!');
            window.location.href='pracownikpanel.php';
        </script>";
        exit();

    } catch (PDOException $e) {

        // Если FK ошибка
        if ($e->getCode() == '23000') {

            $msg = "Nie można usunąć produktu, ponieważ jest używany w innych miejscach.";

            // Узнаём, какая именно FK вызвала ошибку
            $errorText = $e->getMessage();

            if (strpos($errorText, 'FK_favorites_products') !== false) {
                $msg = "Nie można usunąć produktu, ponieważ znajduje się on w ulubionych u klienta.";
            } elseif (strpos($errorText, 'FK_zamowienia_produkty_produkty') !== false) {
                $msg = "Nie można usunąć produktu, ponieważ znajduje się w zamówieniach.";
            }

            echo "<script>
                alert('{$msg}');
                window.location.href='pracownikpanel.php';
            </script>";
            exit();
        }

        // Любая другая ошибка
        echo "<script>
            alert('Wystąpił błąd podczas usuwania produktu.');
            window.location.href='pracownikpanel.php';
        </script>";
        exit();
    }
}




    // Получение всех продуктов
    $stmt = $conn->query("
    SELECT TOP (1000)
        p.id,
        p.Nazwa,
        p.Cena,
        p.Opis,
        p.Foto,
        k.Nazwa AS kategoria
    FROM Produkty p
    LEFT JOIN kategoria k
        ON p.id_kategoria = k.id
    ORDER BY k.Nazwa, p.Nazwa
");
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
    <title>Produkty</title>
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
            background-color: #4b775d;
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
            background-color: #7dbe7f;
        }

        .delete-btn {
            background-color: #ee665d;
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
</header>

<div class="menu ggg">
    <a href="pracownikpanel.php" class="menu-button active">Lista produktów </a>
    <a href="analiza_sprzedazy.php" class="menu-button">Statystyki konwersji</a>
    <a href="statystyki_sprzedazy.php" class="menu-button">Statystyki sprzedaży</a>
</div>

<h2 class="Odstep">Panel Pracowników — Lista produktów</h2>

<div style="text-align: left; margin-top: 20px;">
    <a href="dodajprodukt.php"
       class="btn edit-btn"
       style="background-color: #66ac68;
              font-size: 22px;
              padding: 12px 25px;
              border-radius: 12px;">
        Dodaj produkt
    </a>
</div>



<div class="hhhh">
   <table>
    <tr>
        <th>ID</th>
        <th>Nazwa</th>
        <th>Cena</th>
        <th>Opis</th>
        <th>Kategoria</th>
        <th>Akcje</th>
    </tr>
    <?php if ($products): ?>
        <?php foreach ($products as $row): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['Nazwa']) ?></td>
                <td><?= $row['Cena'] ?></td>
                <td><?= htmlspecialchars($row['Opis']) ?></td>
                <td><?= $row['kategoria'] ?></td>
                <td>
                    <a href="edcjaprodukta.php?id=<?= $row['id'] ?>" class="btn edit-btn">Edytuj</a>
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
