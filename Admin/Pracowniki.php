<?php
session_start();

if (
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'admin'
) {
    header("Location: ../Index/Index.php");
    exit;
}




$config = require __DIR__ . '/../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Добавление нового пользователя
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
        $first_name = trim($_POST['first_name']);
        $last_name  = trim($_POST['last_name']);
        $email      = trim($_POST['email']);
        $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role       = $_POST['role'] ?? 'client';
        $created_at = date('Y-m-d H:i:s');

        // Проверка email
        $stmt = $conn->prepare("SELECT id FROM Użytkownicy WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $message = "Błąd: Użytkownik z takim emailem już istnieje.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO Użytkownicy (Imię, Nazwisko, email, Hasło, Rola, Data_utworzenia)
                VALUES (:first_name, :last_name, :email, :password, :role, :created_at)
            ");
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name'  => $last_name,
                ':email'      => $email,
                ':password'   => $password,
                ':role'       => $role,
                ':created_at' => $created_at
            ]);
            $message = "Nowy użytkownik został dodany.";
        }
    }

if (isset($_GET['delete_user_id'])) {
    $user_id = (int)$_GET['delete_user_id'];

    // Проверяем, существует ли пользователь
    $stmtCheck = $conn->prepare("SELECT id, Rola FROM Użytkownicy WHERE id = :id");
    $stmtCheck->execute([':id' => $user_id]);
    $user = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<script>
            alert('Użytkownik o podanym ID nie istnieje.');
            window.location.href='Pracowniki.php';
        </script>";
        exit();
    }

    // Нельзя удалить самого себя
    if ($user_id == $_SESSION['user']['id']) {
        echo "<script>
            alert('Nie możesz usunąć swojego własnego konta.');
            window.location.href='Pracowniki.php';
        </script>";
        exit();
    }

    // Нельзя удалить админа
    if ($user['Rola'] === 'admin') {
        echo "<script>
            alert('Nie można usunąć administratora.');
            window.location.href='Pracowniki.php';
        </script>";
        exit();
    }

    // Если все ок — удаляем
    $stmt = $conn->prepare("DELETE FROM Użytkownicy WHERE id = :id");
    $stmt->execute([':id' => $user_id]);

    echo "<script>
        alert('Użytkownik został usunięty.');
        window.location.href='Pracowniki.php';
    </script>";
    exit();
}




    // Получение всех пользователей
    $stmt = $conn->query("SELECT TOP (1000) id, Imię, Nazwisko, email, Rola, Data_utworzenia FROM Użytkownicy ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Błąd połączenia z MSSQL: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="styleindex.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Panel admina — Zarządzanie użytkownikami</title>
    <style>

        .Odstep
        {
            text-align: center;
        }
        /* Your existing styles */
        .user-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            color: black;
        }
        .user-table th, .user-table td {
            padding: 12px;
            border: 1px solid black;
            text-align: left;
        }
        .user-table th {
            background-color: #8D242E;
        }

        .user-table tr {
            background-color: #4C7B68; /* Bright Green */
        }

         .edit-btn {
            background-color: #4CAF50;
        }

        .delete-btn {
            background-color: #f44336;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .action-buttons a {
            padding: 6px 12px;
            background-color: #4C7B68;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .action-buttons a:hover {
            background-color: #3E614F;
        }
        .menu {
            display: flex;
            gap: 32px;
            margin-top: 300px;
            padding: 20px;
            border-radius: 10px;
            justify-content: center;
        }
        .menu-button {
            padding: 10px 20px;
            width: 200px;
            border: none;
            border-radius: 20px;
            background-color: #4C7B68;
            color: black;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        .menu-button.active {
            background-color: #7D121A;
            color: white;
        }
        .menu-button:hover {
            background-color: #3E614F;
        }

        #addUserForm {
            display: none; 
            margin-top: 20px;
        }
    </style>
    <script>
        function toggleAddUserForm() {
            var form = document.getElementById('addUserForm');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block'; 
            } else {
                form.style.display = 'none';   
            }
        }
    </script>
