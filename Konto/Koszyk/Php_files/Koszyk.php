<?php
session_start();

$config = require __DIR__ . '/../../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Błąd połączenia z bazą: " . $e->getMessage());
}

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../../Regestracja and login/Html_files/Login.html");
    exit;
}
$user_id = $_SESSION['user']['id'];

// === Обработка POST (AJAX) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Изменение количества
   if (isset($_POST['quantity'], $_POST['product_id'])) {
    $quantity = (int) $_POST['quantity'];
    $product_id = (int) $_POST['product_id'];

    if ($quantity > 0) {
        $stmt = $conn->prepare("UPDATE koszyk SET [Ilość] = :quantity WHERE Id_klienta = :user_id AND Id_produkta = :product_id");
        $success = $stmt->execute([
            ':quantity' => $quantity,
            ':user_id' => $user_id,
            ':product_id' => $product_id
        ]);
        echo json_encode(['success' => $success, 'new_quantity' => $quantity]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Неверное количество']);
    }
    exit;
}


    // Удаление товара
    if (isset($_POST['delete'], $_POST['Id_produkta'])) {
        $product_id = (int) $_POST['Id_produkta'];
        $stmt = $conn->prepare("DELETE FROM koszyk WHERE Id_klienta = :Id_klienta AND Id_produkta = :Id_produkta");
        $success = $stmt->execute([
            ':Id_klienta' => $user_id,
            ':Id_produkta' => $product_id
        ]);
        echo json_encode(['success' => $success, 'deleted' => true]);
        exit;
    }
}

// === Получение данных корзины ===
$sql = "SELECT 
            p.id AS product_id,
            p.Nazwa AS name,
            p.Cena AS price,
            p.Opis AS description,
            p.Foto AS image,
            k.Ilość AS quantity,
            k.Data_utworzenia AS added_at
        FROM koszyk k
        JOIN Produkty p ON k.Id_produkta = p.id
        WHERE k.Id_klienta = :user_id
        ORDER BY p.id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
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

// Общая сумма
$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += $item['price'] * $item['quantity'];
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
      width: calc(30% - 20px);
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

.delete-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  background: transparent;
  border: none;
  font-size: 24px; /* размер корзины */
  cursor: pointer;
  color: black; /* черная корзина */
  z-index: 2;
}

.delete-btn:hover {
  color: #b83232; /* при наведении красная */
}

