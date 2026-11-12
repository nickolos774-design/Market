<?php
session_start();

$config = require __DIR__ . '/../../../secure/config.php';

try {
    $pdo = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе: " . $e->getMessage());
}

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../../Regestracja and login/Html_files/Login.html");
    exit;
}

$user_id = $_SESSION['user']['id'] ?? 0;

$saved_payment = $_SESSION['payment_method'] ?? '';

// Получение товаров из корзины с информацией о продукте
$sql = "
    SELECT k.id AS koszyk_id, k.Id_klienta, k.Id_produkta, k.Ilość, k.Data_utworzenia,
           p.Nazwa, p.Cena, p.Opis, p.Foto, p.id_kategoria
    FROM [dragon_world].[dbo].[koszyk] k
    JOIN [dragon_world].[dbo].[Produkty] p
        ON k.Id_produkta = p.id
    WHERE k.Id_klienta = :user_id
    ORDER BY p.id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function productImageUrl($path) {
    $path = str_replace('\\', '/', trim((string) $path));

    if ($path === '' || preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    if (preg_match('#FOTO/(.+)$#i', $path, $matches)) {
        return '../../../FOTO/' . ltrim($matches[1], '/');
    }

    return '../../' . ltrim($path, '/');
}

// Считаем общую сумму и количество
$total_price = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $total_price += $item['Cena'] * $item['Ilość'];
    $total_items += $item['Ilość'];
}

// === AJAX обновление количества ===
if (isset($_POST['ajax_update_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = max(1, (int)$_POST['quantity']); // минимум 1

    // Обновляем количество в корзине
    $update_sql = "UPDATE [dragon_world].[dbo].[koszyk] SET Ilość = :quantity WHERE Id_klienta = :user_id AND Id_produkta = :product_id";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([
        'quantity' => $quantity,
        'user_id' => $user_id,
        'product_id' => $product_id
    ]);

    // Получаем новую общую сумму
    $total_sql = "
        SELECT SUM(p.Cena * k.Ilość) AS total,
               SUM(k.Ilość) AS total_items
        FROM [dragon_world].[dbo].[koszyk] k
        JOIN [dragon_world].[dbo].[Produkty] p ON k.Id_produkta = p.id
        WHERE k.Id_klienta = :user_id
    ";
    $total_stmt = $pdo->prepare($total_sql);
    $total_stmt->execute(['user_id' => $user_id]);
    $totals = $total_stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'new_quantity' => $quantity,
        'total_price' => $totals['total'] ?? 0,
        'total_items' => $totals['total_items'] ?? 0
    ]);
    exit;
}

// Количество товаров для отображения в шапке
$total_items_in_cart = $total_items;

