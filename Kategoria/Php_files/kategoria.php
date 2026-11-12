<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link href="../Css_files/styleindex.css?v=12" rel="stylesheet">
  <script src="../../quick-view.js" defer></script>
  <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


</head>

<header>
  <a href="../../Index/Php_files/Index.php" class="logo-link">
    <div class="logo header-item">
      <img src="../../Index/Zdecia/12.png" alt="Логотип">
      <span><span class="red">Dragon</span> <span class="green">World</span></span>
    </div>
  </a>
  
  <div class="dropdown header-item">
    <button class="transparent-button">Kategorie</button>
    <div class="dropdown-content">
      <a href="../../Kategoria/Php_files/kategoria.php?id=1">Figurki smoków</a>
      <a href="../../Kategoria/Php_files/kategoria.php?id=2">Biżuteria</a>
      <a href="../../Kategoria/Php_files/kategoria.php?id=3">Plakaty</a>
      <a href="../../Kategoria/Php_files/kategoria.php?id=4">Odzież</a>
      <a href="../../Kategoria/Php_files/kategoria.php?id=5">Książki</a>
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
                     ($role === 'employee' ? '../../Pracownik/pracownikpanel.php' :
                     '../../Konto/Moje dane/Php_files/Moje dane.php');
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
        require_once dirname(__DIR__, 2) . '/secure/profile_menu.php';
        dw_profile_menu('../../Konto/Moje dane/Php_files');
      ?>
    </div>
    </div>
  <?php else: ?>
    <a href="../../Regestracja and login/Html_files/Login.html" class="konto header-item">Konto</a>
  <?php endif; ?>
  
  <a href="../../Konto/Schowek/Php_files/Schowek.php" class="serdce header-item"><i class="fas fa-heart" style="color: #8D242E;"></i></a>

  <?php include "../../Konto/Koszyk/Php_files/cart_count.php"; ?>

  <a href="../../Konto/Koszyk/Php_files/Koszyk.php" class="koszyk header-item">
    Koszyk
    <?php if ($total_items_in_cart > 0): ?>
      <span class="cart-count">(<?php echo $total_items_in_cart; ?>)</span>
    <?php endif; ?>
  </a>
</header>

<body>
<script src="../../runes-bg.js"></script>

    <div class="hero-image">
  <h1 id="category-title"></h1>
  <div class="category-sort" id="category-sort">
    <label class="sort-field">
      <span>Cena</span>
      <select id="sort-price" aria-label="Sortowanie według ceny">
        <option value="">—</option>
        <option value="asc">od najniższej</option>
        <option value="desc">od najwyższej</option>
      </select>
    </label>
    <label class="sort-field">
      <span>Popularność</span>
      <select id="sort-popularity" aria-label="Sortowanie według popularności">
        <option value="">—</option>
        <option value="desc">najpopularniejsze</option>
        <option value="asc">najmniej popularne</option>
      </select>
    </label>
    <label class="sort-field">
      <span>Oceny</span>
      <select id="sort-rating" aria-label="Sortowanie według ocen">
        <option value="">—</option>
        <option value="desc">najwyższe</option>
        <option value="asc">najniższe</option>
      </select>
    </label>
    <div class="sort-field sort-price-range">
      <span>od</span>
      <input type="number" id="price-from" min="0" step="0.01" aria-label="Cena od">
      <span>do</span>
      <input type="number" id="price-to" min="0" step="0.01" aria-label="Cena do">
      <span>zł</span>
    </div>
    <button type="button" id="sort-reset" class="sort-reset">Wyczyść</button>
  </div>
    </div>

  <div class="main-content">
    <div class="product-container">…</div>
  </div>

  <footer class="footer">
  <div class="footer-container">
    <div class="footer-column">
  <h3>Jak mogę skontaktować?</h3>
  <div class="social-icons">
    <a href="viber://chat?number=%2B48725975501" target="_blank">
      <img src="../../FOTO/Viber_icon.png" alt="Viber" width="64" height="64">
    </a>
    <a href="https://t.me/+48725975501" target="_blank">
      <img src="../../FOTO/telegram.png" alt="Telegram" width="64" height="64">
    </a>
    <a href="https://instagram.com/dragon_woorld" target="_blank">
      <img src="../../FOTO/instagram.png" alt="Instagram" width="64" height="64">
    </a>
    <a href="mailto:reper774@gmail.com">
      <img src="../../FOTO/gmail.png" alt="Email" width="64" height="64">
    </a>
  </div>
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
  const footer = document.getElementById("page-footer");
  const searchInput = document.getElementById("searchInput");
  const searchButton = document.getElementById("searchButton");
  const categoryId = new URLSearchParams(window.location.search).get("id");

  const categoryNames = {
  1: "Figurki smoków",
  2: "Biżuteria",
  3: "Plakaty",
  4: "Odzież",
  5: "Książki"
};

