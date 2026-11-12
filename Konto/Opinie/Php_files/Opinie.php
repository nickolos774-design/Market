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
  <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Пример стилей для контейнера продуктов */
    .product-container {
      margin-top: 200px; /* Увеличиваем отступ сверху */
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      padding: 20px;
    }

    .pagination {
    margin-top: 15px;
    display: flex;
    justify-content: center; /* выравнивание по горизонтали */
    gap: 5px; /* расстояние между ссылками */
    flex-wrap: wrap; /* перенос на новую строку при необходимости */
}

.pagination a,
.pagination .current {
    padding: 5px 10px;
    background: #cfd9cd;
    color: #333;
    text-decoration: none;
    border-radius: 3px;
    display: inline-block;
    text-align: center;
}

.pagination .current {
    background: #333;
    color: white;
}

.no-reviews {
    margin: 0;             
    padding: 10px 0;       
    text-align: center;    
    font-style: italic;  
    font-size:32px;
    color: #000000ff;            
}

.reviews {
  margin-top: 20px;
  width: 60%;
  max-width: 1300px; 
  margin-left: auto;
  margin-right: auto;
}

.stars .half {
    color: gold;
}

.stars .empty {
    color: lightgray; /* цвет пустых звезд */
}

.reviews {
  margin-top: 20px;
  width: 100%;
  max-width: 70%; 
  margin-left: auto;
  margin-right: auto;
}

.review {
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.1); /* Полупрозрачный фон */
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 15px;
  width: 100%;
}

.review-content h3 {
  margin: 0;
  font-size: 24px;
  color: black;
  font-weight: bold;
}

.review-content p {
  margin: 5px 0 0;
  font-size: 18px;
  color: black;
}

.review {
  display: flex;
  align-items: flex-start;
  background: rgba(255, 255, 255, 0.1); 
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 15px;
  width: 100%;
  gap: 15px; /* расстояние между аватаром и контентом */
}

.avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: 2px solid white;
}

.review-content {
  flex: 1;
}

.stars {
  color: #8D242E;
  font-size: 28px;
}

.review {
  display: flex;
  align-items: flex-start; /* выравнивание по верху */
  background: rgba(255, 255, 255, 0.1);
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 15px;
  width: 100%;
  gap: 15px; /* расстояние между блоками */
}

.review-content {
  flex: 1; /* занимает всю оставшуюся ширину */
}

.review-content p {
  white-space: pre-line;      /* переносы строк из базы */
  word-wrap: break-word;      /* поддержка старых браузеров */
  overflow-wrap: break-word;  /* современный стандарт */
  word-break: break-word;     /* доп. совместимость */
  line-height: 1.5;           /* чтобы текст «дышал» */
}


.product-photo {
  width: 120px;
  height: auto;
  border-radius: 8px;
  border: 1px solid #ccc;
  margin-left: 15px; /* отступ слева от текста */
  align-self: flex-start; /* выравнивание по верху блока */
}

.review-status.pending { color: orange; font-weight: bold; }
.review-status.rejected { color: red; font-weight: bold; }

.review-actions {
    margin-top: 20px;
    margin-bottom: 10px;
}
.edit-btn, .delete-btn {
    margin-right: 10px;
    text-decoration: none;
    padding: 4px 8px;
    border-radius: 4px;
    background: #eee;
    color: #333;
}
.edit-btn:hover { background: #cce5ff; }
.delete-btn:hover { background: #f8d7da; }

.review-status.pending { color: orange; }
.review-status.approved { color: green; }
.review-status.rejected { color: red; }

header a {
     color: black;           /* чёрный цвет для всех ссылок в header */
     text-decoration: none;  /* убираем подчёркивание */
   }

header a:hover {
    color: #639477;            /* серый при наведении */
  }

/* Модальное окно для редактирования отзыва */
.modal {
    display: none; /* по умолчанию скрыто */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.modal-content {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    width: 400px;
    max-width: 90%;
    position: relative;
}

.modal-close {
    position: absolute;
    top: 10px;
    right: 15px;
    cursor: pointer;
    font-size: 20px;
}

#editForm textarea {
    width: 100%;
    height: 150px;
    resize: none;
    padding: 8px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 16px;
}

#editForm button {
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

#editForm button[type="submit"] {
    background: #8D242E;
    color: white;
}

#editForm button#cancelBtn {
    background: #eee;
    color: #333;
    margin-right: 10px;
}

#editForm p {
    font-size: 12px;
    color: #555;
    margin-top: 5px;
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

<body>
<script src="../../../runes-bg.js"></script>
  
<div class="menu">
    <a href="../../Moje dane/Php_files/Moje dane.php" class="menu-button">Moje dane</a>
    <a href="../../Zamowienia/Php_files/Zamowienia.php" class="menu-button">Zamówienia</a>
    <a href="../../Schowek/Php_files/Schowek.php" class="menu-button">Schowek</a>
    <a href="../../Opinie/Php_files/Opinie.php" class="menu-button active">Opinie</a>
    <a href="../../Faktura/Php_files/Faktura.php" class="menu-button">Faktura</a>
    <a href="../../Koszyk/Php_files/Koszyk.php" class="menu-button">Koszyk</a>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span id="closeModal" class="modal-close">&times;</span>
        <h3>Edytuj opinię</h3>
        <form id="editForm">
            <textarea id="editText" maxlength="500" required></textarea>
            <input type="hidden" id="editId">
            <div style="margin-top:10px; text-align:right;">
                <button type="button" id="cancelBtn">Anuluj</button>
                <button type="submit">Zapisz</button>
            </div>
            <p>Limit: 500 znaków</p>
        </form>
    </div>
