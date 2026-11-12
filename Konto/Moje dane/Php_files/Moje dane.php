<?php
session_start();

// ================== AUTH ==================
if (!isset($_SESSION['user']['id'])) {
    die('Пользователь не авторизован');
}
$user_id = (int)$_SESSION['user']['id'];

// ================== DB CONNECTION ==================
$config = require __DIR__ . '../../../../secure/config.php';
try {
    $pdo = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}

if (!isset($_SESSION['user']['id'])) {
    header("Location: /../../../../Dragon World1111111111/Regestracja and login/Login.html");
    exit;
}

$message = '';
$nameMessage = '';

// ================== PHOTO UPLOAD ==================
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['Foto']) && $_FILES['Foto']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['Foto']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['Foto']['tmp_name']);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'];

        if (isset($allowed[$mime])) {
            $filename = $user_id . '_' . time() . '.' . $allowed[$mime];
            if (move_uploaded_file($_FILES['Foto']['tmp_name'], $uploadDir . $filename)) {
                $stmt = $pdo->prepare('UPDATE [Użytkownicy] SET Foto=? WHERE id=?');
                $stmt->execute(['uploads/' . $filename, $user_id]);
                $_SESSION['user']['foto'] = 'uploads/' . $filename;
                $message .= '<p style="color:green;">Фото обновлено</p>';
            } else {
                $message .= '<p style="color:red;">Ошибка при сохранении файла</p>';
            }
        } else {
            $message .= '<p style="color:red;">Неверный формат изображения</p>';
        }
    }
}

// ================== CHANGE PASSWORD ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_password'], $_POST['new_password'])) {
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);

    if ($currentPassword === '' || $newPassword === '') {
        $message .= '<p style="color:red;">Оба поля обязательны для заполнения</p>';
    } else {
        $stmt = $pdo->prepare('SELECT [Hasło] FROM [Użytkownicy] WHERE id = ?');
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Убираем возможные пробелы и символы конца строки
            $hashFromDb = rtrim($row['Hasło']);

            if (password_verify($currentPassword, $hashFromDb)) {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare('UPDATE [Użytkownicy] SET [Hasło] = ? WHERE id = ?');
                $updateStmt->execute([$newHash, $user_id]);
                $message .= '<p style="color:green;">Пароль успешно изменён</p>';
            } else {
                $message .= '<p style="color:red;">Текущий пароль введён неверно</p>';
            }
        } else {
            $message .= '<p style="color:red;">Пользователь не найден</p>';
        }
    }
}


// ================== NAME ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $imie = trim($_POST['imie'] ?? '');
    $nazwisko = trim($_POST['nazwisko'] ?? '');
    $namePattern = '/^[\p{L}][\p{L}\s\'-]{0,49}$/u';

    $imieForm = $imie;
    $nazwiskoForm = $nazwisko;

    if (!preg_match($namePattern, $imie)) {
        $nameMessage = '<p style="color:red;">Nieprawidłowe imię</p>';
    } elseif (!preg_match($namePattern, $nazwisko)) {
        $nameMessage = '<p style="color:red;">Nieprawidłowe nazwisko</p>';
    } else {
        $stmt = $pdo->prepare('UPDATE [Użytkownicy] SET [Imię]=?, Nazwisko=? WHERE id=?');
        $stmt->execute([$imie, $nazwisko, $user_id]);
        $_SESSION['user']['first_name'] = $imie;
        $_SESSION['user']['last_name'] = $nazwisko;
        $nameMessage = '<p style="color:green;">Imię i nazwisko zostały zmienione</p>';
    }
}

