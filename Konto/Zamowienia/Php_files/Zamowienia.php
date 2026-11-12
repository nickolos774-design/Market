<?php
session_start();

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../../Regestracja and login/Html_files/Login.html");
    exit;
}

$config = require __DIR__ . '../../../../secure/config.php';

try {
    $pdo = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Błąd połączenia z bazą: " . $e->getMessage());
}

$user_id = $_SESSION['user']['id'] ?? null;
if (!$user_id) die("Brak danych użytkownika w sesji.");

// Получаем заказы пользователя с товарами
$sql = "
    SELECT
        z.Id AS zamowienie_id,
        z.Data_utworzenia,
        z.Status,
        zp.Ilość,
        zp.Cena,
        p.Nazwa AS product_name,
        p.Foto AS product_image
    FROM Zamowienia z
    JOIN zamowienia_produkty zp ON z.Id = zp.Id_zamówienia
    JOIN Produkty p ON p.id = zp.Id_produktu
    WHERE z.Id_klienta = ?
    ORDER BY z.Id DESC, p.Nazwa
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Структурируем данные по заказам
$orders = [];
foreach ($rows as $row) {
    $order_id = $row['zamowienie_id'];
    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'created_at' => $row['Data_utworzenia'],
            'status' => $row['Status'],
            'items' => []
        ];
    }
    $orders[$order_id]['items'][] = [
        'name' => $row['product_name'],
        'image' => $row['product_image'],
        'price' => $row['Cena'],
        'quantity' => $row['Ilość']
    ];
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Moje Zamówienia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link href="../Css_files/styleindex.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .order-block {
            background: #4C7B68;
            border: 1px solid black;
            border-radius: 8px;
            margin-bottom: 30px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .order-block h3 {
            margin-top: 0;
            color: #8D242E;
        }

        .order-block ul {
            list-style: none;
            padding: 0;
        }

        .order-block li {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid black;
        }

        .order-block li img {
            width: 80px;
            height: auto;
            margin-right: 15px;
            border-radius: 5px;
        }

        .no-orders {
            font-size: 18px;
            color: #333;
        }

        header a {
            color: black;           /* чёрный цвет для всех ссылок в header */
            text-decoration: none;  /* убираем подчёркивание */
          }

        header a:hover {
            color: #639477;            /* серый при наведении */
          }

          .order-status {
              font-weight: bold;
              color: #8D242E;
              margin-left: 10px;
          }

          .cart-count {
    background-color: #8D242E;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 14px;
    margin-left: 5px;
}


    </style>
</head>
<body>
<script src="../../../runes-bg.js"></script>

<header>
  <a href="../../../Index/Php_files/Index.php" class="logo-link">
    <div class="logo header-item">
    <link href="styleindex.css" rel="stylesheet">
      <img src="../../../Index/Zdecia/12.png" alt="Логотип">
      <span><span class="red">Dragon</span> <span class="green">World</span></span>
    </div>
  </a>

  <div class="dropdown header-item">
    <button class="transparent-button">Kategorie</button>
    <div class="dropdown-content">
      <a href="../../../Kategoria/Php_files/kategoria.php?id=1">Figurki smoków</a>
      <a href="../../../Kategoria/Php_files/kategoria.php?id=2">Biżuteria</a>
      <a href="../../../Kategoria/Php_files/kategoria.php?id=3">Plakaty</a>
      <a href="../../../Kategoria/Php_files/kategoria.php?id=4">Odzież</a>
      <a href="../../../Kategoria/Php_files/kategoria.php?id=5">książki</a>
    </div>
  </div>

  <div class="search-container header-item">
    <input type="text" id="searchInput" placeholder="Wyszukaj w sklepie">
    <button id="searchButton" class="search-btn"><i class="fas fa-search"></i></button>
    <div id="search-dropdown" class="dropdown-content"></div>
  </div>

  <?php if (isset($_SESSION['user'])): ?>
  <div class="user-info header-item">
    <?php
      $role = $_SESSION['user']['role'];

      // Ссылка в зависимости от роли
      $profileLink = $role === 'admin' ? '../../Admin/AdminPanel.php' :
                     ($role === 'employee' ? '../Pracownik/pracownikpanel.php' :
                     '../../Moje dane/Php_files/Moje dane.php');
    ?>

    <div class="profile-drop">
    <a href="<?php echo $profileLink; ?>" class="user-link">
