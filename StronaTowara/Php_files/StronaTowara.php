<?php
session_start();

// Получаем ID продукта и ID пользователя
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$customer_id = $_SESSION['user']['id'] ?? 0;



$source = $_GET['source'] ?? 'unknown';

// Подключение к базе через PDO (SQL Server)
$config = require __DIR__ . '/../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Получаем количество отзывов и среднюю оценку
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_reviews, AVG(Ilosc_gwiazdek) AS avg_rating
        FROM opinie
        WHERE id_produkta = :product_id AND Status = 'zatwierdzony'
    ");
    $stmt->execute(['product_id' => $product_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_reviews = $data['total_reviews'] ?? 0;
    $avg_rating = $data['avg_rating'] ? round($data['avg_rating'], 1) : 0;

} catch (PDOException $e) {
    die("Ошибка подключения к SQL Server: " . $e->getMessage());
}

// Функция для отображения звездочек
function displayStars($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars >= 0.5) ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;

    $html = str_repeat('<span class="full">★</span>', $fullStars);
    if ($halfStar) $html .= '<span class="half">★</span>';
    $html .= str_repeat('<span class="empty">★</span>', $emptyStars);

    return $html;
}

?>


<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../Css_files/styleStronaTowara.css?v=6">
  <script src="../../quick-view.js" defer></script>
  <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <title>Dragon World</title> 
</head>



<body>
<script src="../../runes-bg.js"></script>
  </header>

  <body>
  <header>
    <a href="../../Index/Php_files/Index.php" class="logo-link">
      <div class="logo header-item">
        <img src="../Zdjecia/12.png" alt="Логотип">
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
      $profileLink = $role === 'admin' ? '../../Admin/Php_files/AdminPanel.php' :
                     ($role === 'employee' ? '../../Pracownik/Php_files/pracownikpanel.php' :
                     '../../Konto/Moje Dane/Php_files/Moje dane.php');
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
  Koszyk<?php if ($total_items_in_cart > 0): ?><span class="cart-count" id="cart-count">(<?php echo (int) $total_items_in_cart; ?>)</span><?php endif; ?>
</a>


  </header>

  <main>
    
   <div class="product-page">

  <div class="product-container12">

    <!-- КАРТИНКА -->
    <div class="product-gallery">
      <img id="productImage" class="main-image" alt="product">

      <div class="thumbnails">
        <img src="../../FOTO/Figurka1 1.png" onclick="changeImage(this)">
        <img src="../../FOTO/Figurka1.PNG" onclick="changeImage(this)">
        <img src="../../FOTO/Figurka3.PNG" onclick="changeImage(this)">
      </div>
    </div>

    <!-- ИНФА -->
    <div class="product-info">
      <h1 id="productNameDisplay"></h1>

      <p id="productPrice" class="price"></p>

      <p id="productDescription" class="description"></p>

      <div class="buttons">
        <button class="btn main">DO KOSZYKA</button>
        <button class="btn secondary showReviewFormBtn">Dodaj opinię</button>
        <button class="btn secondary add-to-fav">Dodaj do ulubionych</button>
      </div>
    </div>

  </div>

</div>

  


<div id="productDetails">
    <div id="descriptionBlock" class="description-wrapper">
       <h3 class="opis-title">Opis produktu</h3>
        <div class="description-content">
            <p>
                Ten plakat dekoracyjny przedstawia fantastyczny świat smoków i mitycznych stworzeń. Doskonale nadaje się do ozdoby pokoju, biura lub pokoju dziecięcego. Wykonany z wysokiej jakości papieru, oferuje szczegółowe ilustracje i żywe kolory, które przyciągają wzrok.
            </p>
        </div>
        <div class="image-center-wrapper">
            <img src="../../FOTO/Figurka1 1.png" alt="Plakat 1">
            <img src="../../FOTO/Figurka1.PNG" alt="Plakat 2">
            <img src="../../FOTO/Figurka3.PNG" alt="Plakat 3">
        </div>
        <div class="expand-btn-wrapper">
            <button id="showDescriptionBtn">Pokaż pełny opis</button>
        </div>
    </div>

    <h3 class="product-title">Dane towaru</h3>
    <div class="product-specs-wrapper">
        <table class="product-specs">
            <tr><td>Rodzaj produktu:</td><td>Plakat dekoracyjny</td></tr>
            <tr><td>Motyw:</td><td>Smoki i mityczne stworzenia</td></tr>
            <tr><td>Materiał:</td><td>Papier wysokiej jakości</td></tr>
            <tr><td>Format:</td><td>A3 (29,7 × 42 cm)</td></tr>
            <tr><td>Kolorystyka:</td><td>Pełny kolor</td></tr>
            <tr><td>Wykończenie:</td><td>Matowe / błyszczące</td></tr>
            <tr><td>Przeznaczenie:</td><td>Dekoracja wnętrz</td></tr>
        </table>
    </div>
    <div class="expand-btn-wrapper">
        <button id="showDetailsBtn">Pokaż pełne dane</button>
    </div>
