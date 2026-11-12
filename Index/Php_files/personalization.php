<?php

function dw_norm(string $text): string
{
    $text = mb_strtolower($text);
    return strtr($text, [
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l',
        'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ż' => 'z', 'ź' => 'z',
    ]);
}

function dw_fix_text(string $text): string
{
    $text = mb_strtolower($text);
    return strtr($text, [
        'kziążka' => 'książka',
        'ksiazka' => 'książka',
        'książkę' => 'książka',
        'ksiazki' => 'książki',
    ]);
}

function dw_category_keywords(): array
{
    return [
        'Książki' => [
            'książka', 'książki', 'czytanie', 'powieść', 'autor',
            'historia', 'gra o tron', 'ebook', 'fantasy książka', 'książka o smokach', 'fantasy',
        ],
        'Figurki smoków' => [
            'figurka', 'figurki', 'kolekcja', 'kolekcjonerskie', 'rpg',
            'rpg figurki', 'dekoracja fantasy', 'smocza figurka', 'smok',
        ],
        'Odzież' => ['koszulka', 'bluza', 'ubrania', 'tshirt', 'hoodie'],
        'Plakaty' => ['plakat', 'poster', 'ściana'],
        'Biżuteria' => ['naszyjnik', 'pierścień', 'bransoletka', 'biżuteria'],
    ];
}

function dw_boosts_from_texts(array $textsNewestFirst): array
{
    $boost = [];
    $count = count($textsNewestFirst);

    foreach ($textsNewestFirst as $index => $message) {
        $message = dw_norm(dw_fix_text((string) $message));
        $weight = $count - $index;

        foreach (dw_category_keywords() as $category => $words) {
            foreach ($words as $word) {
                if (str_contains($message, dw_norm($word))) {
                    $boost[$category] = ($boost[$category] ?? 0) + $weight;
                }
            }
        }
    }

    arsort($boost);
    return $boost;
}

function dw_chat_category_boost(array $textsNewestFirst, ?string $currentMessage = null): array
{
    $scaled = [];
    $rank = 0;
    foreach (dw_boosts_from_texts($textsNewestFirst) as $category => $weight) {
        if ($weight <= 0 || $rank >= 3) {
            break;
        }
        $scaled[$category] = (3 - $rank) * 8;
        $rank++;
    }

    if ($currentMessage !== null && trim($currentMessage) !== '') {
        foreach (dw_boosts_from_texts([$currentMessage]) as $category => $weight) {
            if ($weight > 0) {
                $scaled[$category] = ($scaled[$category] ?? 0) + 12;
            }
        }
    }

    return $scaled;
}

function dw_recency_factor($eventAt): float
{
    if ($eventAt instanceof DateTimeInterface) {
        $timestamp = $eventAt->getTimestamp();
    } else {
        $timestamp = strtotime((string) $eventAt);
    }

    if (!$timestamp) {
        return 1.0;
    }

    $days = max(0, (time() - $timestamp) / 86400);
    return exp(-$days / 21);
}

function dw_source_factor(?string $source): float
{
    switch (strtolower(trim((string) $source))) {
        case 'chat':
        case 'search':
            return 1.5;
        case 'slider':
            return 0.6;
        default:
            return 1.0;
    }
}

function dw_signal_weight(string $kind): float
{
    switch ($kind) {
        case 'purchase':
            return 10.0;
        case 'cart':
            return 6.0;
        case 'favorite':
            return 4.0;
        default:
            return 1.0;
    }
}

function dw_query_rows(PDO $conn, string $sql, array $params = []): array
{
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('personalization query failed: ' . $e->getMessage());
        return [];
    }
}

