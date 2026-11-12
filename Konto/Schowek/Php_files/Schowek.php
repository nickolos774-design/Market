<?php
session_start();

if (!isset($_SESSION['user']['id'])) {
    header("Location: ../../../Regestracja and login/Html_files/Login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link href="../Css_files/styleindex.css" rel="stylesheet">
  <script src="../../../quick-view.js" defer></script>
  <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .product-title {
      padding-top: 10px;
      text-align: center;
      font-weight: bold;
      font-size: 2em;
    }

    .card:hover {
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .card img {
      width: 100%;
      height: auto;
      display: block;
    }

    .card-content {
      padding: 10px;
    }

    .product-description {
      font-size: 0.9em;
      color: black;
      margin: 5px 0;
      height: 45px;
      overflow: hidden;
    }

    .product-price {
      font-weight: bold;
      color: black;
      margin: 0;
    }

    .delete-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      background: transparent;
      border: none;
      cursor: pointer;
      font-size: 20px;
      color: #8D242E;
      transition: box-shadow 0.3s ease, transform 0.3s ease;
      z-index: 2;
    }

    .delete-btn:hover {
      color: #b83232; 
    }

    .product-container {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      padding: 20px 5%;
      row-gap: 25px;
      box-sizing: border-box;
      justify-content: flex-start;
    }

    .product-container.center {
      justify-content: center;
    }

    .card {
      flex: 0 1 calc((90% - 36px) / 4);
      box-sizing: border-box;
      border-radius: 6px;
      overflow: hidden;
      background: #8D242E;
      display: flex;
      flex-direction: column;
       transition: box-shadow 0.3s ease, transform 0.3s ease;
      position: relative;
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
  </style>
  <style>
    .product-container {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 20px;
      padding: 20px 4%;
      box-sizing: border-box;
    }
    .product-container > .card {
      width: 100%;
      min-width: 0;
      max-width: none;
      margin: 0;
      flex: none;
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
      <a href="../../../Kategoria/Php_files/kategoria.php?id=5">Książki</a>
    </div>
  </div>

  <div class="search-container header-item">
    <input type="text" id="searchInput" placeholder="Wyszukaj w sklepie">
    <button id="searchButton" class="search-btn"><i class="fas fa-search"></i></button>
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
    <a href="../../../Regestracja and login/Html_files/Login.html" class="konto header-item">Konto</a>
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
  <a href="../Php_files/Schowek.php" class="menu-button active">Schowek</a>
  <a href="../../Opinie/Php_files/Opinie.php" class="menu-button">Opinie</a>
  <a href="../../Faktura/Php_files/Faktura.php" class="menu-button">Faktura</a>
  <a href="../../Koszyk/Php_files/Koszyk.php" class="menu-button">Koszyk</a>
</div>

<div class="product-container"></div>

<footer  id="page-footer" class="footer">
  <div class="footer-container">
    <div class="footer-column">
      <h3>Jak mogę skontaktować?</h3>
      <p>Viber: +48725975501</a></p>
      <p>Telegram: +48725975501</p>
      <p>Instagram: dragon_woorld</p>
      <p>Email: reper774@gmail.com</p>
    </div>

    <div class="footer-column">
      <h3>Kategorie</h3>
      <p>Kategorie</p>
      <p>Kategorie</p>
      <p>Kategorie</p>
      <p>Kategorie</p>
      <p>Kategorie</p>
    </div>

    <div class="footer-column">
      <h3>Moje konto</h3>
      <p>Moje dane</p>
      <p>Zamówienia</p>
      <p>Schowek</p>
      <p>Opinie</p>
      <p>Faktura</p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>Dragon World 2025</p>
  </div>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const container = document.querySelector(".product-container");
  const searchInput = document.getElementById("searchInput");
  const searchButton = document.getElementById("searchButton");

  function truncateText(text, maxLength) {
    return text.length > maxLength ? text.slice(0, maxLength - 3) + "..." : text;
  }

  function updateJustifyContent() {
    const visibleCards = Array.from(container.querySelectorAll('.card')).filter(card => card.style.display !== 'none');
    if (visibleCards.length === 4) {
      container.classList.add('center');
    } else {
      container.classList.remove('center');
    }
  }

  function loadProducts(apiUrl, container) {
    fetch(apiUrl)
      .then(response => {
        if (!response.ok) throw new Error("Błąd ładowania danych");
        return response.json();
      })
      .then(products => {
        container.innerHTML = "";

        products.forEach(product => {
          const truncatedDescription = truncateText(product.description, 43);

          const card = document.createElement("div");
          card.classList.add("card");
          card.setAttribute("data-id", product.id);
          card.setAttribute("data-name", product.name.toLowerCase());

          card.innerHTML = `
            <a href="../../../StronaTowara/Php_files/StronaTowara.php?id=${product.id}" class="card-link">
              <img src="${product.image}" alt="${product.name}" />
              <div class="card-content">
                <p class="product-title">${product.name || ""}</p>
                <p class="product-description">${truncatedDescription}</p>
                <p class="product-price">Cena: ${product.price} zł</p>
                <button class="favorite-btn favorited" data-id="${product.id}" title="Usuń z ulubionych"><i class="fas fa-heart"></i></button>
              </div>
            </a>
          `;

          if (window.attachQuickView) {
            window.attachQuickView(card, product, `../../../StronaTowara/Php_files/StronaTowara.php?id=${product.id}`);
          }

          container.appendChild(card);
        });

        updateJustifyContent();
      })
      .catch(error => console.error("Błąd:", error));
  }

function filterProducts(query) {
  const cards = container.querySelectorAll(".card");
  if (query === "") {
    // Если поиск пустой — показываем все карточки
    cards.forEach(card => card.style.display = "flex");
  } else {
    cards.forEach(card => {
      const name = card.getAttribute("data-name");
      if (name.includes(query)) {
        card.style.display = "flex";
      } else {
        card.style.display = "none";
      }
    });
  }
  updateJustifyContent(); 
}

// Автоматически показываем все товары, если поиск пустой
searchInput.addEventListener("input", () => {
  if (searchInput.value.trim() === "") {
    filterProducts("");
  }
});

// Запуск поиска при нажатии кнопки или Enter
searchButton.addEventListener("click", () => {
  const query = searchInput.value.trim().toLowerCase();
  filterProducts(query);
});

searchInput.addEventListener("keydown", (e) => {
  if (e.key === "Enter") {
    e.preventDefault(); // чтобы не перезагружать форму, если она есть
    const query = searchInput.value.trim().toLowerCase();
    filterProducts(query);
  }
});


  container.addEventListener("click", function (event) {
    const target = event.target.closest(".favorite-btn");
    if (target) {
      event.preventDefault();

      const productId = target.getAttribute("data-id");

      fetch("favorite.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ product_id: productId, action: "remove" }),
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const card = target.closest(".card");
            if (card) card.remove();
            updateJustifyContent();
          } else {
            alert(data.error || "Błąd podczas usuwania");
          }
        })
        .catch(() => alert("Błąd sieci"));
    }
  });

  loadProducts("get_favorites.php", container);
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
