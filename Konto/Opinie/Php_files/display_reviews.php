<?php
$config = require __DIR__ . '../../../../secure/config.php';

try {
    $pdo = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе: " . $e->getMessage());
}

// Параметры
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$reviewsPerPage = 3;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $reviewsPerPage;

// Общее количество одобренных отзывов
$stmtTotal = $pdo->prepare("
    SELECT COUNT(*) 
    FROM opinie 
    WHERE id_produkta = :product_id AND Status = 'zatwierdzony'
");
$stmtTotal->execute([':product_id' => $product_id]);
$totalReviews = (int)$stmtTotal->fetchColumn();
$totalPages = ceil($totalReviews / $reviewsPerPage);

// Получаем отзывы для текущей страницы
$sql = "
    SELECT o.Tekst, o.Ilosc_gwiazdek, u.Imię, u.Nazwisko, u.Foto
    FROM opinie o
    JOIN Użytkownicy u ON o.id_klienta = u.id
    WHERE o.id_produkta = :product_id AND o.Status = 'zatwierdzony'
    ORDER BY o.id DESC
    OFFSET :offset ROWS
    FETCH NEXT :limit ROWS ONLY
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $reviewsPerPage, PDO::PARAM_INT);
$stmt->execute();

// Вывод отзывов
echo '<div class="reviews">';
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $avatarFolder = '../Konto/Moje dane/';
    $avatar = !empty($row['Foto']) ? $avatarFolder . $row['Foto'] : $avatarFolder . 'default_avatar.jpg';
    $name = htmlspecialchars($row['Imię'] . ' ' . $row['Nazwisko']);
    $text = htmlspecialchars($row['Tekst']);
    $textWithBreaks = nl2br($text);

    // Формируем звезды
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= ($i <= $row['Ilosc_gwiazdek']) 
            ? '<span class="star full">★</span>' 
            : '<span class="star empty">★</span>';
    }

    echo '<div class="review">';
    echo "<img src='{$avatar}' alt='Avatar' class='avatar'>";
    echo '<div class="review-content">';
    echo "<h3>{$name} <span class='stars'>{$stars}</span></h3>";
    echo "<div class='review-text'>{$textWithBreaks}</div>";
    echo '</div>';
    echo '</div>';
}
echo '</div>';

// Пагинация
if ($totalPages > 1) {
    echo '<div class="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $page) {
            echo "<span class='current'>{$i}</span>";
        } else {
            echo "<a href='?id={$product_id}&page={$i}' class='page-link'>{$i}</a>";
        }
    }
    echo '</div>';
}
?>