// === AJAX сохранение метода оплаты ===
if (isset($_POST['ajax_payment'])) {
    $payment_method_name = $_POST['payment_method'] ?? '';

    if ($payment_method_name) {
        $_SESSION['payment_method'] = $payment_method_name;

        // Получаем Id метода оплаты из базы
        $stmt = $pdo->prepare("SELECT Id FROM Metoda_platnosci WHERE Nazwa = ? AND Aktywna = 1");
        $stmt->execute([$payment_method_name]);
        $payment_method_id = $stmt->fetchColumn();

        if ($payment_method_id) {
            $_SESSION['payment_method_id'] = $payment_method_id;
        }

        echo json_encode([
            'success' => true,
            'payment_method' => $payment_method_name,
            'payment_method_id' => $_SESSION['payment_method_id'] ?? null
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nie wybrano metody płatności']);
    }
    exit;
}



?>




<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link href="../Css_files/styleindex.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
    }

    .hidden-cart {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.6s ease-in-out, opacity 0.6s;
  opacity: 0;
}

.hidden-cart.show {
  max-height: 100000px; 
  opacity: 1;
}

.toggle-cart {
  cursor: pointer;
  color: #8D242E;
  font-weight: bold;
  font-size: 24px;
  margin: 20px;
  transition: color 0.3s ease;
}

.toggle-cart:hover {
  color: #a62c39;
}
    .product-container {
      margin-right: 740px;
      margin-top: 10px;
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      padding: 20px;
      justify-content: flex-start;
    }

    .product-card .card-content {
      padding: 10px;
      background-color: #8D242E;
      color: #fff;
      border-radius: 5px;
    }

    .product-card img {
      width: 100%;
      height: auto;
    }

    .product-card .new-label {
      position: absolute;
      top: 10px;
      left: 10px;
      background-color: #8D242E;
      color: #fff;
      padding: 5px;
      font-size: 12px;
    }

    h1 {
      margin: 0;
    }

    .order-form {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .order-form h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      font-size: 16px;
      margin-bottom: 5px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 10px;
      font-size: 16px;
      border-radius: 5px;
      border: 1px solid #ccc;
      box-sizing: border-box;
    }

    .form-group textarea {
      height: 120px;
    }

    .order-summary {
      background:rgb(0, 0, 0);
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      margin-top: 20px;
    }

    .order-summary p {
      margin: 5px 0;
      font-size: 16px;
    }

    .order-summary .total {
      font-weight: bold;
      font-size: 18px;
    }

.fixed-summary {
  position: relative; 
  margin-top: 0px;
  height: 700px;
  width: 650px;
  margin-left: auto;  /* прижмет к правому краю */
  margin-right: 0;
  background-color: #4C7B68;
  padding: 10px;
  border-radius: 10px 0 0 10px;
  box-shadow: -2px 0 8px rgba(0,0,0,0.2);
}

    .fixed-summary img {
      width: 100%;  /* Растягивает изображение на всю ширину блока */
      height: auto;  /* Сохраняет пропорции изображения */
      border-radius: 5px;
    }

    .fixed-summary .cart-summary-items {
  margin-top: 10px;
  padding-left: 10px;
}

.fixed-summary .cart-item-summary {
  margin-bottom: 5px;
}

.fixed-summary .cart-item-summary p {
    font-size: 26px;
    color: black; /* Черный цвет текста */
    margin: 3px 0;
}
.fixed-summary .summary-details p {
    color: black; /* Черный цвет текста */
}

hr {
    border: none;
    border-top: 3px solid rgb(0, 0, 0); /* Сплошная линия с цветом */
    margin: 10px 0; /* Отступы сверху и снизу */
}
.fixed-summary .summary-details p {
    color: black; /* Белый цвет текста в этом блоке */
}

.total-price {
  font-size: 26px;  /* Увеличиваем размер шрифта */
  font-weight: bold;  /* Делаем текст жирным */
  color: #000;  /* Цвет текста - черный */
}

h2 {
      text-align: center;
      font-size: 24px;
      margin-bottom: 10px;
    }

    .section {
      margin-right: 722px;
      border-radius: 15px;
      padding: 20px;
      max-width: 500px;
      margin: 20px 250px;
    }

    label {
      display: block;
      margin: 10px 0 5px;
      font-size: 18px;
    }

    input {
      width: 90%;
      padding: 10px;
      font-size: 16px;
      border: none;
      border-radius: 10px;
      background-color: #bbb; /* светло-серый фон, как на картинке */
    }

    .double-input {
      display: flex;
      margin-top: 30px;
      gap: 10px;
    }

    .double-input input {
      flex: 1;
    }

    .title {
      font-size: 20px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 15px;
    }

    .top-title {
      font-size: 22px;
      font-style: italic;
      font-weight: bold;
    }
    

    .main-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  padding: 20px;
}

   .product-container {
  flex: 1;
  display: flex;
  flex-wrap: wrap;
  gap: 45px;
}

    .product-card {
      width: calc(27% - 20px);
      text-align: center;
      position: relative;
      box-sizing: border-box;
    }

    .product-card .card-content {
      background-color: #8D242E;
      color: #000000ff;
 font-size: 12px;
    }

    .product-price {
    font-size: 2em;
}

.payment-summary-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 30px;
  margin: 30px;
}

/* Блок выбора оплаты */
.payment-box {
  width: 300px;
  background: #4C7B68;
  padding: 20px;
  border-radius: 10px;
}

