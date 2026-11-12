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

    $sql = "
        SELECT 
            z.id AS zamowienie_id,
            z.Data_utworzenia AS created_at,
            u.Imię AS first_name,
            u.Nazwisko AS last_name,
            u.email,
            p.Nazwa AS name,
            p.Foto AS image,
            p.Cena AS price,
            zp.Ilość AS quantity
        FROM Zamowienia z
        JOIN Użytkownicy u ON z.id = u.id
        JOIN Zamowienia_produkty zp ON z.id = zp.id
        JOIN Produkty p ON zp.id_produktu = p.id
        ORDER BY z.id DESC, p.Nazwa
    ";

    $stmt = $conn->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orders = [];
    foreach ($rows as $row) {
        $id = $row['zamowienie_id'];
        if (!isset($orders[$id])) {
            $orders[$id] = [
                'created_at' => $row['created_at'],
                'user' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email']
                ],
                'items' => []
            ];
        }
        $orders[$id]['items'][] = $row;
    }

} catch (PDOException $e) {
    die("Błąd połączenia z bazą MSSQL: " . $e->getMessage());
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
    <title>Panel admina — Zamówienia</title>
    <style>

        .order-block {
            margin-top: 20px;
            background-color: #4C7B68;
            border: 1px solid #ccc;
            margin-bottom: 30px;
            border-radius: 10px;
            padding: 20px;
        }

        .order-block h3 {
            margin-top: 0;
            color: #8D242E;
        }

        .red{
    font-family: 'Julee', Regular;
    color: #D73435;
  }
  
  .green{
    font-family: 'Julee', Regular;
    color: #649377;
  }

        .order-block ul {
            list-style: none;
            padding: 0;
            border-top: 1px solid black;
            margin-top: 10px;
        }

        .order-block li {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid black;
        }

        .order-block img {
            width: 80px;
            height: auto;
            margin-right: 15px;
        }

        .admin-header {
            text-align: center;
            font-size: 24px;
            margin-bottom: 40px;
            color: #000000ff;
        }

        .ggg{
            margin-top:0px;
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

.Odstep
        {
            text-align: center;
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

<div class="menu">
  <a href="Edycja.php" class="menu-button">Edycja produktu</a>
  <a href="../../Konto/Zamowienia/Zamowienia.php" class="menu-button active">Zamówienia</a>
  <a href="Pracowniki.php" class="menu-button">Zarządzania użytkownikami</a>
  <a href="opinie.php" class="menu-button ">Opinie</a>
  <a href="chat_stats_page.php" class="menu-button ">Statystyki czatu</a>
</div>

<h2 class="admin-header">Panel Administratora — Przegląd wszystkich zamówień</h2>

  <div class="ggg">
<?php if (!empty($orders)): ?>
    <?php foreach ($orders as $order_id => $order): ?>
        <div class="order-block">
            <h3>Zamówienie #<?php echo $order_id; ?> — <?php echo $order['created_at']; ?></h3>
            <p><strong>Użytkownik:</strong> <?php echo htmlspecialchars($order['user']['first_name'] . ' ' . $order['user']['last_name']); ?> — <?php echo htmlspecialchars($order['user']['email']); ?></p>
            <ul>
                <?php foreach ($order['items'] as $item): ?>
                    <li>
                        <img src="../path_to_product_images/<?php echo htmlspecialchars($item['image']); ?>" alt="">
                        <div>
                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                            Cena: <?php echo $item['price']; ?> PLN × Ilość: <?php echo $item['quantity']; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Brak zamówień do wyświetlenia.</p>
<?php endif; ?>
  </div>


<script src="../header-scroll.js"></script>
</body>
</html>