// ================== USER DATA ==================
$stmt = $pdo->prepare('SELECT Foto, [Imię], Nazwisko, email FROM [Użytkownicy] WHERE id=?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$currentPhoto = (!empty($user['Foto']) && file_exists(__DIR__ . '/' . $user['Foto'])) ? $user['Foto'] : 'uploads/Drak.jpeg';
if (!isset($imieForm)) {
    $imieForm = $user['Imię'] ?? '';
    $nazwiskoForm = $user['Nazwisko'] ?? '';
}

// ================== ADD ADDRESS ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $ulica = trim($_POST['ulica'] ?? '');
    $miasto = trim($_POST['miasto'] ?? '');
    $woj = trim($_POST['wojewodztwo'] ?? '');
    $kod = trim($_POST['kod_pocztowy'] ?? '');
    $kraj = trim($_POST['kraj'] ?? '');

    $errors = [];
    if (!preg_match('/^[\p{L}0-9\s.,\/-]{3,150}$/u', $ulica)) $errors[] = 'Неверная улица';
    if (!preg_match('/^[\p{L}\s-]{2,100}$/u', $miasto)) $errors[] = 'Неверный город';
    if (!preg_match('/^[\p{L}\s-]{2,100}$/u', $woj)) $errors[] = 'Неверное воеводство';
    if (!preg_match('/^\d{2}-\d{3}$/', $kod)) $errors[] = 'Неверный индекс';
    if (!preg_match('/^[\p{L}\s-]{2,100}$/u', $kraj)) $errors[] = 'Неверная страна';

    if (!$errors) {
        $is_default = $pdo->query("SELECT COUNT(*) FROM [Adresy_dostawy] WHERE Id_klienta={$user_id}")->fetchColumn() == 0 ? 1 : 0;
        $stmt = $pdo->prepare('
            INSERT INTO [Adresy_dostawy] 
            (Id_klienta, Miasto, [Wojewódstwo], [Kod_pocztowy], Kraj, [Domyślny], [Data_utworzenia], Ulica)
            VALUES (?, ?, ?, ?, ?, ?, GETDATE(), ?)
        ');
        $stmt->execute([$user_id, $miasto, $woj, $kod, $kraj, $is_default, $ulica]);
        $message .= '<p style="color:green;">Адрес доставки добавлен</p>';
    } else {
        foreach ($errors as $e) $message .= '<p style="color:red;">'.$e.'</p>';
    }
}

// ================== SET DEFAULT ADDRESS ==================
if (isset($_POST['set_default_address'])) {
    $id = (int)$_POST['set_default_address'];
    $pdo->prepare('UPDATE [Adresy_dostawy] SET [Domyślny]=0 WHERE Id_klienta=?')->execute([$user_id]);
    $pdo->prepare('UPDATE [Adresy_dostawy] SET [Domyślny]=1 WHERE id=? AND Id_klienta=?')->execute([$id, $user_id]);
}

// ================== FETCH ADDRESSES ==================
$stmt = $pdo->prepare("SELECT * FROM [Adresy_dostawy] WHERE Id_klienta = ? ORDER BY [Domyślny] DESC, [Data_utworzenia] DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== UPDATE ADDRESS ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    $addressId = (int)($_POST['address_id'] ?? 0);
    $ulica = trim($_POST['ulica'] ?? '');
    $miasto = trim($_POST['miasto'] ?? '');
    $woj = trim($_POST['wojewodztwo'] ?? '');
    $kod = trim($_POST['kod_pocztowy'] ?? '');
    $kraj = trim($_POST['kraj'] ?? '');

    $errors = [];
    if (!preg_match('/^[\p{L}0-9\s.,\/-]{3,150}$/u', $ulica)) $errors[] = 'Niepoprawna ulica';
    if (!preg_match('/^[\p{L}\s-]{2,100}$/u', $miasto)) $errors[] = 'Niepoprawne miasto';
    if (!preg_match('/^[\p{L}\s-]{2,100}$/u', $woj)) $errors[] = 'Niepoprawne województwo';
    if (!preg_match('/^\d{2}-\d{3}$/', $kod)) $errors[] = 'Niepoprawny kod pocztowy';
    if (!preg_match('/^[\p{L}\s-]{2,100}$/u', $kraj)) $errors[] = 'Niepoprawny kraj';

    if (!$errors) {
        $stmt = $pdo->prepare('
            UPDATE [Adresy_dostawy]
            SET Miasto = ?, [Wojewódstwo] = ?, [Kod_pocztowy] = ?, Kraj = ?, Ulica = ?
            WHERE id = ? AND Id_klienta = ?
        ');
        $stmt->execute([$miasto, $woj, $kod, $kraj, $ulica, $addressId, $user_id]);
        $message .= $stmt->rowCount()
            ? '<p style="color:green;">Adres został zmieniony</p>'
            : '<p style="color:red;">Nie znaleziono adresu</p>';
    } else {
        foreach ($errors as $e) $message .= '<p style="color:red;">'.htmlspecialchars($e).'</p>';
    }
}

// ================== DELETE ADDRESS ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
    $addressId = (int)$_POST['delete_address'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('
            SELECT [Domyślny]
            FROM [Adresy_dostawy]
            WHERE id = ? AND Id_klienta = ?
        ');
        $stmt->execute([$addressId, $user_id]);
        $wasDefault = $stmt->fetchColumn();

        if ($wasDefault === false) {
            $pdo->rollBack();
            $message .= '<p style="color:red;">Nie znaleziono adresu</p>';
        } else {
            $pdo->prepare('
                DELETE FROM [Adresy_dostawy]
                WHERE id = ? AND Id_klienta = ?
            ')->execute([$addressId, $user_id]);

            if ($wasDefault) {
                $next = $pdo->prepare('
                    SELECT TOP 1 id
                    FROM [Adresy_dostawy]
                    WHERE Id_klienta = ?
                    ORDER BY [Data_utworzenia] DESC
                ');
                $next->execute([$user_id]);
                $nextId = $next->fetchColumn();
                if ($nextId) {
                    $pdo->prepare('
                        UPDATE [Adresy_dostawy]
                        SET [Domyślny] = 1
                        WHERE id = ? AND Id_klienta = ?
                    ')->execute([$nextId, $user_id]);
                }
            }

            $pdo->commit();
            $message .= '<p style="color:green;">Adres został usunięty</p>';
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message .= '<p style="color:red;">Tego adresu nie można usunąć, bo jest użyty w zamówieniu. Możesz go zmienić.</p>';
    }
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link href="../Css_files/styleindex.css?v=3" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>body {
    font-family: 'Julee', cursive;
    margin: 0;
    padding: 0;
    overflow-x: hidden; 
    background-color: rgba(169, 127, 130, 0.88);
  }
    /* Пример стилей для контейнера продуктов */
    .product-container {
      margin-top: 200px; /* Увеличиваем отступ сверху */
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      padding: 20px;
    }



.user-card-wrapper {
  margin-top:5%;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 600px;
}

.user-card {
  display: flex;
  gap: 30px;
  align-items: flex-start;
  padding: 20px;
  border-radius: 10px;
  color: #000;
  font-size: 28px;
  font-family: sans-serif;

}

.user-photo {
  width: 500px;
  border-radius: 8px;
  box-shadow: 0 0 8px rgba(0, 0, 0, 0.3);
  object-fit: cover;
}

.user-info hr {
  margin: 10px 0;
  font-size: 48px;
}

.password-input {
  width: 300px;         /* фиксированная ширина */
  max-width: 100%;      /* чтобы не выходило за контейнер */
  padding: 10px;
  font-size: 16px;
  box-sizing: border-box; /* чтобы padding учитывался внутри ширины */
  margin-bottom: 10px;  /* отступ между полями */
  border: 1px solid #ccc;
  border-radius: 4px;
}


.delivery-button {
  margin-top: 10px;
  background-color: #4CAF50;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 5px;
  cursor: pointer;
}

a {
  color: black;         /* цвет текста ссылки */
  text-decoration: none; /* убираем подчёркивание */
}

a:hover {
  color: #639477;         
}

/* Фон затемнения */
.modal {
  display: none; 
  position: fixed;
  z-index: 1000; /* поверх всего */
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background: rgba(0,0,0,0.6);
}

/* Контейнер окна */
.modal-content {
  background: #fff;
  margin: 10% auto;
  padding: 20px;
  border-radius: 12px;
  width: 400px;
  max-width: 90%;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  animation: fadeIn 0.3s ease;
}

/* Кнопка закрытия */
.close {
  float: right;
  font-size: 24px;
  font-weight: bold;
  cursor: pointer;
}

/* Анимация */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

.form-group { 
  margin-bottom: 12px; 
}


  .error-msg { 
    color: red; 
    font-size: 14px; 
    height: 16px; 
    display: block; 
  }


  .list-style-type{
    list-style-type: none; 
    padding-left: 0;
  }

  .address-row {
    margin-bottom: 12px;
    font-size: 18px;
    line-height: 1.35;
  }

  .address-actions {
    display: inline-flex;
    gap: 8px;
    margin-left: 8px;
    vertical-align: middle;
  }

  .address-actions button {
    border: none;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 15px;
    cursor: pointer;
    color: #fff;
  }

  .address-edit {
    background: #1E5946;
  }

  .address-delete {
    background: #8D242E;
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

require_once dirname(__DIR__, 3) . '/secure/grywalizacja.php';
require_once dirname(__DIR__, 3) . '/secure/profile_menu.php';
$postep = dw_postep(dw_pdo(), (int) $_SESSION['user']['id']);
$_SESSION['klasa'] = $postep['klasa'];
echo '<div class="profile-drop">';
echo '<a href="Moje dane.php" class="user-link">';
echo '<span class="user-info-inline"><span class="user-name">' . htmlspecialchars($display_name) . '</span>';
if ($postep['klasa'] !== '') {
    echo '<span class="user-title">' . htmlspecialchars($postep['klasa']) . '</span>';
}
echo '</span>';
dw_level_sketch();
echo '</a>';
dw_profile_menu('', '', false);
echo '</div>';
?>
  </div>
<?php else: ?>
  <a href="../../Regestracja and login/Login.html" class="konto header-item">Konto</a>
<?php endif; ?>
  
<a href="../../Schowek/Php_files/Schowek.php" class="serdce header-item"><i class="fas fa-heart" style="color: #8D242E;"></i></a>

<a href="../../Koszyk/Php_files/Koszyk.php" class="koszyk header-item">
  Koszyk
  <span class="cart-count" id="cart-count"></span>
</a>

</header>

<div class="menu">
    <a href="../Php_files/Moje dane.php" class="menu-button active">Moje dane</a>
    <a href="../../Zamowienia/Php_files/Zamowienia.php" class="menu-button">Zamówienia</a>
    <a href="../../Schowek/Php_files/Schowek.php" class="menu-button">Schowek</a>
    <a href="../../Opinie/Php_files/Opinie.php" class="menu-button">Opinie</a>
    <a href="../../Faktura/Php_files/Faktura.php" class="menu-button">Faktura</a>
    <a href="../../Koszyk/Php_files/Koszyk.php" class="menu-button">Koszyk</a>
</div>

<!-- Центрирование всей карточки -->
<div class="user-card-wrapper">
  <div class="user-card">
    <div>
      <div style="display: flex; flex-direction: column; align-items: center;">
  <img src="<?= htmlspecialchars($currentPhoto) ?>" alt="Фото пользователя" class="user-photo" style="max-width: 450px; height: auto; border-radius:8px; box-shadow:0 0 8px rgba(0,0,0,0.3);">

  <form method="post" enctype="multipart/form-data" style="margin-top: 10px; width: 100%; text-align: center;">
    <input type="file" name="Foto" accept="image/jpeg,image/png,image/gif" required style="display: block; margin: 0 auto 8px;">
    <button type="submit" class="delivery-button" style="width: 150px;">Nowe zdj</button>
  </form>
</div>
    </div>
    
    <div class="user-info">
      <p><strong>Imię:</strong> <?= htmlspecialchars($user['Imię']) ?></p>
      <p><strong>Nazwisko:</strong> <?= htmlspecialchars($user['Nazwisko']) ?></p>
      <?php if ($nameMessage !== '' && !str_contains($nameMessage, 'Nieprawidłowe')) echo $nameMessage; ?>
      <button type="button" id="openNameModal" class="delivery-button">Zmień imię i nazwisko</button>
      <p><strong>Adres e-mail:</strong> <?= htmlspecialchars($user['email']) ?></p> 

      <hr>

      <p><strong>Zmiana hasła:</strong></p>

   <p id="password-error" style="color: red; font-weight: bold; font-size: 18px;"></p>

     <form method="post" action="">
  <input type="password" id="current_password" name="current_password" placeholder="Napisz obecne hasło" class="password-input" required>
  <input type="password" name="new_password" placeholder="Napisz nowe hasło" class="password-input" required>
  <button type="submit" class="delivery-button">Zmień hasło</button>
</form>

      <button type="button" id="openAddressModal" class="delivery-button">+ Adres dostawy</button>


<?php
$stmt = $pdo->prepare("
    SELECT TOP (1000) *
    FROM [Adresy_dostawy]
    WHERE Id_klienta = ?
    ORDER BY [Domyślny] DESC, [Data_utworzenia] DESC
");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
 <h3 id="addresses">Adresy dostawy:</h3>
 <?= $message ?>
     <?php foreach ($addresses as $addr): ?>
  <div class="address-row">
    <?= htmlspecialchars($addr['Ulica']) ?>,
    <?= htmlspecialchars($addr['Kod_pocztowy']) ?>
    <?= htmlspecialchars($addr['Miasto']) ?>
    <?php if ($addr['Domyślny']): ?>
      <strong>(Główny)</strong>
    <?php endif; ?>

    <span class="address-actions">
      <button
        type="button"
        class="address-edit"
        data-id="<?= (int)$addr['id'] ?>"
        data-ulica="<?= htmlspecialchars($addr['Ulica'], ENT_QUOTES) ?>"
        data-miasto="<?= htmlspecialchars($addr['Miasto'], ENT_QUOTES) ?>"
        data-woj="<?= htmlspecialchars($addr['Wojewódstwo'], ENT_QUOTES) ?>"
        data-kod="<?= htmlspecialchars($addr['Kod_pocztowy'], ENT_QUOTES) ?>"
        data-kraj="<?= htmlspecialchars($addr['Kraj'], ENT_QUOTES) ?>"
      >Zmień</button>

      <?php if (!$addr['Domyślny']): ?>
      <form method="post" style="display:inline;">
        <button type="submit" class="address-edit" name="set_default_address" value="<?= (int)$addr['id'] ?>">Główny</button>
      </form>
      <?php endif; ?>

      <form method="post" action="#addresses" style="display:inline;">
        <button
          type="submit"
          class="address-delete"
          name="delete_address"
          value="<?= (int)$addr['id'] ?>"
          onclick="return confirm('Usunąć adres?');"
        >Usuń</button>
      </form>
    </span>
  </div>
<?php endforeach; ?>


<div id="nameModal" class="modal"<?= $nameMessage !== '' && str_contains($nameMessage, 'Nieprawidłowe') ? ' style="display:block;"' : '' ?>>
  <div class="modal-content">
    <span class="close" id="closeNameModal">&times;</span>
    <h2>Zmień imię i nazwisko</h2>
    <?php if (str_contains($nameMessage, 'Nieprawidłowe')) echo $nameMessage; ?>
    <form method="post">
      <input type="text" name="imie" value="<?= htmlspecialchars($imieForm) ?>" placeholder="Imię" class="password-input" required maxlength="50">
      <input type="text" name="nazwisko" value="<?= htmlspecialchars($nazwiskoForm) ?>" placeholder="Nazwisko" class="password-input" required maxlength="50">
      <button type="submit" name="update_name" value="1" class="delivery-button">Zapisz</button>
    </form>
  </div>
</div>

<!-- Модальное окно -->
<div id="addressModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2 id="addressModalTitle">Dodaj adres dostawy</h2>
    <form method="post" id="addressForm" novalidate>
      <input type="hidden" name="address_id" id="addressId" value="">
      <input type="text" name="ulica" placeholder="Ulica i numer" class="password-input" required><small class="error-msg"></small>
      <input type="text" name="miasto" placeholder="Miasto" class="password-input" required><small class="error-msg"></small>
      <input type="text" name="wojewodztwo" placeholder="Województwo" class="password-input" required><small class="error-msg"></small>
      <input type="text" name="kod_pocztowy" placeholder="Kod pocztowy" class="password-input" required><small class="error-msg"></small>
      <input type="text" name="kraj" placeholder="Kraj" class="password-input" required><small class="error-msg"></small>
      <button type="submit" name="add_address" id="addressSubmit" class="delivery-button">Zapisz</button>
    </form>
  </div>
</div>

    </div>
  </div>  

<script>
// Проверка текущего пароля
const currentPwdInput = document.getElementById('current_password');
const passwordErrorDiv = document.getElementById('password-error');
let timeoutId;

currentPwdInput.addEventListener('input', () => {
  clearTimeout(timeoutId);
  const val = currentPwdInput.value.trim();
  passwordErrorDiv.textContent = '';
  if (!val) return;

  timeoutId = setTimeout(() => {
    fetch('check_password.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'password=' + encodeURIComponent(val)
    })
    .then(res => res.json())
    .then(data => {
      if (data.valid === true) {
        passwordErrorDiv.textContent = '';
      } else {
        passwordErrorDiv.textContent = 'Niepoprawnie hasło!';
      }
    })
    .catch(() => {
      passwordErrorDiv.textContent = 'błąd hasła.';
    });
  }, 500);
});


// Модалка
const modal = document.getElementById('addressModal');
const btn = document.getElementById('openAddressModal');
const span = modal.querySelector('.close');
const addressTitle = document.getElementById('addressModalTitle');
const addressIdInput = document.getElementById('addressId');
const addressSubmit = document.getElementById('addressSubmit');

function openAddressModal(mode) {
  if (mode === 'add') {
    form.reset();
    addressIdInput.value = '';
    addressSubmit.name = 'add_address';
    addressSubmit.textContent = 'Zapisz';
    addressTitle.textContent = 'Dodaj adres dostawy';
  }
  modal.style.display = 'block';
}

btn.onclick = () => openAddressModal('add');
span.onclick = () => modal.style.display = 'none';

const nameModal = document.getElementById('nameModal');
document.getElementById('openNameModal').onclick = () => nameModal.style.display = 'block';
document.getElementById('closeNameModal').onclick = () => nameModal.style.display = 'none';
window.onclick = e => {
  if (e.target === modal) modal.style.display = 'none';
  if (e.target === nameModal) nameModal.style.display = 'none';
};

document.querySelectorAll('.address-edit[data-id]').forEach(button => {
  button.addEventListener('click', () => {
    form.reset();
    addressIdInput.value = button.dataset.id;
    form.ulica.value = button.dataset.ulica || '';
    form.miasto.value = button.dataset.miasto || '';
    form.wojewodztwo.value = button.dataset.woj || '';
    form.kod_pocztowy.value = button.dataset.kod || '';
    form.kraj.value = button.dataset.kraj || '';
    addressSubmit.name = 'update_address';
    addressSubmit.textContent = 'Zapisz zmiany';
    addressTitle.textContent = 'Zmień adres';
    modal.style.display = 'block';
  });
});

// Валидация формы адреса
const form = document.getElementById('addressForm');
const patterns = {
  ulica:/^[A-Za-z0-9\s.,/ńŃćĆśŚąĄęĘłŁóÓżŻźŹ-]{3,100}$/,
  miasto:/^[A-Za-zÀ-ÿ\s-]{2,50}$/,
  wojewodztwo:/^[A-Za-zÀ-ÿ\s-]{2,50}$/,
  kod_pocztowy:/^\d{2}-\d{3}$/,
  kraj:/^[A-Za-zÀ-ÿ\s-]{2,50}$/
};
form.querySelectorAll('input').forEach(input=>{
  input.addEventListener('input', ()=>{
    const errorEl=input.nextElementSibling;
    if(!errorEl) return;
    const pattern=patterns[input.name];
    if(!pattern) return;
    errorEl.textContent = pattern.test(input.value.trim())?'':'Неверный формат';
  });
});
form.addEventListener('submit', e=>{
  let valid=true;
  form.querySelectorAll('input').forEach(input=>{
    input.dispatchEvent(new Event('input'));
    const pattern=patterns[input.name];
    if(pattern&&!pattern.test(input.value.trim())) valid=false;
  });
  if(!valid){e.preventDefault();alert('Проверьте корректность полей');}
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