</div>


<h2>Twoje opiniję</h2>
<div class="reviews-wrapper">
    <div class="reviews" id="reviews-container">
        <!-- Отзывы будут подгружены через JS -->
    </div>
    <div class="pagination" id="pagination"></div>
</div>

  <script>
   
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('page-link')) {
        e.preventDefault();
        let page = e.target.getAttribute('data-page');

        fetch(`display_all_reviews.php?page=${page}`)
            .then(res => res.text())
            .then(html => {
                document.querySelector('.reviews').innerHTML = html;

                // Сразу перемещаемся к блоку отзывов
                document.querySelector('.reviews-wrapper')
                    .scrollIntoView({behavior: "auto", block: "start"});
            });
    }
});

function renderReviews(data) {
    const container = document.getElementById('reviews-container');
    const pagination = document.getElementById('pagination');
    container.innerHTML = '';
    pagination.innerHTML = '';

    if (data.error) {
        container.innerHTML = `<p class="no-reviews">${data.error}</p>`;
        return;
    }

    if (data.reviews.length === 0) {
        container.innerHTML = `<p class="no-reviews">Brak opinii</p>`;
        return;
    }

    data.reviews.forEach(r => {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += (i <= r.stars) ? '<span class="star full">★</span>' : '<span class="star empty">★</span>';
        }

        let statusLabel = '';
        if (r.status === 'oczekujacy') statusLabel = `<span class="review-status pending">Na moderacji</span>`;
        else if (r.status === 'zatwierdzony') statusLabel = `<span class="review-status approved">Zatwierdzono</span>`;
        else if (r.status === 'odrzucony') statusLabel = `<span class="review-status rejected">Odrzucono</span>`;
        else statusLabel = `<span class="review-status unknown">${r.status}</span>`;

        let actions = '';
        if (['oczekujacy', 'zatwierdzony'].includes(r.status)) {
            actions = `
                <a href="edit_review.php?id=${r.id}" class="edit-btn">Edytuj</a>
                <a href="#" class="delete-btn" data-id="${r.id}">Usuń</a>

            `;
        } else if (r.status === 'odrzucony') {
            actions = `
                <a href="delete_review.php?id=${r.id}" class="delete-btn" onclick="return confirm('Czy na pewno chcesz usunąć ten komentarz?');">Usuń</a>
            `;
        }

        container.innerHTML += `
            <div class="review">
                ${statusLabel}
                <img src="${r.user_photo}" alt="Avatar" class="avatar">
                <div class="review-content">
                    <h3>${r.user_name} <span class="stars">${stars}</span></h3>
                    <p><strong>Produkt:</strong> ${r.product}</p>
                    <p>${r.text}</p>
                    <div class="review-actions">${actions}</div>
                </div>
                <img src="${r.product_photo}" alt="${r.product}" class="product-photo">
            </div>
        `;
    });

    // пагинация
    for (let i = 1; i <= data.totalPages; i++) {
        let cls = (i === data.page) ? 'current' : 'page-link';
        pagination.innerHTML += `<a href="#" class="${cls}" data-page="${i}">${i}</a>`;
    }
}

function loadReviews(page = 1) {
    fetch(`display_all_reviews.php?page=${page}`)
        .then(res => res.json())
        .then(data => renderReviews(data));
}

// обработка клика на пагинацию
document.addEventListener('click', e => {
    if (e.target.classList.contains('page-link')) {
        e.preventDefault();
        let page = parseInt(e.target.getAttribute('data-page'));
        loadReviews(page);
    }
});

// загружаем первую страницу
loadReviews();

document.addEventListener('click', e => {
    // открытие модалки при нажатии на редактирование
    if (e.target.classList.contains('edit-btn')) {
        e.preventDefault();
        const reviewDiv = e.target.closest('.review');
        const reviewId = e.target.getAttribute('href').split('id=')[1];
        const reviewText = reviewDiv.querySelector('p:nth-of-type(2)').innerText;

        document.getElementById('editId').value = reviewId;
        document.getElementById('editText').value = reviewText;

        document.getElementById('editModal').style.display = 'flex';
    }

    // закрытие модалки по крестику
    if (e.target.id === 'closeModal' || e.target.id === 'cancelBtn') {
        document.getElementById('editModal').style.display = 'none';
    }
});

// отправка изменений
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const reviewId = document.getElementById('editId').value;
    const reviewText = document.getElementById('editText').value;

    fetch('edit_review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${reviewId}&text=${encodeURIComponent(reviewText)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Opinia została zaktualizowana!');
            document.getElementById('editModal').style.display = 'none';
            loadReviews(); // перезагружаем отзывы
        } else {
            alert('Błąd: ' + data.error);
        }
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

document.addEventListener('click', e => {
    if (e.target.classList.contains('delete-btn')) {
        e.preventDefault();
        if (!confirm('Czy na pewno chcesz usunąć ten komentarz?')) return;

        const reviewDiv = e.target.closest('.review');
        const reviewId = e.target.dataset.id; // <-- берём id из data-атрибута

        fetch('delete_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${reviewId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                reviewDiv.remove(); // удаляем блок
            } else {
                alert('Błąd: ' + data.error);
            }
        })
        .catch(err => console.error(err));
    }
});


  </script>
<script src="../../../header-scroll.js"></script>
</body>
</html>