</div>




<div class="review-stats">
    <p>Średnia ocena: <span class="stars"><?php echo displayStars($avg_rating); ?></span> (<?php echo $avg_rating; ?>)</p>
    <p>Ilość opinii: <?php echo $total_reviews; ?></p>
    <!-- Кнопка для показа формы -->
    <button class="showReviewFormBtn add-review-btn">Dodaj opinię</button>

</div>

<div id="addReviewModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Dodaj opinię</h2>
    <form id="reviewForm" method="POST">
      <input type="hidden" name="id_products" value="<?php echo $product_id; ?>">
      <input type="hidden" name="id_klienta" value="<?php echo $customer_id; ?>">

      <label>Ilość gwiazdek:</label><br>
      <select name="ilosc_gwiazdek" required>
        <option value="5">★★★★★ (5)</option>
        <option value="4">★★★★☆ (4)</option>
        <option value="3">★★★☆☆ (3)</option>
        <option value="2">★★☆☆☆ (2)</option>
        <option value="1">★☆☆☆☆ (1)</option>
      </select><br><br>

      <label for="text">Twój komentarz:</label><br>
<textarea name="text" id="text" rows="4" cols="50" maxlength="500" required></textarea>
<div id="textCounter">0 / 500</div><br>



      <button type="submit">Dodaj opinię</button>
    </form>
  </div>
</div>

<h2>Opinie klientów</h2>
<div class="reviews-wrapper">
    <div class="reviews">
        <?php include '../../Konto/Opinie/Php_files/display_reviews.php'; ?>
    </div>
</div>


  


<div class="text12">Inne produkty w kategorii:</div>

<div class="product-container"></div>


<div class="product-container">
    <h2>Niedawno oglądane produkty</h2>
    <div class="card-container" id="recently-viewed"></div>
</div>




  
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
  <ul class="footer-links">
    <li><a href="../Kategoria/kategoria.php?id=1">Figurki smoków</a></li>
    <li><a href="../Kategoria/kategoria.php?id=2">Biżuteria</a></li>
    <li><a href="../Kategoria/kategoria.php?id=3">Plakaty</a></li>
    <li><a href="../Kategoria/kategoria.php?id=4">Odzież</a></li>
    <li><a href="../Kategoria/kategoria.php?id=5">Książki</a></li>
  </ul>
</div>


       <div class="footer-column">
  <h3>Konto</h3>
  <ul class="footer-links">
    <li><a href="../Konto/Moje dane/Moje dane.php">Moje dane</a></li>
    <li><a href="../Konto/Zamowienia/Zamowienia.php">Zamowienia</a></li>
    <li><a href="../Konto/Schowek/Schowek.php">Schowek</a></li>
    <li><a href="../Konto/Opinie/Php_files/Opinie.php">Opinie</a></li>
    <li><a href="../Konto/Faktura/Faktura.php">Faktura</a></li>
  </ul>
</div>
  </div>
  <div class="footer-bottom">
    <p>Dragon World 2025</p>
  </div>
</footer>
  </main>

<script>
function normalizeProductPhoto(photo) {
  if (!photo) return "";
  const path = String(photo).replace(/\\/g, "/").replace(/^\/+/, "");
  const fotoMatch = path.match(/FOTO\/(.+)$/i);
  if (fotoMatch) {
    return "../../FOTO/" + fotoMatch[1].replace(/^\/+/, "");
  }
  return path;
}

