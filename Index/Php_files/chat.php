<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

$config = require __DIR__ . '/../../secure/config.php';
require_once __DIR__ . '/personalization.php';

// ================= DB =================
try {
    $conn = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["reply" => "DB error", "products" => []]);
    exit;
}

// ================= USER =================
$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
    echo json_encode(["reply" => "Nie zalogowany", "products" => []]);
    exit;
}

// ================= INPUT =================
$data = json_decode(file_get_contents("php://input"), true);
$userMessage = trim($data['message'] ?? '');

if (!$userMessage) {
    echo json_encode(["reply" => "Pusta wiadomość", "products" => []]);
    exit;
}

// ================= SESSION =================
if (!isset($_SESSION['chat_session_id'])) {
    $stmt = $conn->prepare("INSERT INTO SesjeCzatu (user_id) VALUES (?)");
    $stmt->execute([$userId]);
    $_SESSION['chat_session_id'] = $conn->lastInsertId();
}

$sessionId = $_SESSION['chat_session_id'];

// ================= SAVE USER =================
$stmt = $conn->prepare("
    INSERT INTO Wiadomości (sesja_id, Nadawca, Tekst)
    VALUES (?, 'użytkownik', ?)
");
$stmt->execute([$sessionId, $userMessage]);

// ================= LOAD PRODUCTS =================
$products = dw_load_products($conn);

// ================= NORMALIZE =================
function norm($t) {
    $t = mb_strtolower($t);
    return strtr($t, [
        "ą"=>"a","ć"=>"c","ę"=>"e","ł"=>"l",
        "ń"=>"n","ó"=>"o","ś"=>"s","ż"=>"z","ź"=>"z"
    ]);
}

// ================= FIX TYPO =================
function fix($t) {
    return strtr($t, [
        "kziążka"=>"książka",
        "ksiazka"=>"książka",
        "książkę"=>"książka",
        "ksiazki"=>"książki"
    ]);
}

$userMessage = fix($userMessage);
$q = norm($userMessage);

/// ================= CHAT HISTORY =================

$stmt = $conn->prepare("
    SELECT TOP 10 Tekst
    FROM Wiadomości
    WHERE sesja_id = ?
    AND Nadawca = 'użytkownik'
    ORDER BY id DESC
");

$stmt->execute([$sessionId]);

$historyMessages = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ================= ANALYZE INTERESTS =================
$chatBoost = dw_chat_category_boost($historyMessages, $userMessage);
$preferredCategories = array_slice(array_keys($chatBoost), 0, 3);

$ranked = dw_rank_products($conn, (int) $userId, [
    'mode' => 'catalog',
    'categoryBoost' => $chatBoost,
    'text' => $userMessage,
]);

if ($ranked) {
    $products = $ranked;
}


// ================= DETECT CATEGORY =================

$categoryMatch = null;

foreach ($products as $p) {
    if (str_contains($q, norm($p["category_name"]))) {
        $categoryMatch = $p["category_name"];
        break;
    }
}



// ================= SEARCH =================
$categoryMatched = [];
$textMatched = [];

$order = 0;
foreach ($products as $p) {

    $name = norm($p["name"]);
    $desc = norm($p["description"]);
    $cat  = norm($p["category_name"]);
    $p["_order"] = $order++;

    // 🔥 если есть категория → ищем только её
    if ($categoryMatch) {
        if ($cat === norm($categoryMatch)) {
            $categoryMatched[] = $p;
        }
        continue;
    }

    // 🔎 текстовый поиск
    $score = 0;

    foreach (explode(" ", $q) as $w) {
        if (mb_strlen($w) < 3) continue;

        if (str_contains($name, $w) || str_contains($desc, $w)) {
            $score++;
        }
    }

    if ($score > 0) {
        $p["score"] = $score;
        $textMatched[] = $p;
    }
}

usort($textMatched, function ($a, $b) {
    if ($a["score"] !== $b["score"]) {
        return $b["score"] <=> $a["score"];
    }
    return $a["_order"] <=> $b["_order"];
});

// ================= DECISION =================
if ($categoryMatch) {

    if (!empty($categoryMatched)) {
        $final = array_slice($categoryMatched, 0, 2);
        $replyIntro = "Znalazłem produkty z tej kategorii 👇";
    } else {
        echo json_encode([
            "reply" => "Niestety nie mamy produktów w tej kategorii.",
            "products" => []
        ]);
        exit;
    }

} elseif (!empty($textMatched)) {

    $final = array_slice($textMatched, 0, 2);
    $replyIntro = "Oto produkty pasujące do Twojego zapytania 👇";

} else {

    $final = dw_rank_products($conn, (int) $userId, [
        'mode' => 'recommend',
        'limit' => 4,
        'categoryBoost' => $chatBoost,
        'text' => $userMessage,
        'exploreTail' => 1,
    ]);

    if (!empty($preferredCategories) || !empty($chatBoost)) {
        $replyIntro = "Polecam produkty na podstawie Twoich zainteresowań 👇";
    } else {
        $replyIntro = "Nie znalazłem dokładnie tego, czego szukasz, ale mogę polecić coś takiego 👇";
    }
}

// ================= BUILD CONTEXT =================
$productText = "Produkty:\n";

foreach ($final as $p) {
    $productText .= "- {$p['name']} | {$p['price']} zł | {$p['description']}\n";
}

// ================= AI =================
$apiKey = $config['API_KEY'];

$postData = [
    "model" => "nvidia/nemotron-3.5-super-vl:free",
    "messages" => [
        [
            "role" => "system",
            "content" =>
"Jesteś asystentem sklepu.

Zasady:
- używaj tylko podanych produktów
- nie wymyślaj
- odpowiadaj naturalnie
- krótko
- jeśli produkty nie pasują idealnie, wybierz tylko spośród listy
- nie dodawaj nowych produktów
- nie mieszaj kategorii bez potrzeby
- nie rekomenduj losowych produktów jako głównych, tylko jeśli brak innych opcji"
        ],
        [
            "role" => "user",
            "content" => "$replyIntro\n\n$productText"
        ]
    ]
];

// ================= REQUEST =================
$ch = curl_init("https://openrouter.ai/api/v1/chat/completions");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

$aiReply = $result["choices"][0]["message"]["content"] ?? $replyIntro;

function normalizePhotoPath($path) {
    $path = str_replace('\\', '/', (string)$path);
    $path = ltrim($path, '/');

    if (preg_match('#FOTO/(.+)$#i', $path, $matches)) {
        return '../../FOTO/' . $matches[1];
    }

    return $path;
}

// ================= OUTPUT =================
$productsOut = [];

foreach ($final as $p) {
    $productsOut[] = [
        "id" => $p["id"],
        "name" => $p["name"],
        "price" => $p["price"],
        "description" => $p["description"],
        "photo" => normalizePhotoPath($p["image"]),
        "category" => $p["category_name"]
    ];
}

// ================= SAVE =================
$stmt = $conn->prepare("
    INSERT INTO Wiadomości (sesja_id, Nadawca, Tekst, Produkty)
    VALUES (?, 'AI', ?, ?)
");

$stmt->execute([
    $sessionId,
    $aiReply,
    json_encode($productsOut, JSON_UNESCAPED_UNICODE)
]);

// ================= RESPONSE =================
echo json_encode([
    "reply" => $aiReply,
    "products" => $productsOut
], JSON_UNESCAPED_UNICODE);

$_SESSION['chat_updated'] = time();
