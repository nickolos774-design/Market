<?php
if (!isset($pageTitle) || $pageTitle === '') {
    $pageTitle = '';
}

session_start();

if (!isset($_SESSION['user']['id'])) {
    header('Location: ../../../Regestracja and login/Html_files/Login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link href="../Css_files/styleindex.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .blank-main {
      min-height: 55vh;
      margin-top: 18vw;
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
      $role = $_SESSION['user']['role'] ?? '';
      $profileLink = $role === 'admin' ? '../../../Admin/AdminPanel.php' :
                     ($role === 'employee' ? '../../../Pracownik/pracownikpanel.php' :
                     'Moje dane.php');
      $max_length = 14;
      $first_name = $_SESSION['user']['first_name'];
      $last_name = $_SESSION['user']['last_name'];
      $display_name = mb_substr($first_name, 0, 1, 'UTF-8')
        . mb_substr($first_name, 1, max(0, $max_length - 3), 'UTF-8')
        . ' '
        . (mb_strlen($last_name, 'UTF-8') > 1
            ? mb_substr($last_name, 0, 1, 'UTF-8') . '.'
            : mb_substr($last_name, 0, 1, 'UTF-8'));
    ?>
    <div class="profile-drop">
      <a href="<?= htmlspecialchars($profileLink) ?>" class="user-link">
        <span class="user-info-inline"><span class="user-name"><?= htmlspecialchars($display_name) ?></span></span>
      </a>
      <?php
        require_once dirname(__DIR__, 3) . '/secure/profile_menu.php';
        dw_profile_menu('');
      ?>
    </div>
    <a href="../../../Regestracja and login/logout.php">Wyloguj się</a>
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

<main class="blank-main"></main>

<footer id="page-footer" class="footer">
  <div class="footer-container">
    <div class="footer-column">
      <h3>Jak mogę skontaktować?</h3>
      <p>Viber: +48725975501</p>
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
<script src="../../../header-scroll.js"></script>
</body>
</html>
