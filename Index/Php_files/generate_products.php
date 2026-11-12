<?php

function updateProductsJson($conn) {

    try {
        $stmt = $conn->query("
    SELECT 
        p.id,
        p.Nazwa,
        p.Cena,
        p.Opis,
        p.Foto,
        p.id_kategoria,
        k.Nazwa AS category_name
    FROM Produkty p
    LEFT JOIN kategoria k ON p.id_kategoria = k.id
");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $products = [];

        foreach ($rows as $row) {
            $products[] = [
                "id" => $row["id"],
                "name" => $row["Nazwa"],
                "price" => $row["Cena"],
                "description" => $row["Opis"],
                "image" => $row["Foto"],
                "category_id" => $row["id_kategoria"],
                "category_name" => $row["category_name"]
            ];
        }

        file_put_contents(
            __DIR__ . "/products.json",
            json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );

        return count($products);

    } catch (Exception $e) {
        return false;
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {

    header("Content-Type: text/plain; charset=utf-8");

    $configPath = __DIR__ . '/../../secure/config.php';

    if (!file_exists($configPath)) {
        die("Nie znaleziono config.php");
    }

    $config = require $configPath;

    try {
        $conn = new PDO(
            "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
            $config['username'],
            $config['password']
        );

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    } catch (PDOException $e) {
        die("Błąd DB: " . $e->getMessage());
    }

    $count = updateProductsJson($conn);

    if ($count !== false) {
        echo "Zapisano products.json\n";
        echo "Ilość produktów: " . $count;
    } else {
        echo "Błąd generowania JSON";
    }
}