const categoryTitle = document.getElementById("category-title");

if (categoryTitle && categoryId) {
  categoryTitle.textContent = categoryNames[categoryId] || "Kategoria";
}

  if (!container) return console.error("Контейнер .product-container не найден!");
  if (!categoryId) {
    container.innerHTML = "<p>Nie wybrano kategorii.</p>";
    return;
  }

  let allProducts = [];
  let sortKeys = [];

  const sortFields = {
    price: document.getElementById("sort-price"),
    popularity: document.getElementById("sort-popularity"),
    rating: document.getElementById("sort-rating")
  };
  const priceFrom = document.getElementById("price-from");
  const priceTo = document.getElementById("price-to");

  function applySort(list) {
    if (!sortKeys.length) return list.slice();
    return list.slice().sort((a, b) => {
      for (const { key, dir } of sortKeys) {
        const diff = (Number(a[key]) - Number(b[key])) * (dir === "asc" ? 1 : -1);
        if (diff !== 0) return diff;
      }
      return 0;
    });
  }

  function currentList() {
    const query = searchInput.value.toLowerCase().trim();
    const from = priceFrom && priceFrom.value !== "" ? Number(priceFrom.value) : null;
    const to = priceTo && priceTo.value !== "" ? Number(priceTo.value) : null;
    const base = allProducts.filter(p => {
      const matchesQuery = !query ||
        (p.name && p.name.toLowerCase().includes(query)) ||
        (p.description && p.description.toLowerCase().includes(query));
      if (!matchesQuery) return false;
      const price = Number(p.price);
      if (from !== null && !Number.isNaN(from) && price < from) return false;
      if (to !== null && !Number.isNaN(to) && price > to) return false;
      return true;
    });
    return applySort(base);
  }

  function showProducts() {
    const query = searchInput.value.toLowerCase().trim();
    renderProducts(currentList());
    loadFavorites();
    if (footer) footer.style.display = query ? "none" : "block";
  }

  // Функция обрезки текста
  function truncateText(text, maxLength) {
    return text.length > maxLength ? text.slice(0, maxLength) + "..." : text;
  }

  // Функция рендера карточек
  function renderProducts(products) {
    container.innerHTML = "";

    if (!products || products.length === 0) {
      container.innerHTML = "<p>Brak produktów w tej kategorii.</p>";
      if (footer) footer.style.display = "block";
      return;
    }

    let row;
    products.forEach((product, index) => {
      if (index % 4 === 0) {
        row = document.createElement("div");
        row.classList.add("card-container");
        const remaining = products.length - index;
        if (remaining <= 2) row.classList.add("few");
        container.appendChild(row);
      }

      const card = document.createElement("div");
      card.classList.add("card");

      const shortName = product.name || "";
      const shortDescription = truncateText(product.description || '', 43);

     card.innerHTML = `
                  <a href="../../StronaTowara/Php_files/StronaTowara.php?id=${product.id}&source=category&cat=${encodeURIComponent(categoryId)}" class="card-link">
                    <img src="${product.image}" alt="${product.name}">
                    <div class="card-content">
                      <p class="product-title">${shortName}</p>
                      <p class="product-description">${shortDescription}</p>
                      <p class="product-price">Cena: ${product.price} zł</p>
                      <button class="favorite-btn" data-id="${product.id}" title="Dodaj do ulubionych"><i class="far fa-heart"></i></button>
                    </div>
                  </a>
                `;

      if (window.attachQuickView) {
        window.attachQuickView(card, {
          id: product.id,
          name: product.name,
          price: product.price,
          description: product.description,
          image: product.image
        }, `../../StronaTowara/Php_files/StronaTowara.php?id=${product.id}&source=category&cat=${encodeURIComponent(categoryId)}`);
      }

      row.appendChild(card);
    });

    if (footer) footer.style.display = "block";
  }

  // Загрузка товаров
  function loadProducts() {
    fetch(`category.php?id=${encodeURIComponent(categoryId)}`)
      .then(res => {
        if (!res.ok) throw new Error("Ошибка загрузки данных");
        return res.json();
      })
      .then(products => {
        allProducts = products;
        showProducts();
      })
      .catch(error => {
        console.error("Ошибка загрузки товаров:", error);
        container.innerHTML = `<p>Wystąpił błąd: ${error.message}</p>`;
      });
  }

  function setHeartFavorited(btn, on) {
    btn.classList.toggle("favorited", on);
    const icon = btn.querySelector("i");
    if (!icon) return;
    icon.classList.toggle("fas", on);
    icon.classList.toggle("far", !on);
  }

  // Загрузка избранных
  function loadFavorites() {
    fetch("../../Konto/Schowek/Php_files/get_favorites.php")
      .then(res => res.json())
      .then(favorites => {
        favorites.forEach(item => {
          const btn = container.querySelector(`.favorite-btn[data-id="${item.id}"]`);
          if (btn) setHeartFavorited(btn, true);
        });
      })
      .catch(err => console.error("Ошибка загрузки избранных:", err));
  }

  // Обработчик поиска
  function handleSearch() {
    showProducts();
    if (searchInput.value.trim() !== "") {
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  }

  const resetSort = document.getElementById("sort-reset");
  if (resetSort) {
    resetSort.addEventListener("click", function () {
      sortKeys = [];
      Object.values(sortFields).forEach(select => {
        if (select) select.value = "";
      });
      if (priceFrom) priceFrom.value = "";
      if (priceTo) priceTo.value = "";
      showProducts();
    });
  }

  [priceFrom, priceTo].forEach(input => {
    if (!input) return;
    input.addEventListener("input", showProducts);
  });

  Object.entries(sortFields).forEach(([key, select]) => {
    if (!select) return;
    select.addEventListener("change", function () {
      sortKeys = sortKeys.filter(item => item.key !== key);
      if (select.value) sortKeys.unshift({ key: key, dir: select.value });
      showProducts();
    });
  });

  // Обработчик клика по сердечкам
  container.addEventListener("click", function (event) {
    const target = event.target.closest(".favorite-btn");
    if (!target) return;

    event.preventDefault();
    const productId = target.dataset.id;
    const action = target.classList.contains("favorited") ? "remove" : "add";

    fetch("../../Konto/Schowek/Php_files/favorite.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ product_id: productId, action: action }),
    })
      .then(res => res.json())
      .then(data => {
        if (data.redirect) {
          window.location.href = data.redirect;
          return;
        }
        if (data.success) {
          setHeartFavorited(target, !target.classList.contains("favorited"));
        } else {
          alert(data.error || "Ошибка при изменении избранного");
        }
      })
      .catch(() => alert("Ошибка сети"));
  });

  // События поиска
  searchButton.addEventListener("click", handleSearch);
  searchInput.addEventListener("keyup", function (e) {
    if (searchInput.value.trim() === "") {
      showProducts();
    }
    if (e.key === "Enter") handleSearch();
  });

  // Инициализация
  loadProducts();
});


</script>
<script src="../../header-scroll.js"></script>
</body>
</html>