/* Подсуммирование */
.fixed-summary {
  width: 650px;
}



    .product-description {
    font-size: 1vw;
    color: #000000;
    margin-top: 5px;
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

    .product-title {
    padding-top: 10px;
    text-align: center;
    font-weight: bold;
    font-size: 2em;
    }

.product-card img {
  display: block;      /* убираем пустую дырку снизу */
  width: 100%;         /* ширина картинки не больше блока */
  max-width: 700px;    /* ограничение ширины */
  max-height: 400px;   /* ограничение высоты */
  object-fit: contain; /* сохраняем пропорции, не обрезая картинку */
  margin: 0 auto;      /* центрируем изображение внутри блока */
  border-radius: 5px;
}

.quantity-controls {
  display: flex;
  align-items: center;
  justify-content: center; /* центрирует всё по горизонтали */
  gap: 20px; /* увеличиваем расстояние между кнопками и числом */
}

.quantity-controls input.quantity-number {
  display: none; /* скрываем поле ввода */
}

.quantity-controls button {
  background: none;       /* прозрачный фон */
  border: none;           /* без границ */
  color: black;           /* черный цвет + и - */
  font-size: 32px;        /* увеличиваем кнопки */
  cursor: pointer;
  padding: 0;
  line-height: 1;
}

.quantity-controls button:hover {
  color: #555; /* чуть темнее при наведении, по желанию */
}

.quantity-controls .quantity-number {
  font-size: 24px;        /* крупное число */
  font-weight: bold;
  min-width: 50px;        /* фиксированная ширина */
  text-align: center;     /* центр цифры */
  display: inline-block;
  color: black;
}

.product-quantity {
  font-size: 20px; /* размер текста "Quantity:" */
  font-weight: bold;
  margin-bottom: 10px;
}

header a {
    color: black;           /* чёрный цвет для всех ссылок в header */
    text-decoration: none;  /* убираем подчёркивание */
  }

header a:hover {
    color: #639477;            /* серый при наведении */
  }

  .cart-count {
  background-color: #8D242E;
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 14px;
  margin-left: 5px;
}

.product-container {
  position: fixed;
  top: 414px; /* под уровень подсуммирования */
  left: 0;
  width: 60%;
  height: calc(100% - 460px); /* высота до низа окна */
  background: #AA656A;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
  overflow-y: auto; /* прокрутка только внутри панели */
  transform: translateX(-100%);
  transition: transform 0.4s ease-in-out;
  z-index: 1000;
  padding: 20px;
}

/* когда открыта панель */
.product-container.show {
  transform: translateX(0);
}




/* когда открыта */
.product-container.show {
  transform: translateX(0);
}

/* заголовок-стрелка */
.top-title.toggle-cart {
  cursor: pointer;
  font-weight: bold;
  margin: 10px 0;
  display: inline-block;
}

.checkout-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 30px; /* расстояние между формой и подсуммированием */
}

/* форма слева */
.checkout-form {
  flex: 1;
  max-width: 65%; /* ширина формы */
}


@media (max-width: 768px) {
      .product-container {
        margin-right: 0;
        justify-content: center;
      }

      .product-card {
        width: 100%;
        margin-bottom: 20px;
      }

      .fixed-summary {
        position: relative;
        margin-top: 600px;
        width: 90%;
        height: auto;
        margin-left: 0;
        box-shadow: none;
        margin-left: 10px;
        border-radius: 10px;
        
      }

      .total-price {
        font-size: 22px;  /* Уменьшаем размер шрифта для мобильных устройств */
      }

      .order-summary p {
        font-size: 14px; /* Уменьшаем размер шрифта */
      }

      .cart-item-summary p {
        font-size: 18px;  /* Уменьшаем размер шрифта для описания товаров */
      }

      /* Уменьшаем размеры кнопок на мобильных устройствах */
      .order-form button,
      .fixed-summary button {
        padding: 8px;
        font-size: 14px;
      }

      .form-group input,
      .form-group select,
      .form-group textarea {
        font-size: 14px;
      }

      /* Скрываем ненужные элементы или изменяем стили для мобильных */
      .user-info {
        font-size: 14px;
      }

      .header-item {
        font-size: 14px;
      }

      .section {
        width: 50%;
        margin-left:0px;
      margin-right: 722px;
      border-radius: 15px;
      padding: 20px;
      max-width: 500px;
      margin: 20px 250px;
    }
    }

    @media (max-width: 480px) {
      .total-price {
        font-size: 18px; /* Еще больше уменьшаем размер шрифта */
      }

      .cart-item-summary p {
        font-size: 16px;  /* Еще больше уменьшаем размер шрифта */
      }

      .order-form input,
      .order-form select,
      .order-form textarea {
        font-size: 120px;  /* Снижаем размер шрифта в форме */
      }
    }

    .order-button {
  display: block;
  width: 300px;
  margin: 20px auto; /* Центрирует кнопку по горизонтали */
  padding: 15px 30px; /* Увеличенные отступы для большей кнопки */
  font-size: 18px; /* Увеличенный размер шрифта */
  background-color: #8D242E;
  color: white;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  transition: background-color 0.3s ease;
  text-align: center;
}

