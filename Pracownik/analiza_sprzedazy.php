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

/* ===============================
   LIGHT DRAGON BACKGROUND
================================*/

body{
    background:
    linear-gradient(135deg,#dff7ec,#e9f5ff);
    color:#1e293b;
}


/* ===============================
   MENU
================================*/

.menu{
    display:flex;
    justify-content:center;
    gap:25px;
    margin-top:40px;
}

.menu-button{
    text-decoration:none;
    padding:12px 22px;
    border-radius:14px;

    background:#4C7B68;
    color:white;
    font-weight:600;
    transition:.3s;
}

.menu-button:hover{
    background:#3E614F;
    transform:translateY(-2px);
}

.menu-button.active{
    background:#8D242E;
}


/* ===============================
   SECTION
================================*/

.section{
    width:90%;
    margin:40px auto;
}


/* ===============================
   STATS BLOCK
================================*/

.stats-block{
    background:white;

    padding:30px;
    border-radius:18px;

    box-shadow:
        0 10px 30px rgba(0,0,0,0.08);

    margin-bottom:40px;
}

.stats-block h2{
    color:#8D242E;
    margin-bottom:25px;
    font-size:26px;
}


/* ===============================
   PRODUCT ITEMS
================================*/

.stats-item{
    display:flex;
    align-items:center;
    gap:25px;

    padding:18px;
    border-radius:12px;

    transition:.25s;
}

.stats-item:hover{
    background:#f1f9f4;
}

.stats-item img{
    width:90px;
    border-radius:12px;
}

.stats-item strong{
    color:#4C7B68;
}


/* ===============================
   SOURCE BOX
================================*/

.source-box{
    background:#f8fafc;

    padding:18px;
    border-radius:14px;
    margin-bottom:15px;

    border-left:6px solid #649377;

    transition:.25s;
}

.source-box:hover{
    background:#eef7f1;
}


/* ===============================
   TEXT
================================*/

p{
    color:#334155;
}


/* ===============================
   SCROLLBAR
================================*/

::-webkit-scrollbar{
    width:10px;
}

::-webkit-scrollbar-thumb{
    background:#4C7B68;
    border-radius:10px;
}

</style>
</head>
<body>
<script src="../runes-bg.js"></script>



<div class="menu">
  <a href="pracownikpanel.php" class="menu-button">Produkty</a>
  <a href="analiza_sprzedazy.php" class="menu-button  active">Statystyki konwersji</a>
  <a href="statystyki_sprzedazy.php" class="menu-button">Statystyki sprzedaży</a>
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

</body>
</html>