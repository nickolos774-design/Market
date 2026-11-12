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

    if (isset($_GET['delete_id'])) {
        $delete_id = (int)$_GET['delete_id'];

        $stmtCheck = $conn->prepare("SELECT id FROM opinie WHERE id = :id");
        $stmtCheck->execute(['id' => $delete_id]);
        $reviewExists = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($reviewExists) {
            $stmtDelete = $conn->prepare("DELETE FROM opinie WHERE id = :id");
            $stmtDelete->execute(['id' => $delete_id]);
            echo "<script>alert('Opinia została usunięta!'); window.location.href='OpinieAdmin.php';</script>";
            exit();
        } else {
            echo "<script>alert('Opinia o podanym ID nie istnieje.'); window.location.href='OpinieAdmin.php';</script>";
            exit();
        }
    }

    $stmt = $conn->query("
        SELECT TOP (1000) 
            o.id,
            o.id_klienta,
            o.id_produkta,
            o.Tekst,
            o.Ilosc_gwiazdek,
            o.Data_utworzenia,
            o.Status,
            u.Imię, u.Nazwisko,
            p.Nazwa AS ProduktNazwa
        FROM opinie o
        LEFT JOIN Użytkownicy u ON o.id_klienta = u.id
        LEFT JOIN Produkty p ON o.id_produkta = p.id
        ORDER BY o.Data_utworzenia DESC
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
<body>

<div class="menu">
    <a href="pracownikpanel.php" class="menu-button">Produkty</a>
    <a href="OpinieAdmin.php" class="menu-button active">Opinie</a>
    <a href="analiza_sprzedazy.php" class="menu-button ">Statystyki</a>
</div>

<h2 class='Odstep'>Panel pracownika — Lista opinii</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Użytkownik</th>
        <th>Produkt</th>
        <th>Tekst</th>
        <th>Gwiazdy</th>
        <th>Data</th>
        <th>Status</th>
        <th>Akcje</th>
    </tr>
    <?php if ($reviews): ?>
        <?php foreach ($reviews as $row): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['Imię'] . ' ' . $row['Nazwisko']) ?></td>
                <td><?= htmlspecialchars($row['ProduktNazwa']) ?></td>
                <td><?= htmlspecialchars($row['Tekst']) ?></td>
                <td><?= $row['Ilosc_gwiazdek'] ?></td>
                <td><?= $row['Data_utworzenia'] ?></td>
                <td><?= htmlspecialchars($row['Status']) ?></td>
                <td>
                    <a href="edit_opinia.php?id=<?= $row['id'] ?>" class="btn edit-btn">Edytuj</a>
                    <a href="?delete_id=<?= $row['id'] ?>" class="btn delete-btn" onclick="return confirm('Czy na pewno chcesz usunąć tę opinię?');">Usuń</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="8" style="text-align:center;">Brak opinii.</td></tr>
    <?php endif; ?>
</table>

<?php $conn = null; ?>
<script src="../header-scroll.js"></script>
</body>
</html>