.order-button:hover {
  background-color: #a62c39; /* Цвет при наведении */
}




  </style>
</head>

<body>
<script src="../../../runes-bg.js"></script>

<header>
  <a href="../../../Index/Index.php" class="logo-link">
    <div class="logo header-item">
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
$max_length = 14;
$first_name = $_SESSION['user']['first_name'];
$last_name = $_SESSION['user']['last_name'];

// Первая буква имени
$first_name_first_letter = mb_substr($first_name, 0, 1, 'UTF-8');

// Длина фамилии
$last_name_length = mb_strlen($last_name, 'UTF-8');

// Берём первую букву фамилии (если есть)
$last_name_first_letter = $last_name_length > 0 ? mb_substr($last_name, 0, 1, 'UTF-8') : '';

// Максимальная длина для имени (минус 2 символа: пробел и буква фамилии)
$max_first_name_length = $max_length - 2;

// Обрезаем оставшуюся часть имени (после первой буквы)
$first_name_rest = mb_substr($first_name, 1, max(0, $max_first_name_length - 1), 'UTF-8');

$display_first_name = $first_name_first_letter . $first_name_rest;

// Если фамилия больше 1 символа — добавляем точку после буквы, иначе нет
if ($last_name_length > 1) {
    $display_last_name = $last_name_first_letter . '.';
} else {
    $display_last_name = $last_name_first_letter;
}

$display_name = $display_first_name . ' ' . $display_last_name;

echo '<div class="profile-drop">';
echo '<span class="user-info-inline">';
echo '<span class="user-name">' . htmlspecialchars($display_name) . '</span>';
echo '</span>';
require_once dirname(__DIR__, 3) . '/secure/profile_menu.php';
dw_profile_menu('../../Moje dane/Php_files');
echo '</div>';
?>
    </div>
  <?php else: ?>
    <a href="../../../Regestracja and login/Html_files/Login.html" class="konto header-item">Konto</a>
  <?php endif; ?>

  <a href="../../Schowek/Php_files/Schowek.php" class="serdce header-item"><i class="fas fa-heart" style="color: #8D242E;"></i></a>
  <?php include "cart_count.php"; ?>

<a href="Koszyk.php" class="koszyk header-item">
  Koszyk
  <?php if ($total_items_in_cart > 0): ?>
    <span class="cart-count">(<?php echo $total_items_in_cart; ?>)</span>
  <?php endif; ?>
</a>
</header>

<div class="menu">
  <a href="../../Moje dane/Php_files/Moje dane.php" class="menu-button">Moje dane</a>
  <a href="../../Zamowienia/Php_files/Zamowienia.php" class="menu-button">Zamówienia</a>
  <a href="../../Schowek/Php_files/Schowek.php" class="menu-button">Schowek</a>
  <a href="../../Opinie/Php_files/Opinie.php" class="menu-button">Opinie</a>
  <a href="../../Faktura/Php_files/Faktura.php" class="menu-button">Faktura</a>
  <a href="Koszyk.php" class="menu-button active">Koszyk</a>
</div>


<div class="top-title toggle-cart">Towary →</div>

