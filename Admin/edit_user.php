<?php
session_start();

if (
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'admin'
) {
    header("Location: ../Index/Index.php");
    exit;
}

$config = require __DIR__ . '../../secure/config.php';

try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    if (!isset($_GET['id'])) {
        echo "Brak ID użytkownika.";
        exit();
    }

    $user_id = (int)$_GET['id'];

    $stmt = $conn->prepare(
        "SELECT id, Imię, Nazwisko, email, Rola FROM Użytkownicy WHERE id = :id"
    );
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "Nie znaleziono użytkownika.";
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first_name = trim($_POST['first_name']);
        $last_name  = trim($_POST['last_name']);

        if ($user_id == $_SESSION['user']['id']) {
            $email = $user['email'];
            $role  = $user['Rola'];
        } else {
            $email = trim($_POST['email']);
            $role  = $_POST['role'];
        }

        $update = $conn->prepare("
            UPDATE Użytkownicy
            SET Imię = :first_name,
                Nazwisko = :last_name,
                email = :email,
                Rola = :role
            WHERE id = :id
        ");

        $update->execute([
            ':first_name' => $first_name,
            ':last_name'  => $last_name,
            ':email'      => $email,
            ':role'       => $role,
            ':id'         => $user_id
        ]);

        header("Location: Pracowniki.php");
        exit();
    }

} catch (PDOException $e) {
    die("Błąd połączenia z MSSQL: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edycja Użytkownika</title>
    <style>
        .edit-form {
            max-width: 500px;
            margin: 100px auto;
            background: #4C7B68;
            padding: 20px;
            border-radius: 10px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }
        button {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
        }
        button:hover {
            background: #218838;
        }
    </style>
</head>
<body>
<script src="../runes-bg.js"></script>

<div class="edit-form">
    <h2>Edytuj użytkownika</h2>
    <form method="POST">
        <label>Imię:</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($user['Imię']) ?>" required>

        <label>Nazwisko:</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($user['Nazwisko']) ?>" required>

<label>Email:</label>
<?php if ($user_id == $_SESSION['user']['id']): ?>
    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
<?php else: ?>
    <input type="email" name="email" id="emailInput" value="<?= htmlspecialchars($user['email']) ?>" required>
    <p id="emailMessage" style="font-weight:bold; font-size:18px; margin-top:5px;"></p>
<?php endif; ?>



        <label>Rola:</label>
        <?php if ($user_id == $_SESSION['user']['id']): ?>
            <input type="text" value="Administrator" disabled>
        <?php else: ?>
            <select name="role">
                <option value="employee" <?= $user['Rola'] === 'employee' ? 'selected' : '' ?>>Pracownik</option>
                <option value="client" <?= $user['Rola'] === 'client' ? 'selected' : '' ?>>Użytkownik</option>
            </select>
        <?php endif; ?>

        <button type="submit">Zapisz zmiany</button>
    </form>
</div>

</body>

<script>
<?php if ($user_id != $_SESSION['user']['id']): ?>
const emailInput = document.getElementById('emailInput');
const emailMessage = document.getElementById('emailMessage');
const submitBtn = document.querySelector('button[type="submit"]');

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
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'email=' + encodeURIComponent(email)
        })
        .then(res => res.json())
        .then(data => {
            // Разрешаем текущий email пользователя
            if (email === "<?= $user['email'] ?>") {
                emailMessage.textContent = '';
                submitBtn.disabled = false;
                return;
            }

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
<?php endif; ?>
</script>


</html>