</head>
<body>
<script src="../runes-bg.js"></script>
<header>
    <a href="../Index/Index.php" class="logo-link">
        <div class="logo header-item">
            <img src="../FOTO/12.png" alt="Логотип">
            <span><span class="red">Dragon</span> <span class="green">World</span></span>
        </div>
    </a>
    <div class="dropdown header-item">
        <button class="transparent-button">Kategorie</button>
        <div class="dropdown-content">
            <a href="../Kategoria/kategoria.php?id=1">Figurki smoków</a>
            <a href="../Kategoria/kategoria.php?id=2">Biżuteria</a>
            <a href="../Kategoria/kategoria.php?id=3">Plakaty</a>
            <a href="../Kategoria/kategoria.php?id=4">Odzież</a>
            <a href="../Kategoria/kategoria.php?id=5">książki</a>
        </div>
    </div>
    <div class="search-container header-item">
        <input type="text" id="searchInput" placeholder="Wyszukaj w sklepie">
        <button id="searchButton" class="search-btn"><i class="fas fa-search"></i></button>
        <div id="search-dropdown" class="dropdown-content"></div>
    </div>
    <?php if (isset($_SESSION['user'])): ?>
        <div class="user-info header-item">
            <div class="profile-drop">
            <a href="AdminPanel.php">
                <?php echo htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']); ?>
            </a>
            <?php
              require_once dirname(__DIR__) . '/secure/profile_menu.php';
              dw_profile_menu('../Konto/Moje dane/Php_files');
            ?>
            </div>
            <a href="logout.php">Wyloguj się</a>
        </div>
    <?php else: ?>
        <a href="../../Regestracja and login/Php_files/logout.php" class="konto header-item">Konto</a>
    <?php endif; ?>
    <a href="../../Konto/Schowek/Schowek.php" class="serdce header-item"><i class="fas fa-heart" style="color: #8D242E;"></i></a>
    <a href="../../Konto/Koszyk/Koszyk.php" class="koszyk header-item">Koszyk</a>
</header>

<div class="menu">
    <a href="Edycja.php" class="menu-button">Edycja produktu</a>
    <a href="AdminPanel.php" class="menu-button">Zamówienia</a>
    <a href="Pracowniki.php" class="menu-button active">Zarządzania użytkownikami</a>
    <a href="opinie.php" class="menu-button ">Opinie</a>
</div>

<h2 class="Odstep"> Panel Administratora — Lista urzutkowników</h2>




<!-- Button to toggle the Add User Form -->
<button onclick="toggleAddUserForm()" class="menu-button">Dodaj nowego użytkownika</button>

<!-- Form to Add New User -->
<div id="addUserForm">
    <h2>Dodaj nowego użytkownika</h2>
    <form action="" method="POST">
        <input type="text" name="first_name" placeholder="Imię" required>
        <input type="text" name="last_name" placeholder="Nazwisko" required>
        <input type="email" name="email" id="emailInput" placeholder="Email" required>
        <input type="password" name="password" placeholder="Hasło" required>

        <label for="role">Rola użytkownika:</label>
        <select name="role" required>
            <option value="client">Użytkownik</option>
            <option value="employee">Pracownik</option>
        </select>

        <button type="submit" name="add_user">Dodaj</button>
    </form>
</div>

<p id="emailMessage" style="font-weight:bold; font-size:22px; margin-top:10px;"></p>



<div class="gggg">
    <table class="user-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imię</th>
                <th>Nazwisko</th>
                <th>Email</th>
                <th>Rola</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
<?php if (!empty($users)): ?>
    <?php foreach ($users as $row): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['Imię']); ?></td>
            <td><?php echo htmlspecialchars($row['Nazwisko']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td>
                <?php
                switch ($row['Rola']) {
                    case 'admin':
                        echo 'Administrator';
                        break;
                    case 'employee':
                        echo 'Pracownik';
                        break;
                    case 'client':
                    default:
                        echo 'Użytkownik';
                        break;
                }
                ?>
            </td>
            <td class="action-buttons">
                <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn edit-btn">Edytuj</a>
                <a href="?delete_user_id=<?php echo $row['id']; ?>" onclick="return confirm('Czy na pewno chcesz usunąć tego użytkownika?')">Usuń</a>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="6">Brak użytkowników.</td>
    </tr>
<?php endif; ?>
</tbody>

    </table>
</div>
<script src="../header-scroll.js"></script>
</body>

<script>
const emailInput = document.getElementById('emailInput');
const emailMessage = document.getElementById('emailMessage');
const submitBtn = document.querySelector('button[name="add_user"]');

let timer = null;

emailInput.addEventListener('input', () => {
    clearTimeout(timer);

    const email = emailInput.value.trim();

    if (email.length < 5) {
        emailMessage.textContent = '';
        submitBtn.disabled = false;
        return;
    }

    timer = setTimeout(() => {
        fetch('check_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'email=' + encodeURIComponent(email)
        })
        .then(res => res.json())
        .then(data => {
            if (data.exists) {
                emailMessage.textContent = 'Użytkownik z takim adresem e-mail już istnieje.';
                emailMessage.style.color = 'red';
                submitBtn.disabled = true;
            } else {
                emailMessage.textContent = 'Adres e-mail jest dostępny.';
                emailMessage.style.color = 'green';
                submitBtn.disabled = false;
            }
        });
    }, 400); 
});
</script>


</html>