document.addEventListener("DOMContentLoaded", function () {

  // ======== 1) Раскрытие деталей и описания ========
  const toggleBlock = (btnId, blockSelector, showText, hideText) => {
    const btn = document.getElementById(btnId);
    const block = document.querySelector(blockSelector);
    if (!btn || !block) return;

    btn.addEventListener('click', () => {
      block.classList.toggle('expanded');
      btn.textContent = block.classList.contains('expanded') ? hideText : showText;
    });
  };
  toggleBlock('showDetailsBtn', '#productDetails', 'Pokaż pełne dane', 'Ukryj dane towaru');
  toggleBlock('showDescriptionBtn', '#descriptionBlock .description-content', 'Pokaż pełny opis', 'Ukryj pełny opis');

  // ======== 2) Смена главного изображения ========
  window.changeImage = function(el) {
    const mainImg = document.getElementById('productImage');
    if (!mainImg) return;

    mainImg.src = el.src;
    document.querySelectorAll('.thumbnail').forEach(img => img.classList.remove('active'));
    el.classList.add('active');
  };

  // ======== 3) Форма сохранения данных пользователя ========
  const saveButton = document.getElementById('saveButton');
  if (saveButton) {
    saveButton.addEventListener('click', function (event) {
      const firstName = document.getElementById('firstName').value.trim();
      const lastName = document.getElementById('lastName').value.trim();
      const phone = document.getElementById('phone').value.trim();

      const regex = /^[A-Za-zĄąĆćĘęŁłŃńÓóŚśŻżŹź]+$/;
      const phoneRegex = /^\+(38\d{10}|48\d{9})$/;

      let isValid = true;
      function showError(id, msg) {
        const el = document.getElementById(id);
        if(el) { el.textContent = msg; el.style.visibility = msg ? 'visible' : 'hidden'; }
      }

      if(!firstName){ showError('firstNameError',"Proszę wypełnić pole Imię."); isValid=false; }
      else if(!regex.test(firstName)){ showError('firstNameError',"Imię może zawierać tylko litery."); isValid=false; }
      else showError('firstNameError','');

      if(!lastName){ showError('lastNameError',"Proszę wypełnić pole Nazwisko."); isValid=false; }
      else if(!regex.test(lastName)){ showError('lastNameError',"Nazwisko może zawierać tylko litery."); isValid=false; }
      else showError('lastNameError','');

      if(!phone){ showError('phoneError',"Proszę wypełnić pole Numer telefonu."); isValid=false; }
      else if(!phoneRegex.test(phone)){ showError('phoneError',"Numer telefonu musi zaczynać się od '+38' i mieć 10 cyfr, lub '+48' и 9 cyfr."); isValid=false; }
      else showError('phoneError','');

      if(!isValid){ event.preventDefault(); return; }

      fetch('save_form.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({firstName,lastName,phone})
      })
      .then(res=>res.text())
      .then(data=>console.log("Odpowiedź serwera:",data))
      .catch(err=>console.error("Ошибка сохранения данных:",err));
    });
  }

  // ======== 4) Загрузка данных товара ========
  const urlParams = new URLSearchParams(window.location.search);
  const productId = urlParams.get('id') ? decodeURIComponent(urlParams.get('id')).replace(/"/g,'') : null;
  const source = urlParams.get('source') || "unknown";

  if (productId) {
  setTimeout(() => {
    fetch("../Php_files/register_view.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: new URLSearchParams({
        product_id: productId,
        source: source
      })
    })
    .then(res => res.json())
    .then(data => {
      console.log("View saved:", data);
    })
    .catch(err => console.error(err));
  }, 5000); // 20 секунд
}

  if(productId){
    const displayId = document.getElementById("productIdDisplay");
    if(displayId) displayId.textContent = `ID товара: ${productId}`;

    fetch(`../../Index/Php_files/getTowar.php?id=${productId}`)
      .then(res => res.ok ? res.json() : Promise.reject("Ошибка при запросе товара"))
      .then(data=>{
        if(!data) return;
        const setText=(id,text)=>{ const el=document.getElementById(id); if(el) el.textContent=text; };
        const setImg=(id,src,alt)=>{ const el=document.getElementById(id); if(el){ el.src=src; el.alt=alt; } };
        setText('productNameDisplay', data.name||'');
        setImg('productImage', normalizeProductPhoto(data.image||''), data.name||'');
        setText('productDescription', data.description||'');
        setText('productPrice', data.price ? `Cena: ${data.price} zł` : '');
      })
      .catch(err=>console.error(err));
  }

  // ======== 5) Загрузка похожих товаров с сердечками ========
  const productContainer = document.querySelector(".product-container");
  function renderProductCard(product){
    const card = document.createElement('div'); card.className='card';
    const truncate = (text,len)=>text.length>len ? text.slice(0,len)+'...' : text;
    card.innerHTML = `
      <a href="StronaTowara.php?id=${product.id}&source=${encodeURIComponent(source)}" class="card-link">
        <img src="${normalizeProductPhoto(product.image)}" alt="${product.name}">
        <div class="card-content">
          <p class="product-title">${product.name || ""}</p>
          <p class="product-description">${truncate(product.description||'',43)}</p>
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
        image: normalizeProductPhoto(product.image)
      }, `StronaTowara.php?id=${product.id}&source=${encodeURIComponent(source)}`);
    }
    return card;
  }

  if(productContainer && productId){
    fetch(`../../Index/Php_files/get_products_by_category.php?id=${productId}`)
      .then(res=>res.ok?res.json():Promise.reject("Ошибка загрузки категории"))
      .then(products=>{
        productContainer.innerHTML='';
        let row;
        products.forEach((product,i)=>{
          if(i%4===0){ row=document.createElement('div'); row.className='card-container'; productContainer.appendChild(row); }
          const card=renderProductCard(product);
          row.appendChild(card);
        });
        loadFavorites(productContainer); // сразу отмечаем любимые
      })
      .catch(err=>console.error(err));
  }

  // ======== 6) Recently Viewed ========
  const recentlyViewedContainer = document.getElementById("recently-viewed");
  let viewed = JSON.parse(localStorage.getItem("recentlyViewed")) || [];
  if(productId){
    viewed = viewed.filter(id=>id!==productId);
    viewed.unshift(productId);
    viewed = viewed.slice(0,4);
    localStorage.setItem("recentlyViewed", JSON.stringify(viewed));
  }
  if(recentlyViewedContainer){
    recentlyViewedContainer.innerHTML='';
    viewed.forEach(id=>{
      fetch(`../../Index/Php_files/getTowar.php?id=${id}`)
        .then(res=>res.json())
        .then(product=>{
          if(!product||!product.id) return;
          const card=renderProductCard(product);
          recentlyViewedContainer.prepend(card);
        }).catch(err=>console.error(err));
    });
    loadFavorites(recentlyViewedContainer);
  }

  // ======== Favorite click handler (универсальный) ========
document.addEventListener('click', function(e){
    const btn = e.target.closest('.favorite-btn, .add-to-fav');
    if(!btn) return;
    e.preventDefault();

    // Если кнопка карточки - берём id из data-id
    let pid = btn.dataset.id;

    // Если верхняя кнопка - берём id из URL
    if(!pid){
        pid = new URLSearchParams(window.location.search).get('id');
    }

    if(!pid) {
        alert("Не найден ID товара!");
        return;
    }

    const isFav = btn.classList.contains('active') || btn.classList.contains('favorited');
    const action = isFav ? 'remove' : 'add';

    fetch("../../Konto/Schowek/Php_files/favorite.php", {
        method:'POST',
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: new URLSearchParams({product_id: pid, action: action})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.redirect){ window.location.href = data.redirect; return; }
        if(data.success){
            const on = !(btn.classList.contains('favorited') || btn.classList.contains('active'));
            btn.classList.toggle('active', on);
            btn.classList.toggle('favorited', on);
            const icon = btn.querySelector('i');
            if (icon) {
              icon.classList.toggle('fas', on);
              icon.classList.toggle('far', !on);
            } else {
              btn.textContent = on ? "💖" : "❤️";
            }
        } else alert("Ошибка: " + (data.error || "unknown"));
    })
    .catch(err=>console.error(err));
});

  // ======== 8) Cart update ========
  function updateCartCount(){
    fetch("../../Konto/Koszyk/Php_files/cart_count2.php")
      .then(res=>res.json())
      .then(data=>{
        const link = document.querySelector("header .koszyk");
        if (!link) return;
        let el = document.getElementById("cart-count");
        if (data.success && data.count > 0) {
          if (!el) {
            el = document.createElement("span");
            el.className = "cart-count";
            el.id = "cart-count";
            link.appendChild(el);
          }
          el.textContent = `(${data.count})`;
        } else if (el) {
          el.remove();
        }
      }).catch(err=>console.error(err));
  }
  updateCartCount();
  setInterval(updateCartCount,5000);

  const mainBtn = document.querySelector(".btn.main");
  if(mainBtn){
    mainBtn.addEventListener('click', function(){
      const userId=<?php echo isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 'null'; ?>;
      if(!productId||!userId){ alert("Невозможно определить товар или пользователя"); return; }
      fetch("../Php_files/add_to_cart.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:new URLSearchParams({userId, productId})
      })
      .then(res=>res.json())
      .then(data=>{ if(data.success) updateCartCount(); else alert("Ошибка при добавлении товара"); })
      .catch(err=>console.error(err));
    });
  }

  // ======== 9) Search overlay ========
  const searchInput = document.getElementById("searchInput");
  const searchBtn = document.getElementById("searchButton");
  const searchOverlay = document.getElementById("search-overlay");
  const closeSearchBtn = document.getElementById("close-search");
  const resultsContainer = searchOverlay ? searchOverlay.querySelector(".search-results") : null;

  function renderSearchProducts(products){
    if(!resultsContainer) return;
    resultsContainer.innerHTML='';
    if(!products.length){ resultsContainer.innerHTML='<div style="font-size:24px;margin-left:20px">Brak rezultatów wyszukiwania.</div>'; return; }

    let row;
    products.forEach((p,i)=>{
      if(i%4===0){ row=document.createElement('div'); row.className='card-container'; resultsContainer.appendChild(row); }
      const card=document.createElement('div'); card.className='card';
      const truncate=(t,l)=>t.length>l?t.slice(0,l)+'...':t;
      card.innerHTML=`
        <a href="StronaTowara.php?id=${p.id}&source=search" class="card-link">
          <img src="${normalizeProductPhoto(p.image)}" alt="${p.name || ""}">
          <div class="card-content">
            <p class="product-title">${p.name || ""}</p>
            <p class="product-description">${truncate(p.description||'',43)}</p>
            <p class="product-price">Cena: ${p.price} zł</p>
            <button class="favorite-btn" data-id="${p.id}" title="Dodaj do ulubionych"><i class="far fa-heart"></i></button>
          </div>
        </a>
      `;
      if (window.attachQuickView) {
        window.attachQuickView(card, {
          id: p.id,
          name: p.name,
          price: p.price,
          description: p.description,
          image: normalizeProductPhoto(p.image)
        }, `StronaTowara.php?id=${p.id}&source=search`);
      }
      row.appendChild(card);
    });
  }

  function performSearch(){
    const q=searchInput.value.trim();
    if(!q) return;
    if(searchOverlay) searchOverlay.style.display='block';
    window.scrollTo(0,0);

    fetch("../../Index/Php_files/search_products.php?q="+encodeURIComponent(q))
      .then(res=>res.json())
      .then(products => {
        renderSearchProducts(products);
        if (resultsContainer) loadFavorites(resultsContainer);
      })
      .catch(()=>{ if(resultsContainer) resultsContainer.innerHTML="Błąd wyszukiwania"; });
  }

  if(searchBtn && searchInput) searchBtn.addEventListener('click',performSearch);
  if(searchInput) searchInput.addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); performSearch(); } });
  if(closeSearchBtn && searchOverlay) closeSearchBtn.addEventListener('click',()=>{ searchOverlay.style.display='none'; if(resultsContainer) resultsContainer.innerHTML=''; });

}); // DOMContentLoaded end

// ======== Load favorites function ========
function loadFavorites(container){
  fetch("../../Konto/Schowek/Php_files/get_favorites.php")
    .then(res=>res.json())
    .then(favs=>{
      favs.forEach(item=>{
        const btn=container.querySelector(`.favorite-btn[data-id="${item.id}"]`);
        if(btn){
          btn.classList.add("favorited");
          const icon = btn.querySelector("i");
          if (icon) {
            icon.classList.add("fas");
            icon.classList.remove("far");
          } else {
            btn.textContent = "💖";
          }
        }
      });
    })
    .catch(err=>console.error("Ошибка загрузки избранных:",err));
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addReviewModal');
    const closeBtn = modal.querySelector('.close');
    const form = document.getElementById('reviewForm');

    // Открытие модалки при клике на кнопки
    document.querySelectorAll('.showReviewFormBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            modal.style.display = 'block';
        });
    });

    // Закрытие модалки
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if(event.target === modal) modal.style.display = 'none';
    });

    // Отправка формы отзыва
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        fetch('../Konto/Opinie/Php_files/submit_review.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(data => {
            if(data.trim() === 'success'){
                fetch(`../Konto/Opinie/Php_files/display_reviews.php?id=${new URLSearchParams(window.location.search).get('id')}`)
                    .then(res => res.text())
                    .then(html => {
                        document.querySelector('.reviews').innerHTML = html;
                        form.reset();
                        modal.style.display = 'none';
                    });
            } else {
                alert(data);
            }
        })
        .catch(err => console.error(err));
    });

    // Пагинация отзывов
    document.addEventListener('click', function(e){
        if(e.target.classList.contains('page-link')){
            e.preventDefault();
            const page = e.target.dataset.page;
            const productId = new URLSearchParams(window.location.search).get('id');

            fetch(`../Konto/Opinie/Php_files/display_reviews.php?id=${productId}&page=${page}`)
                .then(res=>res.text())
                .then(html => {
                    document.querySelector('.reviews').innerHTML = html;
                    document.querySelector('.review-stats').scrollIntoView({behavior:'auto', block:'start'});
                });
        }
    });
});

// (removed duplicate view registration; handled powyżej przez `fetch("register_view.php", ...)`)

</script>

<script src="../../header-scroll.js"></script>
</body>
</html>
