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

// =========================
// ZAMÓWIENIA DZIŚ
// =========================
$todayOrdersSql = "
    SELECT COUNT(*) as total
    FROM Zamowienia
    WHERE CAST(Data_utworzenia AS DATE) = CAST(GETDATE() AS DATE)
";

$todayOrders = $conn->query($todayOrdersSql)->fetch(PDO::FETCH_ASSOC);


// =========================
// ZAMÓWIENIA W TYM MIESIĄCU
// =========================
$monthOrdersSql = "
    SELECT COUNT(*) as total
    FROM Zamowienia
    WHERE MONTH(Data_utworzenia) = MONTH(GETDATE())
    AND YEAR(Data_utworzenia) = YEAR(GETDATE())
";

$monthOrders = $conn->query($monthOrdersSql)->fetch(PDO::FETCH_ASSOC);


// =========================
// CAŁKOWITY PRZYCHÓD
// =========================
$revenueSql = "
    SELECT 
        ISNULL(SUM(Cena * Ilość), 0) as revenue
    FROM zamowienia_produkty
";

$revenue = $conn->query($revenueSql)->fetch(PDO::FETCH_ASSOC);


// =========================
// ŚREDNIA WARTOŚĆ ZAMÓWIENIA
// =========================
$avgSql = "
    SELECT 
        ISNULL(AVG(order_total), 0) as avg_order
    FROM (
        SELECT 
            Id_zamówienia,
            SUM(Cena * Ilość) as order_total
        FROM zamowienia_produkty
        GROUP BY Id_zamówienia
    ) as orders_summary
";

$avgOrder = $conn->query($avgSql)->fetch(PDO::FETCH_ASSOC);


// =========================
// ANULOWANE ZAMÓWIENIA
// =========================
$cancelSql = "
    SELECT COUNT(*) as total
    FROM Zamowienia
    WHERE Status = 'cancelled'
";

$cancelledOrders = $conn->query($cancelSql)->fetch(PDO::FETCH_ASSOC);


// =========================
// OCZEKUJĄCE ZAMÓWIENIA
// =========================
$pendingSql = "
    SELECT COUNT(*) as total
    FROM Zamowienia
    WHERE Status = 'pending'
";

$pendingOrders = $conn->query($pendingSql)->fetch(PDO::FETCH_ASSOC);


// =========================
// TOP PRODUKTY
// =========================
$topProductsSql = "
    SELECT TOP 5
        p.Nazwa,
        p.Foto,
        SUM(zp.Ilość) as sold_quantity

    FROM zamowienia_produkty zp

    JOIN Produkty p
        ON p.id = zp.Id_produktu

    GROUP BY p.Nazwa, p.Foto

    ORDER BY sold_quantity DESC
";

$topProducts = $conn->query($topProductsSql)->fetchAll(PDO::FETCH_ASSOC);
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
   LIGHT DRAGON ADMIN STYLE
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
}


/* ===============================
   ITEMS
================================*/

.stats-item{
    display:flex;
    align-items:center;
    gap:25px;

    padding:18px 0;

    border-bottom:1px solid #dbe4ea;

    transition:.25s;
}


.stats-item:last-child{
    border-bottom:none;
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
  <a href="analiza_sprzedazy.php" class="menu-button">Statystyki konwersji</a>
  <a href="statystyki_sprzedazy.php" class="menu-button  active">Statystyki sprzedaży</a>
</div>

<div class="section">

    <div class="stats-block">
        <h2>Statystyki sprzedaży</h2>

        <div class="stats-item">
            <strong>Liczba zamówień dziś:</strong>
            <?= $todayOrders['total'] ?>
        </div>

        <div class="stats-item">
            <strong>Liczba zamówień w tym miesiącu:</strong>
            <?= $monthOrders['total'] ?>
        </div>

        <div class="stats-item">
            <strong>Całkowity przychód:</strong>
            <?= number_format($revenue['revenue'], 2) ?> zł
        </div>

        <div class="stats-item">
            <strong>Średnia wartość zamówienia:</strong>
            <?= number_format($avgOrder['avg_order'], 2) ?> zł
        </div>

        <div class="stats-item">
            <strong>Anulowane zamówienia:</strong>
            <?= $cancelledOrders['total'] ?>
        </div>

        <div class="stats-item">
            <strong>Zamówienia oczekujące:</strong>
            <?= $pendingOrders['total'] ?>
        </div>
    </div>


    <div class="stats-block">
        <h2>Najlepiej sprzedające się produkty</h2>

      <?php foreach($topProducts as $product): ?>

<div class="stats-item">

    <img src="../path_to_product_images/<?php echo htmlspecialchars($product['Foto']); ?>" alt="">

    <div>
        <strong>
            <?= htmlspecialchars($product['Nazwa']) ?>
        </strong>

        <p>
            Sprzedano:
            <?= $product['sold_quantity'] ?>
        </p>
    </div>

</div>

<?php endforeach; ?>

    </div>

</div>


</body>
</html>