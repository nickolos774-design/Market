<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../../../Regestracja and login/Html_files/Login.html");
    exit();
}

// Подключение к базе данных через config.php
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

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../../Regestracja and login/Html_files/Login.html");
    exit;
}

$user_id = $_SESSION['user']['id'];

// Запрос заказов пользователя с учетом диакритики
$sql = "
SELECT 
    z.Id AS zamowienie_id,
    z.Data_utworzenia,
    z.Status,
    p.Nazwa,
    p.Foto,
    p.Cena,
    zp.[Ilość]
FROM Zamowienia z
JOIN Zamowienia_produkty zp ON z.Id = zp.[Id_zamówienia]
JOIN Produkty p ON p.Id = zp.[Id_produktu]
WHERE z.Id_klienta = :user_id
ORDER BY z.Id DESC, p.Nazwa
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);

$orders = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $orderId = $row['zamowienie_id'];
    $orders[$orderId]['created_at'] = $row['Data_utworzenia'];
    $orders[$orderId]['status'] = $row['Status'];
    $orders[$orderId]['items'][] = [
        'name' => $row['Nazwa'],
        'image' => $row['Foto'],
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
              width: 60%;   
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

          .order-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: flex-start; 
    margin-bottom: 30px;
}

.order-block {
    flex: 2;
    background: #4C7B68;
    border: 1px solid black;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.invoice-block {
    flex: 1;
    background: #f3f3f3;
    border: 1px solid black;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    overflow: hidden; /* лишний контент скрыт */
    position: relative;
}
.invoice-button-container {
    display: flex;
    justify-content: flex-end; /* справа */
    margin-bottom: 30px; /* расстояние до следующего заказа */
}

.show-more-btn {
    padding: 6px 12px;
    background: #8D242E;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}



.invoice-block {
    background: #f3f3f3; /* можно другой фон для наглядности */
}

.print-btn {
    margin-top: 15px;
    padding: 10px 15px;
    background: #8D242E;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
}

.print-btn:hover {
    background: #a5303b;
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
  <a href="../../Zamowienia/Php_files/Zamowienia.php" class="menu-button">Zamówienia</a>
  <a href="../../Schowek/Php_files/Schowek.php" class="menu-button">Schowek</a>
  <a href="../../Opinie/Php_files/Opinie.php" class="menu-button">Opinie</a>
  <a href="../../Faktura/Php_files/Faktura.php" class="menu-button active">Faktura</a>
  <a href="../../Koszyk/Php_files/Koszyk.php" class="menu-button">Koszyk</a>
</div>

    <h1>Twoje zamówienia</h1>

  <?php foreach ($orders as $order_id => $order): ?>
    <div class="order-row">
        <!-- ЛЕВАЯ КОЛОНКА: заказ -->
        <div class="order-block" id="invoice">
            <h3>
                Zamówienie #<?php echo $order_id; ?> — <?php echo $order['created_at']; ?> — 
                <span class="order-status"><?php echo htmlspecialchars($order['status']); ?></span>
            </h3>

            <ul>
                <?php foreach ($order['items']  as $item): ?>
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

        

        <!-- ПРАВАЯ КОЛОНКА: чек -->
        <div class="invoice-block" id="invoice-<?php echo $order_id; ?>">
              <h3>Faktura</h3>
    <p><strong>Numer faktury:</strong> F-<?php echo $order_id; ?></p>
    <p><strong>Data:</strong> <?php echo $order['created_at']; ?></p>
    <p><strong>Status płatności:</strong> <?php echo htmlspecialchars($order['status']); ?></p>

     <hr>
    <h4>Sprzedawca:</h4>
    <p>Dragon World<br>
    ul. Fantastyczna 12<br>
    00-001 Warszawa<br>
    NIP: 123-456-78-90</p>

    
    <h4>Klient:</h4>
    <p><?php echo htmlspecialchars($_SESSION['user']['first_name'] . " " . $_SESSION['user']['last_name']); ?><br>
    <?php echo htmlspecialchars($_SESSION['user']['email']); ?></p>


    <hr>
    <h4>Produkty:</h4>
    <ul>
        <?php 
            $total = 0;
            foreach ($order['items'] as $item):
                $item_total = $item['price'] * $item['quantity'];
                $total += $item_total;
        ?>
            <li>
                <?php echo htmlspecialchars($item['name']); ?> —
                <?php echo $item['quantity']; ?> × <?php echo number_format($item['price'], 2); ?> PLN
                = <strong><?php echo number_format($item_total, 2); ?> PLN</strong>
            </li>
        <?php endforeach; ?>
    </ul>

            <hr>
            <h4>Podsumowanie</h4>
            <?php 
                $total = 0;
                foreach ($order['items'] as $item) {
                    $total += $item['price'] * $item['quantity'];
                }
            ?>
            <p><strong>Razem: <?php echo number_format($total, 2); ?> PLN</strong></p>

            <button class="print-btn" onclick="printInvoice('invoice-<?php echo $order_id; ?>')">
        🖨️ Drukuj fakturę
    </button>

    <a href="generate_invoice.php?order_id=<?php echo $order_id; ?>" target="_blank" class="print-btn">
    📄 Pobierz fakturę PDF

    
</a>

        </div>

        
    </div>

    <div class="invoice-button-container">
    <button class="show-more-btn" onclick="toggleInvoice('<?php echo $order_id; ?>', this)">⬇️ Pokaż pełną fakturą</button>
</div>


    
<?php endforeach; ?>


<script>

function printInvoice(invoiceId) {
    let invoiceBlock = document.getElementById(invoiceId);

    // найти все кнопки внутри блока
    let buttons = invoiceBlock.querySelectorAll(".print-btn");

    // скрыть их
    buttons.forEach(btn => btn.style.display = "none");

    let printContents = invoiceBlock.innerHTML;
    let originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    location.reload();
}

window.addEventListener('load', () => {
    document.querySelectorAll('.order-row').forEach(row => {
        const order = row.querySelector('.order-block');
        const invoice = row.querySelector('.invoice-block');

        // Берём высоту заказа
        const orderHeight = order.offsetHeight;

        // Присваиваем чекy такую же высоту
        invoice.style.height = orderHeight + 'px';
        invoice.style.overflowY = 'auto'; // если контент больше, включаем прокрутку
    });
});

function toggleInvoice(orderId, btn) {
    const invoice = document.getElementById('invoice-' + orderId);

    if (invoice.style.height === 'auto') {
        // Скрываем лишнее, возвращаем высоту заказа
        const order = invoice.closest('.order-row').querySelector('.order-block');
        invoice.style.height = order.offsetHeight + 'px';
        btn.textContent = '⬇️ Pokaż pełny';
    } else {
        // Показываем весь чек
        invoice.style.height = 'auto';
        btn.textContent = '⬆️ Ukryj pełny';
    }
}

// Синхронизация высоты чека с заказом при загрузке страницы
window.addEventListener('load', () => {
    document.querySelectorAll('.order-row').forEach(row => {
        const order = row.querySelector('.order-block');
        const invoice = row.querySelector('.invoice-block');
        invoice.style.height = order.offsetHeight + 'px';
        invoice.style.overflowY = 'auto'; // если чек длиннее, можно прокрутить
    });
});

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