<div class="product-container">
  <?php if (!empty($cart_items)): ?>
  <?php foreach ($cart_items as $row): ?>
    <div class="product-card">
      <a href="../../../StronaTowara/StronaTowara.php?id=<?php echo htmlspecialchars($row['Id_produkta']); ?>" class="card-link">
        <img src="<?php echo htmlspecialchars(productImageUrl($row['Foto'])); ?>" alt="<?php echo htmlspecialchars($row['Nazwa']); ?>">
      </a>
      <div class="card-content">
        <p class="product-title"><?php echo htmlspecialchars($row['Nazwa']); ?></p>
        <p class="product-price">Cena: <?php echo htmlspecialchars($row['Cena']); ?> PLN</p>
        <p class="product-quantity">
          Quantity:
          <div class="quantity-controls" data-product-id="<?php echo $row['Id_produkta']; ?>">
            <button class="quantity-decrease">−</button>
            <span class="quantity-number"><?php echo htmlspecialchars($row['Ilość']); ?></span>
            <button class="quantity-increase">+</button>
          </div>
        </p>
        <p class="product-added">Added on: <?php echo htmlspecialchars($row['Data_utworzenia']); ?></p>
      </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p>Twój koszyk jest pusty.</p>
<?php endif; ?>

</div>


  <div class="checkout-wrapper">
  <div class="checkout-form">
    <div class="section">
       
    <div class="title">Sposób płatności</div>
    <div class="radio-group">
    <div class="option">
        <input type="radio" name="payment" id="blik" value="blik" <?php echo ($saved_payment === 'blik') ? 'checked' : ''; ?>>
        <label for="blik">Blik kod</label>
    </div><br>
    <div class="option">
        <input type="radio" name="payment" id="transfer" value="transfer" <?php echo ($saved_payment === 'transfer') ? 'checked' : ''; ?>>
        <label for="transfer">Przelew</label>
    </div>


  </div>
  </div>

<input type="hidden" name="delivery_method" value="">


  </div>

  <div class="fixed-summary">
    <img src="../Zdjecia/14.PNG" alt="Podsumowanie">
    <div class="summary-details">
      <div class="cart-summary-items">
                 <?php foreach ($cart_items as $item): ?>
<div class="cart-item-summary" data-product-id="<?php echo $item['Id_produkta']; ?>" data-name="<?php echo htmlspecialchars($item['Nazwa']); ?>" data-price="<?php echo htmlspecialchars($item['Cena']); ?>">
  <p><?php echo htmlspecialchars($item['Nazwa']); ?> - <?php echo htmlspecialchars($item['Cena']); ?> PLN x <?php echo htmlspecialchars($item['Ilość']); ?></p>
</div>

  <hr>
<?php endforeach; ?>
      </div>
      <p class="total-price">Łączna kwota: <?php echo number_format($total_price, 2, ',', ' '); ?> PLN</p>
          <a href="Podsumowanie.php" class="order-button">Podsumowanie</a>
    </div>
  </div>
</div>