<?php
          $max_length = 14;
          $first_name = $_SESSION['user']['first_name'];
          $last_name = $_SESSION['user']['last_name'];
          $first_name_first_letter = mb_substr($first_name, 0, 1, 'UTF-8');
          $last_name_length = mb_strlen($last_name, 'UTF-8');
          $last_name_first_letter = $last_name_length > 0 ? mb_substr($last_name, 0, 1, 'UTF-8') : '';
          $max_first_name_length = $max_length - 2;
          $first_name_rest = mb_substr($first_name, 1, max(0, $max_first_name_length - 1), 'UTF-8');
          $display_first_name = $first_name_first_letter . $first_name_rest;
          $display_last_name = $last_name_length > 1 ? $last_name_first_letter . '.' : $last_name_first_letter;
          $display_name = $display_first_name . ' ' . $display_last_name;
          echo '<span class="user-info-inline"><span class="user-name">' . htmlspecialchars($display_name) . '</span></span>';
        ?>
      </a>
      <?php
        require_once dirname(__DIR__, 3) . '/secure/profile_menu.php';
        dw_profile_menu('../../Moje dane/Php_files');
      ?>
    </div>
    </div>
    </div>
  <?php else: ?>
    <a href="../../../Regestracja and login/Php_files/logout.php" class="konto header-item">Konto</a>
  <?php endif; ?>

  <a href="../../Schowek/Php_files/Schowek.php" class="serdce header-item"><i class="fas fa-heart" style="color: #8D242E;"></i></a>
<a href="../../Koszyk/Php_files/Koszyk.php" class="koszyk header-item">
  Koszyk
  <span class="cart-count" id="cart-count"></span>
</a>
</header>

<div class="menu">
  <a href="../../Moje dane/Php_files/Moje dane.php" class="menu-button">Moje dane</a>
  <a href="../../Zamowienia/Php_files/Zamowienia.php" class="menu-button  active">Zamówienia</a>
  <a href="../../Schowek/Php_files/Schowek.php" class="menu-button">Schowek</a>
  <a href="../../Opinie/Php_files/Opinie.php" class="menu-button">Opinie</a>
  <a href="../../Faktura/Php_files/Faktura.php" class="menu-button">Faktura</a>
  <a href="../../Koszyk/Php_files/Koszyk.php" class="menu-button">Koszyk</a>
</div>

    <h1>Twoje zamówienia</h1>

    <?php if (!empty($orders)): ?>
        <?php foreach ($orders as $order_id => $order): ?>
            <div class="order-block">
                <h3>
                    Zamówienie #<?php echo $order_id; ?> — <?php echo $order['created_at']; ?> — 
                    <span class="order-status"><?php echo htmlspecialchars($order['status']); ?></span>
                </h3>

                <ul>
                    <?php foreach ($order['items'] as $item): ?>
                        <li>
                            <img src="../../path_to_product_images/<?php echo htmlspecialchars($item['image']); ?>" alt="">
                            <div>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                <?php echo $item['price']; ?> PLN × <?php echo $item['quantity']; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-orders">Brak zamówień.</p>
    <?php endif; ?>

    <script>

function updateCartCount() {
  fetch("../../Koszyk/cart_count2.php")
    .then(res => res.json())
    .then(data => {
      const cartCountElement = document.getElementById("cart-count");
      if (!cartCountElement) return;

      cartCountElement.textContent =
        (data.success && data.count > 0) ? `(${data.count})` : "";
    })
    .catch(err => console.error("Ошибка обновления корзины:", err));
}

updateCartCount();
setInterval(updateCartCount, 5000);
    
    </script>

<script src="../../../header-scroll.js"></script>
</body>
</html>
