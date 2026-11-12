<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link href="../css/bootstrap.min.css" rel="stylesheet">
  <link href="../../Index/Css_files/styleindex.css?v=45" rel="stylesheet">
  <script src="../../quick-view.js" defer></script>
  <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <title>Dragon World</title>
</head>

<body>
<script src="../../runes-bg.js"></script>
  <header>
    <a href="Index.php" class="logo-link">
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
      <input type="text" id="searchInput" class="form-control" placeholder="Wyszukaj w sklepie">
      <button id="searchButton" class="search-btn btn" type="button"><i class="fas fa-search"></i></button>
      <div id="search-dropdown" class="dropdown-content"></div>
    </div>
    
    <?php if (isset($_SESSION['user'])): ?>
  <div class="user-info header-item">
  <div class="profile-drop">
<a href="../../Konto/Moje dane/Php_files/Moje dane.php" class="user-link">
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

require_once dirname(__DIR__, 2) . '/secure/grywalizacja.php';
$postep = dw_postep(dw_pdo(), (int) $_SESSION['user']['id']);
$_SESSION['klasa'] = $postep['klasa'];

echo '<span class="user-info-inline">';
echo '<span class="user-name">' . htmlspecialchars($display_name) . '</span>';
if ($postep['klasa'] !== '') {
    echo '<span class="user-title">' . htmlspecialchars($postep['klasa']) . '</span>';
}
echo '</span>';
require_once dirname(__DIR__, 2) . '/secure/profile_menu.php';
dw_level_sketch();
?></a>
<?php
dw_profile_menu('../../Konto/Moje dane/Php_files', '', false);
?>
  </div>

  </div>
<?php else: ?>
  <a href="../../Regestracja and login/Html_files/Login.html" class="konto header-item">Konto</a>
<?php endif; ?>


    <a href="../../Konto/Schowek/Php_files/Schowek.php" class="serdce header-item"><i class="fas fa-heart"></i></a>

    <?php include "../../Konto/Koszyk/Php_files/cart_count.php"; ?>

<a href="../../Konto/Koszyk/Php_files/Koszyk.php" class="koszyk header-item">
  Koszyk
  <?php if ($total_items_in_cart > 0): ?>
    <span class="cart-count">(<?php echo $total_items_in_cart; ?>)</span>
  <?php endif; ?>
</a>
  </header>

  <main>
    <div class="hero-image">
      <div class="hero-text ppp">
        <p>Dragon World to nie tylko sklep, to miejsce, w którym </p>
        <p>możesz doświadczyć i poznać tajemniczą rasę żywych istot.</p>
        <p>Kup figurkę smoka, ubrania ze smoczymi nadrukami lub prostą, ale </p>
        <p> zabawną książkę o smokach, znajdziesz tutaj wszystko.</p>
        <p>Smoki na zawsze pozostaną w naszych sercach.</p>
      </div>
    </div>

<div class="background_dla_slajdera">
 <div class="slider">
  <div class="slides" id="slides-container">
    <!-- Картинки подгрузятся сюда -->
  </div>
</div>
</div>

<div class="product-container-new"></div>


</div>


<a id="map-button" href="../../Mapa/mapa.php" title="Mapa świata">
  <i class="fas fa-map"></i>
</a>
<div id="chat-button">
  <i class="fas fa-comments"></i>
</div>

<!-- ОКНО ЧАТА -->
<div id="chat-window">

  <!-- HEADER -->
  <div class="chat-header">
    <span>AI Chat</span>
    <button id="close-chat">&times;</button>
  </div>

  <!-- TOP BAR -->
  <div class="chat-top-bar">

    <button id="recommended-btn">
      🔥 Rekomendacje
    </button>

    <button id="back-chat-btn">
      💬 Chat
    </button>

    <button id="history-chat-btn">
      Rozmowy
    </button>

  </div>

  <!-- CHAT BODY -->
  <div class="chat-body">

    <!-- ЧАТ -->
    <div id="chat-view">

      <div id="chat-body">
        <div class="message bot">
          Cześć! Jak mogę pomóc?
        </div>
      </div>

    </div>

    <!-- РЕКОМЕНДАЦИИ -->
    <div id="recommendations-view">

      <div id="recommendations-body"></div>

    </div>

    <!-- ОТДЕЛЬНЫЕ РАЗГОВОРЫ -->
    <div id="conversations-view">
      <div id="conversations-body"></div>
    </div>

  </div>

  <!-- FOOTER -->
  <div class="chat-footer">

    <button id="new-chat-btn">
      Nowy czat
    </button>

    <input
      type="text"
      id="chat-input"
      placeholder="Napisz wiadomość..."
    >

    <button id="send-btn">
      Wyślij
    </button>

  </div>