.product-card {
  position: relative; /* чтобы кнопка позиционировалась относительно карточки */
}




    .product-card .new-label {
      position: absolute;
      left: 10px;
      background-color: #8D242E;
      color: #fff;
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

header a {
    color: black;           /* чёрный цвет для всех ссылок в header */
    text-decoration: none;  /* убираем подчёркивание */
  }

header a:hover {
    color: #639477;            /* серый при наведении */
  }


@media (max-width: 768px) {
      .product-container {
        margin-top: -300px;
        margin-right: 0;
        justify-content: center;
      }

      .product-card {
        width: 100%;
        margin-bottom: 20px;
      }

      .fixed-summary {
        position: relative;
        margin-top: 400px;
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
        font-size: 12px;  /* Снижаем размер шрифта в форме */
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
                     ($role === 'employee' ? '../../../Pracownik/pracownikpanel.php' :
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
    <a href="../../../Regestracja and login/Html_files/Login.html" class="konto header-item">Konto</a>
  <?php endif; ?>

  <a href="../../Schowek/Php_files/Schowek.php" class="serdce header-item"><i class="fas fa-heart" style="color: #8D242E;"></i></a>
  <?php include "cart_count.php"; ?>

<a href="Koszyk.php" class="koszyk header-item">
  Koszyk
  <span class="cart-count" id="cart-count">
    <?php if ($total_items_in_cart > 0): ?>
      (<?php echo $total_items_in_cart; ?>)
    <?php endif; ?>
  </span>
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

<div class="main-content">


<div class="main-wrapper">
  <!-- Товары -->
  <div class="product-container">
    <?php if (!empty($cart_items)): ?>
      <?php foreach ($cart_items as $row): ?>
        <div class="product-card">
          <a href="../../../StronaTowara/StronaTowara.php?id=<?php echo htmlspecialchars($row['product_id']); ?>" class="card-link">
            <img src="<?php echo htmlspecialchars(productImageUrl($row['image'])); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
          </a>

           <button class="delete-btn" data-product-id="<?php echo $row['product_id']; ?>" title="Usuń z koszyka">🗑️</button>

          <div class="card-content">
            <p class="product-title">
  <?php 
    $max_length = 30;
    $name = $row['name'];
    if(mb_strlen($name, 'UTF-8') > $max_length) {
        $name = mb_substr($name, 0, $max_length, 'UTF-8') . '…'; // добавляем троеточие
    }
    echo htmlspecialchars($name); 
  ?>
</p>

            <p class="product-price" data-price="<?php echo $row['price']; ?>">
    Cena: <?php echo $row['price']; ?> PLN
</p>

           <p class="product-quantity">
  Ilosc:
  <div class="quantity-controls" data-product-id="<?php echo $row['product_id']; ?>">
    <button class="quantity-decrease">−</button>
    <span class="quantity-number"><?php echo htmlspecialchars($row['quantity']); ?></span>
    <button class="quantity-increase">+</button>
  </div>
</p>

            <p class="product-added">Dodano: <?php echo htmlspecialchars($row['added_at']); ?></p>
            <form class="delete-form" method="POST" data-product-id="<?php echo $row['product_id']; ?>">
              <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
              <input type="hidden" name="delete" value="1">
            
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Twój koszyk jest pusty.</p>
    <?php endif; ?>
  </div>

<div class="fixed-summary">
    <img src="../Zdjecia/12.png" alt="Podsumowanie">
    <div class="summary-details">
      <div class="cart-summary-items">
        <?php foreach ($cart_items as $item): ?>
          <div class="cart-item-summary" data-product-id="<?php echo $item['product_id']; ?>" data-price="<?php echo $item['price']; ?>">
            <p>
              <?php echo htmlspecialchars($item['name']); ?> - 
              <span class="item-total"><?php echo $item['price'] * $item['quantity']; ?></span> PLN x 
              <span class="item-quantity"><?php echo $item['quantity']; ?></span>
            </p>
          </div>
          <hr class="cart-item-divider" data-product-id="<?php echo $item['product_id']; ?>">
        <?php endforeach; ?>
      </div>
      <p class="total-price">
        Łączna kwota: <?php echo number_format($total_price, 2, ',', ' '); ?> PLN
      </p>
      <?php if (!empty($cart_items)): ?>
        <a href="Dostawa.php" class="order-button">Złóż zamówienie</a>
      <?php endif; ?>
    </div>
</div>











<script>
// Обработчик отправки формы через AJAX
document.querySelectorAll('input.quantity-input').forEach(function(input) {
  input.addEventListener('input', function() {
    const productId = this.dataset.productId;
    const quantity = parseInt(this.value);

    if (!productId || quantity < 1) return; // валидация

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch('', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        console.log('Количество обновлено:', data.new_quantity);
        // Можно обновить сумму, если хотите сделать это без перезагрузки
        location.reload(); // или обновить часть DOM вручную
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

document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const card = this.closest('.product-card');

        const formData = new FormData();
        formData.append('Id_produkta', productId); // обязательно совпадает с PHP
        formData.append('delete', '1');

        fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.deleted) {
                    // удаляем карточку товара
                    card.remove();
                    const summary = document.querySelector(`.cart-item-summary[data-product-id="${productId}"]`);
                    if (summary) {
                        const nextElement = summary.nextElementSibling;
                        if (nextElement && nextElement.tagName === 'HR') {
                            nextElement.remove();
                        }
                        summary.remove();
                    }

                    const divider = document.querySelector(`.cart-item-divider[data-product-id="${productId}"]`);
                    if (divider) divider.remove();

                    // пересчет общей суммы
                    let total = 0;
                    document.querySelectorAll('.cart-item-summary').forEach(item => {
                        const price = parseFloat(item.dataset.price);
                        const qty = parseInt(item.querySelector('.item-quantity').textContent);
                        total += price * qty;
                    });
                    document.querySelector('.total-price').textContent = 'Łączna kwota: ' + total.toFixed(2) + ' PLN';

                    if (!document.querySelector('.product-card')) {
                        document.querySelector('.product-container').innerHTML = '<p>Twój koszyk jest pusty.</p>';
                        const orderButton = document.querySelector('.order-button');
                        if (orderButton) orderButton.remove();
                    }

                    updateCartCount();
                }
            })
            .catch(err => console.error(err));
    });
});



document.querySelectorAll('.quantity-controls').forEach(function(control) {
  const productId = control.dataset.productId;
  const numberSpan = control.querySelector('.quantity-number');

  control.querySelector('.quantity-increase').addEventListener('click', function() {
    let quantity = parseInt(numberSpan.textContent);
    quantity++;
    updateQuantity(productId, quantity, numberSpan);
  });

  control.querySelector('.quantity-decrease').addEventListener('click', function() {
    let quantity = parseInt(numberSpan.textContent);
    if (quantity > 1) { 
      quantity--;
      updateQuantity(productId, quantity, numberSpan);
    }
  });
});

function updateQuantity(productId, quantity, numberSpan) {
  const formData = new FormData();
  formData.append('quantity', quantity);
  formData.append('product_id', productId);

  fetch('', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        numberSpan.textContent = data.new_quantity;

        // Обновляем сумму для этого товара в fixed-summary
        const summary = document.querySelector(`.cart-item-summary[data-product-id="${productId}"]`);
        const pricePerUnit = parseFloat(summary.dataset.price); // цена за 1 шт
        summary.querySelector('.item-quantity').textContent = quantity;
        summary.querySelector('.item-total').textContent = (pricePerUnit * quantity).toFixed(2);

        // Пересчитываем общую сумму
        let total = 0;
        document.querySelectorAll('.cart-item-summary').forEach(item => {
          const price = parseFloat(item.dataset.price);
          const qty = parseInt(item.querySelector('.item-quantity').textContent);
          total += price * qty;
        });
        document.querySelector('.total-price').textContent = 'Łączna kwota: ' + total.toFixed(2) + ' PLN';

        updateCartCount();
      } 
      else 
        {
        alert('Ошибка при обновлении количества');
        }
    });
}

function updateCartCount() {
  fetch("cart_count2.php")
    .then(res => res.json())
    .then(data => {
      const cartCountElement = document.getElementById("cart-count");
      if (!cartCountElement) return;

      cartCountElement.textContent = (data.success && data.count > 0) ? `(${data.count})` : "";
    })
    .catch(err => console.error("Ошибка обновления корзины:", err));
}

// фоновые обновления каждые 5 секунд (по желанию)
setInterval(updateCartCount, 5000);

// обновление при загрузке страницы
updateCartCount();



</script>

<script src="../../../header-scroll.js"></script>
</body>
</html>