function dw_load_products(PDO $conn): array
{
    $rows = dw_query_rows($conn, "
        SELECT
            p.id,
            p.Nazwa AS name,
            p.Cena AS price,
            p.Opis AS description,
            p.Foto AS image,
            p.id_kategoria,
            k.Nazwa AS category_name
        FROM Produkty p
        LEFT JOIN kategoria k ON p.id_kategoria = k.id
    ");

    $products = [];
    foreach ($rows as $row) {
        $products[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'price' => $row['price'],
            'description' => $row['description'],
            'image' => $row['image'],
            'id_kategoria' => (int) $row['id_kategoria'],
            'category_name' => (string) ($row['category_name'] ?? ''),
        ];
    }

    return $products;
}

function dw_collect_signals(PDO $conn, int $userId): array
{
    $signals = [];

    foreach (dw_query_rows($conn, "
        SELECT zp.Id_produktu AS product_id, z.Data_utworzenia AS event_at
        FROM zamowienia_produkty zp
        INNER JOIN Zamowienia z ON z.Id = zp.[Id_zamówienia]
        WHERE z.Id_klienta = ?
    ", [$userId]) as $row) {
        $signals[] = [
            'product_id' => (int) $row['product_id'],
            'kind' => 'purchase',
            'event_at' => $row['event_at'],
            'source' => null,
        ];
    }

    foreach (dw_query_rows($conn, "
        SELECT Id_produkta AS product_id, Data_utworzenia AS event_at
        FROM koszyk
        WHERE Id_klienta = ?
    ", [$userId]) as $row) {
        $signals[] = [
            'product_id' => (int) $row['product_id'],
            'kind' => 'cart',
            'event_at' => $row['event_at'],
            'source' => null,
        ];
    }

    foreach (dw_query_rows($conn, "
        SELECT product_id, Data_utworzenia AS event_at
        FROM Ulubione_towary
        WHERE Id_klienta = ?
    ", [$userId]) as $row) {
        $signals[] = [
            'product_id' => (int) $row['product_id'],
            'kind' => 'favorite',
            'event_at' => $row['event_at'],
            'source' => null,
        ];
    }

    foreach (dw_query_rows($conn, "
        SELECT TOP 80 product_id, viewed_at AS event_at, source
        FROM viewed_products
        WHERE user_id = ?
        ORDER BY viewed_at DESC
    ", [$userId]) as $row) {
        $signals[] = [
            'product_id' => (int) $row['product_id'],
            'kind' => 'view',
            'event_at' => $row['event_at'],
            'source' => $row['source'] ?? null,
        ];
    }

    return $signals;
}

function dw_popularity(PDO $conn): array
{
    $popularity = [];
    foreach (dw_query_rows($conn, "
        SELECT Id_produktu AS product_id, COUNT(*) AS cnt
        FROM zamowienia_produkty
        GROUP BY Id_produktu
    ") as $row) {
        $popularity[(int) $row['product_id']] = (int) $row['cnt'];
    }
    return $popularity;
}

function dw_interleave(array $products): array
{
    usort($products, function ($a, $b) {
        $score = ($b['score'] <=> $a['score']);
        if ($score !== 0) {
            return $score;
        }
        return $b['id'] <=> $a['id'];
    });

    $result = [];
    $lastCategory = null;

    while ($products) {
        $pick = 0;
        if ($lastCategory !== null && count($products) > 1 && (int) $products[0]['id_kategoria'] === $lastCategory) {
            $limit = min(6, count($products) - 1);
            for ($i = 1; $i <= $limit; $i++) {
                if ((int) $products[$i]['id_kategoria'] !== $lastCategory) {
                    $pick = $i;
                    break;
                }
            }
        }

        $item = $products[$pick];
        array_splice($products, $pick, 1);
        $result[] = $item;
        $lastCategory = (int) $item['id_kategoria'];
    }

    return $result;
}

function dw_mix_catalog(array $interested, array $explore): array
{
    $interested = dw_interleave($interested);
    usort($explore, function ($a, $b) {
        $score = ($b['score'] <=> $a['score']);
        if ($score !== 0) {
            return $score;
        }
        return $b['id'] <=> $a['id'];
    });

    $merged = [];
    $i = 0;
    $e = 0;
    $interestedCount = count($interested);
    $exploreCount = count($explore);

    while ($i < $interestedCount || $e < $exploreCount) {
        for ($step = 0; $step < 7 && $i < $interestedCount; $step++) {
            $merged[] = $interested[$i++];
        }
        for ($step = 0; $step < 3 && $e < $exploreCount; $step++) {
            $merged[] = $explore[$e++];
        }
    }

    return $merged;
}

function dw_public_product(array $product): array
{
    return [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'description' => $product['description'],
        'image' => $product['image'],
        'photo' => $product['image'],
        'id_kategoria' => $product['id_kategoria'],
        'category_name' => $product['category_name'],
        'category' => $product['category_name'],
    ];
}

function dw_text_bonus(array $product, string $text): float
{
    $text = dw_norm(dw_fix_text($text));
    if ($text === '') {
        return 0.0;
    }

    $bonus = 0.0;
    $name = dw_norm((string) $product['name']);
    $description = dw_norm((string) $product['description']);

    foreach (explode(' ', $text) as $word) {
        if (mb_strlen($word) < 3) {
            continue;
        }
        if (str_contains($name, $word) || str_contains($description, $word)) {
            $bonus += 3;
        }
    }

    return $bonus;
}

/**
 * mode: catalog | recommend
 * categoryBoost: category name => extra points
 * text: current phrase, adds a small name/description bonus
 * limit: max items, null = all
 * exploreTail: how many items from untouched categories to append in recommend mode
 */
function dw_rank_products(PDO $conn, int $userId, array $options = []): array
{
    $mode = $options['mode'] ?? 'catalog';
    $limit = $options['limit'] ?? null;
    $categoryBoost = $options['categoryBoost'] ?? [];
    $text = trim((string) ($options['text'] ?? ''));
    $exploreTail = (int) ($options['exploreTail'] ?? ($mode === 'recommend' ? 1 : 0));

    $products = dw_load_products($conn);
    if (!$products) {
        return [];
    }

    $byId = [];
    foreach ($products as $product) {
        $byId[$product['id']] = $product;
    }

    $signals = $userId > 0 ? dw_collect_signals($conn, $userId) : [];
    $popularity = dw_popularity($conn);

    $productScore = [];
    $categoryScore = [];
    $actionRank = [];
    $purchased = [];
    $inCart = [];
    $seenProducts = [];
    $committed = [];

    foreach ($signals as $signal) {
        $productId = $signal['product_id'];
        if (!isset($byId[$productId])) {
            continue;
        }

        $points = dw_signal_weight($signal['kind'])
            * dw_recency_factor($signal['event_at'])
            * dw_source_factor($signal['source']);

        $productScore[$productId] = ($productScore[$productId] ?? 0) + $points;
        $categoryId = $byId[$productId]['id_kategoria'];
        $categoryScore[$categoryId] = ($categoryScore[$categoryId] ?? 0) + $points;
        $seenProducts[$productId] = true;

        $rank = 1;
        if ($signal['kind'] === 'favorite') {
            $rank = 2;
        } elseif ($signal['kind'] === 'cart') {
            $rank = 3;
            $inCart[$productId] = true;
            $committed[$productId] = true;
        } elseif ($signal['kind'] === 'purchase') {
            $rank = 4;
            $purchased[$productId] = true;
            $committed[$productId] = true;
        }
        if ($signal['kind'] === 'favorite') {
            $committed[$productId] = true;
        }
        $actionRank[$productId] = max($actionRank[$productId] ?? 0, $rank);
    }

    $warm = count($seenProducts) >= 4 || count($committed) >= 2;

    $boostByCategoryId = [];
    if ($categoryBoost) {
        foreach ($products as $product) {
            $name = $product['category_name'];
            if (isset($categoryBoost[$name])) {
                $boostByCategoryId[$product['id_kategoria']] = (float) $categoryBoost[$name];
            } else {
                foreach ($categoryBoost as $boostName => $points) {
                    if (dw_norm((string) $boostName) === dw_norm($name)) {
                        $boostByCategoryId[$product['id_kategoria']] = (float) $points;
                    }
                }
            }
        }
    }

    foreach ($products as &$product) {
        $id = $product['id'];
        $categoryId = $product['id_kategoria'];
        $direct = $productScore[$id] ?? 0;

        if (!$warm) {
            $product['score'] = (($popularity[$id] ?? 0) * 1.5) + ($id / 1000);
            if ($mode === 'recommend') {
                $product['score'] += (($boostByCategoryId[$categoryId] ?? 0) * 3) + dw_text_bonus($product, $text);
            }
            continue;
        }

        $chatBonus = $boostByCategoryId[$categoryId] ?? 0;
        if ($mode === 'recommend') {
            $chatBonus *= 3;
        }

        // Баллы поднимают категорию. Товар, с которым уже было действие,
        // остаётся позади других товаров этой категории.
        $categoryPart = (($categoryScore[$categoryId] ?? 0) * 2) + $chatBonus;
        $withinCategory = (($popularity[$id] ?? 0) * 0.01) + ($id * 0.000001);
        $lead = ($direct <= 0 && $categoryPart > 0) ? 1000 : 0;
        $sink = ($actionRank[$id] ?? 0) * 0.25;

        $product['score'] = $lead + $categoryPart + $withinCategory - $sink + dw_text_bonus($product, $text);
    }
    unset($product);

    if (!$warm) {
        usort($products, function ($a, $b) {
            $score = ($b['score'] <=> $a['score']);
            return $score !== 0 ? $score : ($b['id'] <=> $a['id']);
        });
        if ($mode === 'recommend' && $limit) {
            $products = array_slice($products, 0, (int) $limit);
        }
        return array_map('dw_public_product', $products);
    }

    if ($mode === 'recommend') {
        $personal = [];
        $explore = [];
        foreach ($products as $product) {
            if (isset($purchased[$product['id']]) || isset($inCart[$product['id']])) {
                continue;
            }
            $touched = ($productScore[$product['id']] ?? 0) > 0
                || ($categoryScore[$product['id_kategoria']] ?? 0) > 0
                || ($boostByCategoryId[$product['id_kategoria']] ?? 0) > 0;
            if ($touched) {
                $personal[] = $product;
            } else {
                $explore[] = $product;
            }
        }

        $personal = dw_interleave($personal);
        usort($explore, function ($a, $b) {
            $score = ($b['score'] <=> $a['score']);
            return $score !== 0 ? $score : ($b['id'] <=> $a['id']);
        });

        $takePersonal = $limit ? max(1, (int) $limit - max(0, $exploreTail)) : count($personal);
        $chosen = array_slice($personal, 0, $takePersonal);
        $chosenIds = array_column($chosen, 'id');

        $tail = [];
        foreach ($explore as $product) {
            if (in_array($product['id'], $chosenIds, true)) {
                continue;
            }
            $tail[] = $product;
            if (count($tail) >= $exploreTail) {
                break;
            }
        }

        $ranked = array_merge($chosen, $tail);
        if ($limit && count($ranked) < (int) $limit) {
            foreach (array_merge($personal, $explore) as $product) {
                if (in_array($product['id'], array_column($ranked, 'id'), true)) {
                    continue;
                }
                $ranked[] = $product;
                if (count($ranked) >= (int) $limit) {
                    break;
                }
            }
        }

        return array_map('dw_public_product', $limit ? array_slice($ranked, 0, (int) $limit) : $ranked);
    }

    $interested = [];
    $explore = [];
    foreach ($products as $product) {
        $touched = ($productScore[$product['id']] ?? 0) > 0
            || ($categoryScore[$product['id_kategoria']] ?? 0) > 0
            || ($boostByCategoryId[$product['id_kategoria']] ?? 0) > 0;
        if ($touched) {
            $interested[] = $product;
        } else {
            $explore[] = $product;
        }
    }

    $ranked = dw_mix_catalog($interested, $explore);
    if ($limit) {
        $ranked = array_slice($ranked, 0, (int) $limit);
    }

    return array_map('dw_public_product', $ranked);
}