</div>



<div class="product-container"></div>

<footer class="footer">
  <div class="footer-container">
    <div class="row g-4">
    <div class="col-12 col-md-4 footer-column">
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


   <div class="col-12 col-md-4 footer-column">
  <h3>Kategorie</h3>
  <ul class="footer-links">
    <li><a href="../../Kategoria/kategoria.php?id=1">Figurki smoków</a></li>
    <li><a href="../../Kategoria/kategoria.php?id=2">Biżuteria</a></li>
    <li><a href="../../Kategoria/kategoria.php?id=3">Plakaty</a></li>
    <li><a href="../../Kategoria/kategoria.php?id=4">Odzież</a></li>
    <li><a href="../../Kategoria/kategoria.php?id=5">Książki</a></li>
  </ul>
</div>


       <div class="col-12 col-md-4 footer-column">
  <h3>Kategorie</h3>
  <ul class="footer-links">
    <li><a href="../../Konto/Moje dane/Moje dane.php">Moje dane</a></li>
    <li><a href="../../Konto/Zamowienia/Zamowienia.php">Zamowienia</a></li>
    <li><a href="../../Konto/Schowek/Schowek.php">Schowek</a></li>
    <li><a href="../../Konto/Opinie/Opinie.php">Opinie</a></li>
    <li><a href="../../Konto/Faktura/Faktura.php">Faktura</a></li>
  </ul>
</div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>Dragon World 2025</p>
  </div>
</footer>
  </main>

  <script>

    

    document.addEventListener("DOMContentLoaded", function() {
      const saveButton = document.getElementById('saveButton');
      if (saveButton) {
          saveButton.addEventListener('click', function(event) {
              const firstName = document.getElementById('firstName').value;
              const lastName = document.getElementById('lastName').value;
              const phone = document.getElementById('phone').value;
  
              const regex = /^[A-Za-zĄąĆćĘęŁłŃńÓóŚśŻżŹź]+$/;
              // Регулярное выражение для проверки номера телефона
              const phoneRegex = /^\+(38\d{10}|48\d{9})$/;  // Если номер начинается с +38, то 10 цифр, если с +48, то 9 цифр
  
              let isValid = true;
  
              // Валидация имени
              if (!firstName) {
                  document.getElementById('firstNameError').textContent = "Proszę wypełnić pole Imię.";
                  document.getElementById('firstNameError').style.visibility = 'visible';
                  isValid = false;
              } else if (!regex.test(firstName)) {
                  document.getElementById('firstNameError').textContent = "Imię może zawierać tylko litery.";
                  document.getElementById('firstNameError').style.visibility = 'visible';
                  isValid = false;
              } else {
                  document.getElementById('firstNameError').style.visibility = 'hidden';
              }
  
              // Валидация фамилии
              if (!lastName) {
                  document.getElementById('lastNameError').textContent = "Proszę wypełnić pole Nazwisko.";
                  document.getElementById('lastNameError').style.visibility = 'visible';
                  isValid = false;
              } else if (!regex.test(lastName)) {
                  document.getElementById('lastNameError').textContent = "Nazwisko może zawierać tylko litery.";
                  document.getElementById('lastNameError').style.visibility = 'visible';
                  isValid = false;
              } else {
                  document.getElementById('lastNameError').style.visibility = 'hidden';
              }
  
              // Валидация номера телефона
              if (!phone) {
                  document.getElementById('phoneError').textContent = "Proszę wypełnić pole Numer telefonu.";
                  document.getElementById('phoneError').style.visibility = 'visible';
                  isValid = false;
              } else if (!phoneRegex.test(phone)) {
                  document.getElementById('phoneError').textContent = "Numer telefonu musi zaczynać się od '+38' i mieć 10 cyfr, lub zaczynać się od '+48' i mieć 9 cyfr.";
                  document.getElementById('phoneError').style.visibility = 'visible';
                  isValid = false;
              } else {
                  document.getElementById('phoneError').style.visibility = 'hidden';
              }
  
              if (!isValid) {
                  event.preventDefault(); // Останавливаем отправку формы, если есть ошибки
              } else {
                  // Отправляем данные на сервер
                  fetch('save_form.php', {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/x-www-form-urlencoded',
                      },
                      body: new URLSearchParams({
                          firstName: firstName,
                          lastName: lastName,
                          phone: phone,
                      }),
                  })
                  .then(response => response.text())
                  .then(data => {
                  console.log("Odpowiedź serwera:", data); // Просто логируем, но не показываем alert
                  })
                  .catch(error => console.error("Ошибка сохранения данных:", error));
              }
          });
      }
  });
  
  
