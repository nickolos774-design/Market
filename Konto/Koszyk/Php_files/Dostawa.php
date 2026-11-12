<?php
session_start();
$last_delivery_type = $_SESSION['last_delivery_type'] ?? '';
$last_address_id = $_SESSION['last_address_id'] ?? null;


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

// Идентификатор пользователя
$user_id = $_SESSION['user']['id'] ?? 0;

// === Получаем корзину ===
$stmt = $pdo->prepare("
    SELECT p.id AS product_id, p.Nazwa AS name, p.Cena AS price, p.Opis AS description,
           p.Foto AS image, p.id_kategoria, k.Ilość AS quantity, k.Data_utworzenia AS added_at
    FROM koszyk k
    JOIN Produkty p ON k.Id_produkta = p.id
    WHERE k.Id_klienta = ?
    ORDER BY p.id ASC
");
$stmt->execute([$user_id]);
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

$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

// === AJAX обновление корзины ===
if (isset($_POST['ajax_update_cart'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $stmt = $pdo->prepare("UPDATE koszyk SET Ilość=? WHERE Id_klienta=? AND Id_produkta=?");
    $stmt->execute([$quantity, $user_id, $product_id]);

    // Новая общая сумма
    $stmt_total = $pdo->prepare("
        SELECT SUM(p.Cena*k.Ilość) AS total
        FROM koszyk k
        JOIN Produkty p ON k.Id_produkta = p.id
        WHERE k.Id_klienta=?
    ");
    $stmt_total->execute([$user_id]);
    $total_price = $stmt_total->fetchColumn();

    // Общее количество товаров
    $stmt_count = $pdo->prepare("SELECT SUM(Ilość) AS total_items FROM koszyk WHERE Id_klienta=?");
    $stmt_count->execute([$user_id]);
    $total_items = $stmt_count->fetchColumn();

    echo json_encode([
        'success' => true,
        'new_quantity' => $quantity,
        'total_price' => $total_price,
        'total_items' => $total_items
    ]);
    exit;
}

// === Получаем адрес пользователя (только один, по умолчанию) ===
$stmt = $pdo->prepare("
    SELECT TOP 1 *
    FROM Adresy_dostawy
    WHERE Id_klienta=?
    ORDER BY Domyślny DESC, Data_utworzenia ASC
");
$stmt->execute([$user_id]);
$default_address = $stmt->fetch(PDO::FETCH_ASSOC);

// Если адреса нет, $default_address будет false — приводим к пустому массиву,
// чтобы ниже в шаблоне не было "Trying to access array offset on value of type bool".
if ($default_address === false) {
    $default_address = [];
}

// === Обновление/добавление адреса через AJAX ===
// === Обновление/добавление адреса через AJAX ===
if (isset($_POST['update_address'])) {
    // Получаем тип доставки из формы
    $delivery_type = $_POST['delivery_type'] ?? '';
    $_SESSION['last_delivery_type'] = $delivery_type;

   if ($delivery_type === 'inpost') {
    $address = [
        'Miasto' => trim($_POST['inpost_miasto'] ?? ''),
        'Ulica' => trim($_POST['inpost_ulica'] ?? ''),
        'Kod_pocztowy' => trim($_POST['inpost_kod'] ?? ''),
        'Kraj' => trim($_POST['inpost_kraj'] ?? 'Polska'),
        'Wojewódstwo' => trim($_POST['inpost_wojewodztwo'] ?? ''),
    ];
    } elseif ($delivery_type === 'kurier') {
        $address = [
            'Miasto' => trim($_POST['kurier_miasto'] ?? ''),
            'Ulica' => trim($_POST['kurier_ulica'] ?? ''),
            'Kod_pocztowy' => trim($_POST['kurier_kod'] ?? ''),
            'Kraj' => trim($_POST['kurier_kraj'] ?? ''),
            'Wojewódstwo' => trim($_POST['kurier_wojewodztwo'] ?? ''),
        ];
    } else {
        die(json_encode(['success' => false, 'message' => 'Nieznany typ dostawy']));
    }

    // === Далее проверка обязательных полей и вставка в базу ===
    foreach ($address as $key => $value) {
        if ($value === '') {
            echo json_encode([
                'success' => false,
                'message' => "Pole $key jest wymagane"
            ]);
            exit;
        }
    }


// Проверка существующего адреса
$stmt_check = $pdo->prepare("
    SELECT id
    FROM Adresy_dostawy
    WHERE Id_klienta=? AND Miasto=? AND Wojewódstwo=? AND Kod_pocztowy=? AND Kraj=? AND Ulica=?
");
$stmt_check->execute([$user_id, $address['Miasto'], $address['Wojewódstwo'], $address['Kod_pocztowy'], $address['Kraj'], $address['Ulica']]);
$existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $address_id = $existing['id'];
} else {
    $stmt_insert = $pdo->prepare("
        INSERT INTO Adresy_dostawy 
            (Id_klienta, Miasto, Wojewódstwo, Kod_pocztowy, Kraj, Ulica, Domyślny, Data_utworzenia)
        VALUES (?, ?, ?, ?, ?, ?, 0, GETDATE())
    ");
    $stmt_insert->execute([$user_id, $address['Miasto'], $address['Wojewódstwo'], $address['Kod_pocztowy'], $address['Kraj'], $address['Ulica']]);
    $address_id = $pdo->lastInsertId();
}

$_SESSION['last_address_id'] = $address_id;

echo json_encode(['success' => true, 'address_id' => $address_id]);
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
        display: none; /* скрыто по умолчанию */
        margin-right: 722px;
        border-radius: 15px;
        padding: 20px;
        max-width: 500px;
        margin: 20px 250px;
      }

      .section.active {
  display: block; /* показывается при выборе */
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

  .proceed-payment{
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
          <a href="../../../StronaTowara/StronaTowara.php?id=<?php echo htmlspecialchars($row['product_id']); ?>" class="card-link">
            <img src="<?php echo htmlspecialchars(productImageUrl($row['image'])); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
          </a>
          <div class="card-content">
            <p class="product-title"><?php echo htmlspecialchars($row['name']); ?></p>
            <p class="product-price">Cena: <?php echo htmlspecialchars($row['price']); ?> PLN</p>
            <p class="product-quantity">
              Quantity:
             <div class="quantity-controls" data-product-id="<?php echo $row['product_id']; ?>">
    <button class="quantity-decrease">−</button>
    <span class="quantity-number"><?php echo htmlspecialchars($row['quantity']); ?></span>
    <button class="quantity-increase">+</button>
  </div>
            </p>
            <p class="product-added">Added on: <?php echo htmlspecialchars($row['added_at']); ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Twój koszyk jest pusty.</p>
    <?php endif; ?>
  </div>

  


<div class="checkout-wrapper">
  <div class="checkout-form">
    <h3>Wybierz dostawę:</h3>
    <div class="order-options">
      <button type="button" class="order-button" data-target="inpost-section">📦 Paczkomat InPost</button>
      <button type="button" class="order-button" data-target="kurier-section">🚚 Kurier</button>
    </div>

    <form id="checkout-form" method="post">
      <!-- Скрытое поле для передачи метода доставки -->
      <input type="hidden" name="delivery_method" id="delivery_method" value="">

      <!-- InPost Section -->
      <div id="inpost-section" class="section">
        <div class="title">InPost Paczkomat® 24/7</div>
        <label>Imię:</label>
        <input type="text" name="inpost_name" value="<?php echo htmlspecialchars($_SESSION['user']['first_name']); ?>" required>
        <label>Nazwisko:</label>
        <input type="text" name="inpost_lastname" value="<?php echo htmlspecialchars($_SESSION['user']['last_name']); ?>" required>
        <label>Numer telefonu:</label>
        <input type="tel" name="inpost_phone" pattern="[0-9+ ]{9,15}" placeholder="+48 600 000 000" required>
        <label>Adres e-mail:</label>
        <input type="email" name="inpost_email" value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>" required>
        <label>Wojewódstwo:</label>
        <input type="text" name="inpost_wojewodztwo" value="<?php echo htmlspecialchars($default_address['Wojewódstwo'] ?? ''); ?>" required>
        <label>Miasto:</label>
        <input type="text" name="inpost_miasto" value="<?php echo htmlspecialchars($default_address['Miasto'] ?? ''); ?>" required>
        <label>Ulica:</label>
        <input type="text" name="inpost_ulica" value="<?php echo htmlspecialchars($default_address['Ulica'] ?? ''); ?>" required>
        <label>Kod pocztowy:</label>
        <input type="text" name="inpost_kod" pattern="[0-9]{2}-[0-9]{3}" value="<?php echo htmlspecialchars($default_address['Kod_pocztowy'] ?? ''); ?>" required>
      </div>

      <!-- Kurier Section -->
      <div id="kurier-section" class="section">
        <div class="title">Adres dla kuriera</div>
        <label>Imię:</label>
        <input type="text" name="kurier_name" value="<?php echo htmlspecialchars($_SESSION['user']['first_name']); ?>" required>
        <label>Nazwisko:</label>
        <input type="text" name="kurier_lastname" value="<?php echo htmlspecialchars($_SESSION['user']['last_name']); ?>" required>
        <label>Numer telefonu:</label>
        <input type="tel" name="kurier_phone" pattern="[0-9+ ]{9,15}" required>
        <label>Adres e-mail:</label>
        <input type="email" name="kurier_email" value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>" required>
        <label>Kraj:</label>
        <input type="text" name="kurier_kraj" value="<?php echo htmlspecialchars($default_address['Kraj'] ?? ''); ?>" required>
        <label>Kod pocztowy:</label>
        <input type="text" name="kurier_kod" pattern="[0-9]{2}-[0-9]{3}" value="<?php echo htmlspecialchars($default_address['Kod_pocztowy'] ?? ''); ?>" required>
        <label>Miasto:</label>
        <input type="text" name="kurier_miasto" value="<?php echo htmlspecialchars($default_address['Miasto'] ?? ''); ?>" required>
        <label>Wojewódstwo:</label>
        <input type="text" name="kurier_wojewodztwo" value="<?php echo htmlspecialchars($default_address['Wojewódstwo']); ?>" required>
        <label>Ulica:</label>
        <input type="text" name="kurier_ulica" value="<?php echo htmlspecialchars($default_address['Ulica'] ?? ''); ?>" required>
        <label>Numer mieszkania (opcjonalnie):</label>
        <input type="text" name="kurier_numer_mieszkania">
      </div>

    </form>


    </div>

    <div class="fixed-summary">
      <img src="../Zdjecia/13.PNG" alt="Podsumowanie">
      <div class="summary-details">
        <div class="cart-summary-items">
          <?php foreach ($cart_items as $item): ?>
  <div class="cart-item-summary" data-product-id="<?php echo $item['product_id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" data-price="<?php echo htmlspecialchars($item['price']); ?>">
      <p><?php echo htmlspecialchars($item['name']); ?> - <?php echo htmlspecialchars($item['price']); ?> PLN x <?php echo htmlspecialchars($item['quantity']); ?></p>
  </div>
  <hr>
<?php endforeach; ?>

        </div>
        <p class="total-price">Łączna kwota: <?php echo number_format($total_price, 2, ',', ' '); ?> PLN</p>
        <button type="button" class="proceed-payment" data-url="Sposób płatności.php">Sposób płatności</button>

      </div>
    </div>
  </div>



  <script>
document.addEventListener("DOMContentLoaded", () => {
    // ==========================
    // Панель корзины
    // ==========================
    const toggleCartBtn = document.querySelector('.toggle-cart');
    const productPanel = document.querySelector('.product-container');
    const hiddenCartContainer = document.querySelector('.hidden-cart');

    const togglePanel = (panel, button) => {
        panel.classList.toggle('show');
        if (panel.classList.contains('show')) {
            document.body.style.overflow = 'hidden';
            button.textContent = button.classList.contains('toggle-cart') ? 'Towary ↓' : button.textContent;
        } else {
            document.body.style.overflow = '';
            button.textContent = button.classList.contains('toggle-cart') ? 'Towary →' : button.textContent;
        }
    };

    if (toggleCartBtn && productPanel) {
        toggleCartBtn.addEventListener('click', () => togglePanel(productPanel, toggleCartBtn));
    }
    if (toggleCartBtn && hiddenCartContainer) {
        toggleCartBtn.addEventListener('click', () => togglePanel(hiddenCartContainer, toggleCartBtn));
    }

    // ==========================
    // Количество товаров в корзине
    // ==========================
    const formatPrice = (value) => {
        const parts = parseFloat(value).toFixed(2).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        return parts.join(',');
    };

    const updateQuantity = (productId, quantity, numberSpan) => {
        const formData = new FormData();
        formData.append('ajax_update_cart', 1);
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    numberSpan.textContent = data.new_quantity;

                    // Обновляем общую сумму
                    const formattedTotal = formatPrice(data.total_price || 0);
                    document.querySelector('.total-price').textContent = 'Łączna kwota: ' + formattedTotal + ' PLN';

                    // Обновляем счетчик в шапке
                    const cartCountSpan = document.querySelector('.cart-count');
                    if (cartCountSpan) {
                        cartCountSpan.textContent = '(' + (data.total_items || 0) + ')';
                    }

                    // Обновляем текст товара в панели
                    const itemSummary = document.querySelector(`.cart-item-summary[data-product-id="${productId}"]`);
                    if (itemSummary) {
                        const price = formatPrice(itemSummary.dataset.price);
                        itemSummary.querySelector('p').textContent = `${itemSummary.dataset.name} - ${price} PLN x ${data.new_quantity}`;
                    }
                }
            })
            .catch(err => console.error('Ошибка:', err));
    };

    document.querySelectorAll('.quantity-controls').forEach(control => {
        const productId = control.dataset.productId;
        const numberSpan = control.querySelector('.quantity-number');

        control.querySelector('.quantity-increase').addEventListener('click', () => {
            let quantity = parseInt(numberSpan.textContent) + 1;
            updateQuantity(productId, quantity, numberSpan);
        });

        control.querySelector('.quantity-decrease').addEventListener('click', () => {
            let quantity = parseInt(numberSpan.textContent);
            if (quantity > 1) {
                updateQuantity(productId, quantity - 1, numberSpan);
            }
        });
    });

    // ==========================
    // Кнопки выбора способа доставки
    // ==========================
    document.querySelectorAll('.order-button').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
            const target = button.dataset.target;
            if (target) document.getElementById(target).classList.add('active');
        });
    });

    // ==========================
    // Отправка адреса и проверка формы
    // ==========================
    const paymentButton = document.querySelector('.proceed-payment');
    const form = document.querySelector('#checkout-form');

    if (paymentButton && form) {
        paymentButton.addEventListener('click', () => {
            const activeSection = form.querySelector('.section.active');
            if (!activeSection) {
                alert('Proszę wybrać metodę dostawy.');
                return;
            }

            const requiredFields = activeSection.querySelectorAll('input[required], select[required], textarea[required]');
            let allFilled = true;

            requiredFields.forEach(field => {
                const error = field.nextElementSibling;
                if (error && error.classList.contains('error-message')) error.remove();

                if (field.value.trim() === '') {
                    allFilled = false;
                    const errorMsg = document.createElement('div');
                    errorMsg.classList.add('error-message');
                    errorMsg.style.color = 'red';
                    errorMsg.style.fontSize = '14px';
                    errorMsg.style.textAlign = 'center';
                    errorMsg.textContent = 'Pole jest obowiązkowe';
                    field.insertAdjacentElement('afterend', errorMsg);
                }
            });

            if (!allFilled) {
                for (let field of requiredFields) {
                    if (field.value.trim() === '') {
                        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        field.focus();
                        break;
                    }
                }
                return;
            }

            // AJAX отправка формы адреса
            const formData = new FormData(activeSection.closest('form'));
            formData.append('update_address', '1');
            if (activeSection.id === 'inpost-section') formData.append('delivery_type', 'inpost');
            if (activeSection.id === 'kurier-section') formData.append('delivery_type', 'kurier');

            fetch(window.location.href, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = paymentButton.dataset.url;
                    } else {
                        alert(data.message || 'Błąd podczas zapisu adresu');
                    }
                })
                .catch(err => {
                    console.error('Ошибка:', err);
                    alert('Błąd połączenia z serwerem');
                });
        });
    }
});
</script>


  <script src="../../../header-scroll.js"></script>
</body>
  </html>
