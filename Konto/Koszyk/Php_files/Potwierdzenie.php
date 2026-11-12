<?php
session_start();

$host = 'localhost';
$dbname = 'dragon world';
$username = 'root'; 
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

if (!isset($_SESSION['user']['id'])) {
    die("Пользователь не авторизован.");
}
$user_id = $_SESSION['user']['id'];

// === Обработка POST (AJAX) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['quantity']) && isset($_POST['product_id'])) {
        $quantity = (int) $_POST['quantity'];
        $product_id = (int) $_POST['product_id'];

        if ($quantity > 0) {
            $stmt = $conn->prepare("UPDATE Koszyk SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $user_id, $product_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success, 'new_quantity' => $quantity]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Неверное количество']);
        }
        exit;
    }

    if (isset($_POST['delete']) && isset($_POST['product_id'])) {
        $product_id = (int) $_POST['product_id'];
        $stmt = $conn->prepare("DELETE FROM Koszyk WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $success = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $success, 'deleted' => true]);
        exit;
    }
}

// Получаем ID заказа из URL
$zamowienie_id = (int)$_GET['zamowienie_id'];

// Подготовка запроса для получения товаров конкретного заказа
$sql = "SELECT p.id AS product_id, p.name, p.price, p.description, p.image, p.id_kategoria, zp.quantity, z.created_at AS added_at
        FROM zamowienia_produkty zp
        JOIN zamowienia z ON zp.zamowienie_id = z.id
        JOIN Products p ON zp.product_id = p.id
        WHERE z.id = ?
        ORDER BY p.id ASC";

$stmt = $conn->prepare($sql);       // <- здесь создаём подготовленный запрос
$stmt->bind_param("i", $zamowienie_id); // <- здесь подставляем ID заказа
$stmt->execute();                   // <- выполняем запрос
$result = $stmt->get_result();

$order_items = [];
$total_price = 0;

while ($row = $result->fetch_assoc()) {
    $order_items[] = $row;
    $total_price += $row['price'] * $row['quantity'];
}

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

$stmt->close();


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
      width: calc(23% - 0px);
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

.center{

  text-align:center;
  font-size:32px;
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
}




  </style>
</head>

<body>
<script src="../../../runes-bg.js"></script>

<header>
  <a href="../../../Index/Index.php" class="logo-link">
    <div class="logo header-item">
      <img src="../../../FOTO/12.png" alt="Логотип">
      <span><span class="red">Dragon</span> <span class="green">World</span></span>
    </div>
  </a>

  <div class="dropdown header-item">
    <button class="transparent-button">Kategorie</button>
    <div class="dropdown-content">
      <a href="../../../Kategoria/kategoria.php?id=1">Figurki smoków</a>
      <a href="../../../Kategoria/kategoria.php?id=2">Biżuteria</a>
      <a href="../../../Kategoria/kategoria.php?id=3">Plakaty</a>
      <a href="../../../Kategoria/kategoria.php?id=4">Odzież</a>
      <a href="../../../Kategoria/kategoria.php?id=5">książki</a>
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
    <a href="../../../Regestracja and login/Php_files/logout.php" class="konto header-item">Konto</a>
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
  <a href="../../Moje dane/Moje dane.php" class="menu-button">Moje dane</a>
  <a href="../../Zamowienia/Zamowienia.php" class="menu-button">Zamówienia</a>
  <a href="../../Schowek/Schowek.php" class="menu-button">Schowek</a>
  <a href="../../Opinie/Opinie.php" class="menu-button">Opinie</a>
  <a href="../../Faktura/Faktura.php" class="menu-button">Faktura</a>
  <a href="Koszyk.php" class="menu-button active">Koszyk</a>
</div>

<div class="main-content">


<div>

<p class="center">Twoje zamowienie</p>

</div>

<div class="main-wrapper">
  <div class="product-container">
    <?php if (!empty($order_items)): ?>
      <?php foreach ($order_items as $row): ?>
        <div class="product-card">
          <a href="../../../StronaTowara/StronaTowara.php?id=<?php echo htmlspecialchars($row['product_id']); ?>" class="card-link">
            <img src="<?php echo htmlspecialchars(productImageUrl($row['image'])); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
          </a>

          <div class="card-content">
            <p class="product-title">
              <?php 
                $max_length = 30;
                $name = $row['name'];
                if(mb_strlen($name, 'UTF-8') > $max_length) {
                    $name = mb_substr($name, 0, $max_length, 'UTF-8') . '…';
                }
                echo htmlspecialchars($name); 
              ?>
            </p>

            <p class="product-price">Cena: <?php echo htmlspecialchars($row['price']); ?> PLN</p>
            <p class="product-quantity">
              Ilosc:
              <span class="quantity-number"><?php echo htmlspecialchars($row['quantity']); ?></span>
            </p>

            <p class="product-added">Dodano: <?php echo htmlspecialchars($row['added_at']); ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Ten zamówienie jest puste.</p>
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

document.querySelectorAll('.delete-form').forEach(function(form) {
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    

    const formData = new FormData(form);

    fetch('', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success && data.deleted) {
        // Удаляем карточку товара со страницы
        const productCard = form.closest('.product-card');
        productCard.remove();
        location.reload(); // Перезагружаем, чтобы обновить сумму и блок "fixed-summary"
      } else {
        alert('Błąd podczas usuwania produktu.');
      }
    })
    .catch(err => {
      console.error('Ошибка:', err);
      alert('Wystąpił błąd podczas usuwania.');
    });
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
    if (quantity > 1) { // минимальное количество = 1
      quantity--;
      updateQuantity(productId, quantity, numberSpan);
    }
  });
});

function updateQuantity(productId, quantity, numberSpan) {
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
      numberSpan.textContent = data.new_quantity;
      location.reload(); // обновляем блок "fixed-summary"
    } else {
      alert('Ошибка при обновлении: ' + (data.message || 'Неизвестная ошибка.'));
    }
  })
  .catch(error => {
    console.error('Ошибка:', error);
    alert('Ошибка при обновлении количества.');
  });
}

document.querySelectorAll('.delete-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    
    const productId = this.dataset.productId;
    const card = this.closest('.product-card');

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('delete', '1');

    fetch('', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.success && data.deleted) {
          card.remove();
          location.reload(); // обновляем сумму корзины
        } else {
          alert('Błąd podczas usuwania produktu.');
        }
      });
  });
});


</script>

<script src="../../../header-scroll.js"></script>
</body>
</html>