document.addEventListener("DOMContentLoaded", function () {
  const slides = document.querySelector(".slides");

  fetch('get_latest_products.php')
  .then(res => res.json())
  .then(products => {
    products.reverse().forEach(product => {
      const slide = document.createElement("div");
      slide.classList.add("slide");
      const image = normalizeProductPhoto(product.image);
      slide.innerHTML = `
        <a href="../../StronaTowara/Php_files/StronaTowara.php?id=${product.id}&source=slider">
          <img src="${image}" alt="${product.name || "Towar"}">
        </a>`;
      if (window.attachQuickView) {
        window.attachQuickView(slide, {
          id: product.id,
          name: product.name,
          price: product.price,
          description: product.description,
          image: image
        }, `../../StronaTowara/Php_files/StronaTowara.php?id=${product.id}&source=slider`);
      }
      slides.appendChild(slide);
    });
    initSlider();
  })
  .catch(err => console.error('Ошибка загрузки товаров:', err));


  function initSlider() {
    const slide = document.querySelector(".slide");
    const slideStyle = getComputedStyle(slide);
    const slideWidth = slide.offsetWidth + parseInt(slideStyle.marginLeft) + parseInt(slideStyle.marginRight);

    let index = 0;
    const allSlides = document.querySelectorAll(".slide");
    const totalSlides = allSlides.length;

    // Клонируем слайды для бесшовного эффекта
    for (let i = 0; i < totalSlides; i++) {
      const clone = allSlides[i].cloneNode(true);
      slides.appendChild(clone);
    }

    slides.style.display = 'flex';

    function nextSlide() {
      index++;
      slides.style.transition = "transform 0.5s ease-in-out";
      slides.style.transform = `translateX(${-index * slideWidth}px)`;
    }

    slides.addEventListener('transitionend', () => {
      // Когда достигли конца оригинальных слайдов — быстро прыгаем назад
      if (index >= totalSlides) {
        slides.style.transition = "none";
        index = 0;
        slides.style.transform = `translateX(0px)`;
      }
    });

    setInterval(nextSlide, 2500);
  }
});



