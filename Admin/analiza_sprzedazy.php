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

    // =========================
    // 1. KONWERSJA PRODUKTU
    // =========================
    $productSql = "
        SELECT 
            p.id AS product_id,
            p.Nazwa AS product_name,
            p.Foto AS product_image,

            COUNT(DISTINCT vp.id) AS views_count,
            COUNT(DISTINCT zp.id) AS purchases_count,

            CASE 
                WHEN COUNT(DISTINCT vp.id) = 0 THEN 0
                ELSE CAST(COUNT(DISTINCT zp.id) * 100.0 / COUNT(DISTINCT vp.id) AS DECIMAL(10,2))
            END AS conversion_rate

        FROM Produkty p
        LEFT JOIN viewed_products vp 
            ON vp.product_id = p.id
        LEFT JOIN Zamowienia_produkty zp 
            ON zp.id_produktu = p.id

        GROUP BY p.id, p.Nazwa, p.Foto
        ORDER BY conversion_rate DESC
    ";

    $productStmt = $conn->query($productSql);
    $productStats = $productStmt->fetchAll(PDO::FETCH_ASSOC);

    // =========================
    // 2. KONWERSJA ŹRÓDŁA
    // =========================
    $sourceSql = "
        SELECT 
            vp.source,

            COUNT(DISTINCT vp.id) AS visits_count,
            COUNT(DISTINCT z.Id) AS purchases_count,

            CASE 
                WHEN COUNT(DISTINCT vp.id) = 0 THEN 0
                ELSE CAST(COUNT(DISTINCT z.Id) * 100.0 / COUNT(DISTINCT vp.id) AS DECIMAL(10,2))
            END AS conversion_rate

        FROM viewed_products vp
        LEFT JOIN Zamowienia z 
            ON z.Id_klienta = vp.user_id
           AND z.Data_utworzenia >= vp.viewed_at

        GROUP BY vp.source
        ORDER BY conversion_rate DESC
    ";

    $sourceStmt = $conn->query($sourceSql);
    $sourceStats = $sourceStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Błąd połączenia z bazą MSSQL: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analiza sprzedaży</title>
<link href="styleindex.css" rel="stylesheet">
<style>
    body {
        font-family: Arial, sans-serif;
    }

    .section {
        width: 90%;
        margin: 40px auto;
    }

    .stats-block {
        background: #4C7B68;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
    }

    .stats-block h2 {
        color: #8D242E;
        margin-bottom: 20px;
    }

    .stats-item {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 15px 0;
        border-bottom: 1px solid rgba(0,0,0,0.2);
    }

    .stats-item img {
        width: 80px;
        border-radius: 10px;
    }

    .stats-item:last-child {
        border-bottom: none;
    }

    .source-box {
        background: #fff;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 15px;
    }

    .admin-header {
        text-align: center;
        margin-top: 220px;
        font-size: 28px;
    }

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
  <a href="../../Konto/Zamowienia/Zamowienia.php" class="menu-button">Zamówienia</a>
  <a href="Pracowniki.php" class="menu-button">Zarządzania użytkownikami</a>
  <a href="opinie.php" class="menu-button ">Opinie</a>
  <a href="analiza_sprzedazy.php" class="menu-button  active">Statystyki</a>
</div>



<div class="section">

    <!-- KONWERSJA PRODUKTU -->
    <div class="stats-block">
        <h2>1. Konwersja produktu</h2>

        <?php if (!empty($productStats)): ?>
            <?php foreach ($productStats as $product): ?>
                <div class="stats-item">
                    <img src="../path_to_product_images/<?php echo htmlspecialchars($product['product_image']); ?>" alt="">
                    <div>
                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong><br>
                        Wyświetlenia: <?php echo $product['views_count']; ?><br>
                        Zakupy: <?php echo $product['purchases_count']; ?><br>
                        Konwersja: <strong><?php echo $product['conversion_rate']; ?>%</strong>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Brak danych o produktach.</p>
        <?php endif; ?>
    </div>

    <!-- KONWERSJA ŹRÓDŁA -->
    <div class="stats-block">
        <h2>2. Konwersja źródła</h2>

        <?php if (!empty($sourceStats)): ?>
            <?php foreach ($sourceStats as $source): ?>
                <div class="source-box">
                    <strong>Źródło:</strong> <?php echo htmlspecialchars($source['source']); ?><br>
                    Wejścia: <?php echo $source['visits_count']; ?><br>
                    Zakupy: <?php echo $source['purchases_count']; ?><br>
                    Konwersja: <strong><?php echo $source['conversion_rate']; ?>%</strong>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Brak danych o źródłach.</p>
        <?php endif; ?>
    </div>

</div>

<script src="../header-scroll.js"></script>
</body>
</html>