<script>
// Обработчик отправки формы через AJAX
document.querySelectorAll('.update-btn').forEach(function(button) {
  button.addEventListener('click', function(e) {
    e.preventDefault();  // Отменить стандартное поведение формы

    var form = this.closest('form');
    var formData = new FormData(form);

    fetch('', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {

        // Обновить отображение
        var quantityInput = form.querySelector('input[name="quantity"]');
        quantityInput.value = data.new_quantity;  // Обновить количество на клиенте
      } else {

      }
    })
    .catch(error => {
      console.error('Ошибка:', error);
      alert('Ошибка при обновлении количества.');
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
  const toggleButton = document.querySelector('.toggle-cart');
  const productPanel = document.querySelector('.product-container');

  toggleButton.addEventListener('click', function () {
    productPanel.classList.toggle('show');

    if (productPanel.classList.contains('show')) {
      document.body.style.overflow = 'hidden'; // блокируем прокрутку страницы
      toggleButton.textContent = 'Towary ↓';
    } else {
      document.body.style.overflow = ''; // возвращаем прокрутку
      toggleButton.textContent = 'Towary →';
    }
  });
});


document.querySelectorAll('.update-btn').forEach(function(button) {
  button.addEventListener('click', function(e) {
    e.preventDefault();  // Отменить стандартное поведение формы

    var form = this.closest('form');
    var formData = new FormData(form);

    fetch('', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Обновить количество товара на странице
        var quantityInput = form.querySelector('input[name="quantity"]');
        quantityInput.value = data.new_quantity;  // Обновить количество на клиенте
      } else {
        alert('Ошибка при обновлении: ' + (data.message || 'Неизвестная ошибка.'));
      }
    })
    .catch(error => {
      console.error('Ошибка:', error);
      alert('Ошибка при обновлении количества.');
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.querySelector('.toggle-cart');
    const cartContainer = document.querySelector('.hidden-cart');

    toggleButton.addEventListener('click', function () {
      cartContainer.classList.toggle('show');

      // Меняем стрелочку
      if (cartContainer.classList.contains('show')) {
        toggleButton.textContent = 'Towary ↓';
      } else {
        toggleButton.textContent = 'Towary →';
      }
    });
  });

  document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.querySelector('.toggle-cart');
    const cartContainer = document.querySelector('.hidden-cart');

    toggleButton.addEventListener('click', function () {
      cartContainer.classList.toggle('show');

      // Меняем стрелочку
      if (cartContainer.classList.contains('show')) {
        toggleButton.textContent = 'Towary ↓';
      } else {
        toggleButton.textContent = 'Towary →';
      }
    });
  });

  document.querySelectorAll('.quantity-controls').forEach(function(control) {
    const productId = control.dataset.productId;
    const numberSpan = control.querySelector('.quantity-number');

    control.querySelector('.quantity-increase').addEventListener('click', function() {
        let quantity = parseInt(numberSpan.textContent) + 1;
        updateQuantity(productId, quantity, numberSpan);
    });

    control.querySelector('.quantity-decrease').addEventListener('click', function() {
        let quantity = parseInt(numberSpan.textContent);
        if(quantity > 1) {
            quantity--;
            updateQuantity(productId, quantity, numberSpan);
        }
    });
});

// Функция для форматирования числа как в PHP number_format($value, 2, ',', ' ')
function formatPrice(value) {
    const parts = parseFloat(value).toFixed(2).split('.'); // 2 знака после запятой
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' '); // добавляем пробел как разделитель тысяч
    return parts.join(','); // заменяем точку на запятую
}

function updateQuantity(productId, quantity, numberSpan){
    const formData = new FormData();
    formData.append('ajax_update_cart', 1);
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch(window.location.href, {method:'POST', body: formData})
    .then(res => res.json())
    .then(data => {
        if(data.success){
            numberSpan.textContent = data.new_quantity;

            // Форматируем сумму точно как PHP
            const formattedTotal = formatPrice(data.total_price || 0);
            document.querySelector('.total-price').textContent = 'Łączna kwota: ' + formattedTotal + ' PLN';

            // Обновляем счетчик товаров в шапке
            const cartCountSpan = document.querySelector('.cart-count');
            if(cartCountSpan) {
                cartCountSpan.textContent = '(' + (data.total_items || 0) + ')';
            }

            // Обновляем текст товара в панели
            const itemSummary = document.querySelector(`.cart-item-summary[data-product-id="${productId}"]`);
            if(itemSummary) {
                const price = formatPrice(itemSummary.dataset.price);
                itemSummary.querySelector('p').textContent = `${itemSummary.dataset.name} - ${price} PLN x ${data.new_quantity}`;
            }
        }
    })
    .catch(err => console.error('Ошибка:', err));
}

document.querySelectorAll('input[name="payment"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const paymentMethod = this.value;
        const formData = new FormData();
        formData.append('ajax_payment', 1);
        formData.append('payment_method', paymentMethod);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                console.log('Payment method saved:', data.payment_method, 'ID:', data.payment_method_id);
                // Можно обновить UI, если нужно
            } else {
                alert('Błąd zapisu metody płatności: ' + (data.message || 'Nieznany błąd'));
            }
        })
        .catch(err => console.error('Ошибка сохранения оплаты:', err));
    });
});



</script>

<script src="../../../header-scroll.js"></script>
</body>
</html>