document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  const searchButton = document.getElementById("searchButton");
  const productContainer = document.querySelector(".product-container");

  const elementsToToggle = [
    document.querySelector(".hero-image"),
    document.querySelector(".background_dla_slajdera"),
    document.querySelector(".container"),
    document.querySelector("footer"),
  ];

  function truncateText(text, maxLength) {
    if (text.length > maxLength) {
      return text.slice(0, maxLength) + '...';
    }
    return text;
  }

 function loadProducts(apiUrl, container) {
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) throw new Error("Ошибка загрузки данных");
            return response.json();
        })
        .then(products => {
            container.style.display = "block";
            container.style.marginTop = "0";
            container.innerHTML = "";

            // Создаем один общий контейнер для всех карточек
            const row = document.createElement("div");
            row.className = "row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4 card-container";
            container.appendChild(row);

            products.forEach(product => {
                const col = document.createElement("div");
                col.className = "col";
                const card = document.createElement("div");
                card.classList.add("card");

                const shortName = product.name || "";
                const shortDescription = truncateText(product.description, 43);
                const image = normalizeProductPhoto(product.image);

                card.innerHTML = `
                    <a href="../../StronaTowara/Php_files/StronaTowara.php?id=${product.id}&source=home" class="card-link">
                        <img src="${image}" alt="${product.name}">
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
                    image: image
                  }, `../../StronaTowara/Php_files/StronaTowara.php?id=${product.id}&source=home`);
                }

                // Обработчик кнопки "избранное"
                const favoriteBtn = card.querySelector(".favorite-btn");
                favoriteBtn.addEventListener("click", (e) => {
                    e.preventDefault();
                    favoriteBtn.classList.toggle("active");
                });

                row.appendChild(col);
                col.appendChild(card);
            });
        })
        .catch(error => {
            console.error("Ошибка загрузки товаров:", error);
            container.innerHTML = "<p>Не удалось загрузить товары.</p>";
        });
}

  function resetToDefaultView() {
    console.log("Сброс к начальному виду, загружаем все товары");
    elementsToToggle.forEach(el => {
      if (el) el.style.display = "";
    });

    productContainer.style.display = "block";
    productContainer.style.marginTop = "0";
    productContainer.innerHTML = "";

    loadProducts("get_products.php", productContainer);
  }

  function performSearch() {
    const query = searchInput.value.trim();
    window.scrollTo(0, 0);

    if (!query) {
      resetToDefaultView();
      return;
    }

    elementsToToggle.forEach(el => {
      if (el) el.style.display = "none";
    });

    fetch("search_products.php?q=" + encodeURIComponent(query))
      .then(response => response.json())
      .then(products => {
        productContainer.style.display = "block";
        productContainer.style.marginTop = "300px";
        productContainer.innerHTML = "";

        if (!products || products.length === 0) {
          productContainer.innerHTML = `
            <div class="search-empty">
              <p>Brak rezultatów wyszukiwania dla frazy „${query}”. Kilka porad, które mogą pomóc:</p>
              <ul>
                <li>Upewnij się, że w wpisanej frazie nie ma błędów ortograficznych.</li>
                <li>Wypróbuj inne słowa kluczowe, np. synonimy lub bardziej ogólne określenia.</li>
                <li>Skorzystaj z menu na górze strony i przejdź do zakładki „Kategorie”, aby znaleźć interesujące Cię artykuły.</li>
              </ul>
            </div>
          `;
          return;
        }

        const row = document.createElement("div");
        row.className = "row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4 card-container";
        productContainer.appendChild(row);

        products.forEach((product) => {
          const col = document.createElement("div");
          col.className = "col";
          const card = document.createElement("div");
          card.classList.add("card");

          const shortName = product.name || "";
          const shortDescription = truncateText(product.description, 43);
          const image = normalizeProductPhoto(product.image);

          card.innerHTML = `
            <a href="../../StronaTowara/Php_files/StronaTowara.php?id=${product.id}&source=search" class="card-link">
              <img src="${image}" alt="${product.name}">
              <div class="card-content">
                <p class="product-title">${shortName}</p>
                <p class="product-description">${shortDescription}</p>
                <p class="product-price">Cena: ${product.price} zł</p>
              </div>
              <button class="favorite-btn" data-id="${product.id}" title="Dodaj do ulubionych"><i class="far fa-heart"></i></button>
            </a>
          `;

          if (window.attachQuickView) {
            window.attachQuickView(card, {
              id: product.id,
              name: product.name,
              price: product.price,
              description: product.description,
              image: image
            }, `../../StronaTowara/Php_files/StronaTowara.php?id=${product.id}&source=search`);
          }

          row.appendChild(col);
          col.appendChild(card);
        });
      })
      .catch(error => {
        console.error("Ошибка поиска:", error);
        resetToDefaultView();
      });
  }

  searchButton.addEventListener("click", performSearch);

  searchInput.addEventListener("input", function () {
    if (searchInput.value.trim() === "") {
      resetToDefaultView();
    }
  });

  searchInput.addEventListener("keydown", function(event) {
    if (event.key === "Enter") {
      event.preventDefault();
      performSearch();
    }
  });

  // При загрузке страницы сразу показываем все товары
  loadProducts("get_products.php", productContainer);
});

  document.addEventListener("DOMContentLoaded", function () {
    const container = document.querySelector(".product-container");

    function setHeartFavorited(btn, on) {
      btn.classList.toggle("favorited", on);
      const icon = btn.querySelector("i");
      if (!icon) return;
      icon.classList.toggle("fas", on);
      icon.classList.toggle("far", !on);
    }

    // Загрузить избранные с сервера
  fetch("../../Konto/Schowek/Php_files/get_favorites.php")
    .then(response => response.json())
    .then(favorites => {
      console.log("Избранные товары:", favorites);
      favorites.forEach(item => {
        const btn = container.querySelector(`.favorite-btn[data-id="${item.id}"]`);
        if (btn) {
          setHeartFavorited(btn, true);
        }
      });
    })
    .catch(err => console.error("Ошибка загрузки избранных:", err));


    container.addEventListener("click", function (event) {
      const target = event.target.closest(".favorite-btn");
      if (target) {
        event.preventDefault();

        const productId = target.getAttribute("data-id");
        const isFavorited = target.classList.contains("favorited");
        const action = isFavorited ? "remove" : "add";

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

      }
    });
  });


const chatBtn = document.getElementById("chat-button");
const chatWindow = document.getElementById("chat-window");
const closeChat = document.getElementById("close-chat");
const sendBtn = document.getElementById("send-btn");
const input = document.getElementById("chat-input");
const chatBody = document.getElementById("chat-body");


function normalizeProductPhoto(photo) {
  if (!photo) return "";

  const path = String(photo).replace(/\\/g, "/").replace(/^\/+/, "");
  const fotoMatch = path.match(/FOTO\/(.+)$/i);

  if (fotoMatch) {
    return "../../FOTO/" + fotoMatch[1].replace(/^\/+/, "");
  }

  return path;
}


chatBtn.onclick = () => {
  chatWindow.classList.add("active");
  loadChatHistory();
};
closeChat.onclick = () => chatWindow.classList.remove("active");

function addMessage(text, type) {

  const wrapper = document.createElement("div");
  wrapper.className = "message " + type;

  wrapper.innerHTML = text;

  chatBody.appendChild(wrapper);

  chatBody.scrollTop = chatBody.scrollHeight;
}

function addTyping() {
  const typing = document.createElement("div");
  typing.className = "message bot typing";
  typing.innerHTML = `<span></span><span></span><span></span>`;
  chatBody.appendChild(typing);
  chatBody.scrollTop = chatBody.scrollHeight;
  return typing;
}

async function getAIResponse(prompt) {
  try {
    const response = await fetch("chat.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message: prompt })
    });

    const raw = await response.text();
    console.log("Ответ сервера:", raw);
    return JSON.parse(raw);
  } catch (err) {
    console.error("Ошибка при получении ответа AI:", err);
    return { products: [], ai_reply: "Ошибка сервера или некорректный JSON" };
  }
}

async function sendMessage() {
  const text = input.value.trim();
  if (!text) return;

  addMessage(text, "user");
  input.value = "";

  const typing = addTyping();
  const data = await getAIResponse(text);
  typing.remove();

 addMessage(data.reply, "bot");

  if (data.products && data.products.length > 0) {
    data.products.forEach(p => {
      const card = document.createElement("div");
      card.className = "product-card";
      const photo = normalizeProductPhoto(p.photo);
card.innerHTML = `
  <a href="../../StronaTowara/Php_files/StronaTowara.php?id=${p.id}&source=chat" class="chat-product-link">
    <strong>${p.name ?? "Brak nazwy"}</strong> – ${p.price ?? "0"} zł<br>
    ${p.description ?? ""}<br>
    ${photo ? `<img src="${photo}">` : ""}
  </a>
`;
      if (window.attachQuickView) {
        window.attachQuickView(card, {
          id: p.id,
          name: p.name,
          price: p.price,
          description: p.description,
          image: photo
        }, `../../StronaTowara/Php_files/StronaTowara.php?id=${p.id}&source=chat`);
      }
      chatBody.appendChild(card);
    });
    chatBody.scrollTop = chatBody.scrollHeight;
  }
}

sendBtn.onclick = sendMessage;
input.addEventListener("keypress", e => {
  if (e.key === "Enter") sendMessage();
});

async function loadChatHistory() {
  try {
    const res = await fetch("get_chat_history.php");
    const messages = await res.json();

    chatBody.innerHTML = "";

    if (!messages.length) {
      addMessage("Cześć! Jak mogę pomóc?", "bot");
      return;
    }

    messages.forEach(msg => {
      const type = msg.Nadawca === "AI" ? "bot" : "user";
      addMessage(msg.Tekst, type);

      // 🖼️ PRODUCTS (NOWE)
      if (msg.Produkty) {
  const products = JSON.parse(msg.Produkty);

  products.forEach(p => {
    const card = document.createElement("div");
    card.className = "product-card";
    const photo = normalizeProductPhoto(p.photo);

   card.innerHTML = `
  <a href="../../StronaTowara/Php_files/StronaTowara.php?id=${p.id}&source=chat" class="chat-product-link">
    <strong>${p.name}</strong> – ${p.price} zł<br>
    ${p.description}<br>
    ${photo ? `<img src="${photo}">` : ""}
  </a>
`;

    if (window.attachQuickView) {
      window.attachQuickView(card, {
        id: p.id,
        name: p.name,
        price: p.price,
        description: p.description,
        image: photo
      }, `../../StronaTowara/Php_files/StronaTowara.php?id=${p.id}&source=chat`);
    }
    chatBody.appendChild(card);
  });
}
    });
  } catch (err) {
     console.error("Ошибка загрузки истории:", err);
     chatBody.innerHTML = "<p>Nie można załadować historii czatu.</p>";

}
  }

  function showChatView() {
    recommendationsView.style.display = "none";
    conversationsView.style.display = "none";
    chatView.style.display = "block";
  }

  document.getElementById("new-chat-btn").onclick = async () => {
  await fetch("new_chat.php");
  showChatView();
  chatBody.innerHTML = "";
  addMessage("Cześć! Jak mogę pomóc?", "bot");
};

async function showConversations() {
  chatView.style.display = "none";
  recommendationsView.style.display = "none";
  conversationsView.style.display = "block";
  conversationsBody.innerHTML = "";

  try {
    const res = await fetch("list_chats.php");
    const data = await res.json();
    const chats = data.chats || [];

    if (!chats.length) {
      conversationsBody.innerHTML = "<p class=\"chat-sessions-empty\">Brak zapisanych rozmów. Napisz wiadomość albo zacznij nowy czat.</p>";
      return;
    }

    chats.forEach(chat => {
      const item = document.createElement("button");
      item.type = "button";
      item.className = "chat-session-item" + (Number(chat.id) === Number(data.active_id) ? " active" : "");

      const title = document.createElement("span");
      title.className = "chat-session-title";
      title.textContent = chat.preview ? chat.preview : "Nowa rozmowa";

      const date = document.createElement("span");
      date.className = "chat-session-date";
      date.textContent = chat.created_at || "";

      item.appendChild(title);
      item.appendChild(date);
      item.onclick = () => openConversation(chat.id);
      conversationsBody.appendChild(item);
    });
  } catch (err) {
    console.error("Ошибка загрузки списка rozmów:", err);
    conversationsBody.innerHTML = "<p class=\"chat-sessions-empty\">Nie można załadować listy rozmów.</p>";
  }
}

async function openConversation(id) {
  const res = await fetch("open_chat.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id })
  });
  const data = await res.json();
  if (!data.ok) return;

  showChatView();
  await loadChatHistory();
}

let lastUpdate = 0;

const recommendedBtn = document.getElementById("recommended-btn");
const backChatBtn = document.getElementById("back-chat-btn");

const chatView = document.getElementById("chat-view");
const recommendationsView = document.getElementById("recommendations-view");
const recommendationsBody = document.getElementById("recommendations-body");
const conversationsView = document.getElementById("conversations-view");
const conversationsBody = document.getElementById("conversations-body");
const historyChatBtn = document.getElementById("history-chat-btn");

historyChatBtn.onclick = showConversations;

recommendedBtn.onclick = async () => {

  chatView.style.display = "none";
  conversationsView.style.display = "none";
  recommendationsView.style.display = "block";

  const res = await fetch("recommendations.php?last=" + lastUpdate);
  const data = await res.json();

  // если нет обновлений — просто показываем старые
  if (data.no_update) return;

  // обновляем время
  if (data.updated) {
    lastUpdate = data.updated;
  } else {
    lastUpdate = Date.now();
  }

  showProducts(data.products || []);
};

backChatBtn.onclick = () => {
  showChatView();
};

function showProducts(products) {

  recommendationsBody.innerHTML = "";

  if (!products.length) {
    recommendationsBody.innerHTML = "<p>Brak rekomendacji</p>";
    return;
  }

  products.forEach(p => {

    const card = document.createElement("div");
    card.className = "product-card";
    const photo = normalizeProductPhoto(p.photo);

    card.innerHTML = `
      <a href="../../StronaTowara/Php_files/StronaTowara.php?id=${p.id}">
        <strong>${p.name}</strong><br>
        ${p.price} zł<br>
        ${photo ? `<img src="${photo}">` : ""}
      </a>
    `;

    if (window.attachQuickView) {
      window.attachQuickView(card, {
        id: p.id,
        name: p.name,
        price: p.price,
        description: p.description,
        image: photo
      }, `../../StronaTowara/Php_files/StronaTowara.php?id=${p.id}`);
    }
    recommendationsBody.appendChild(card);
  });
}

</script>

<script>
(function () {
  const header = document.querySelector('header');
  if (!header) return;

  const compactAt = 40;

  function updateHeaderCompact() {
    header.classList.toggle('scrolled', window.scrollY > compactAt);
  }

  updateHeaderCompact();
  window.addEventListener('scroll', updateHeaderCompact, { passive: true });
})();
</script>
<script src="../Js_files/js/bootstrap.bundle.min.js"></script>

</body>
</html>
