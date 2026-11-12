<?php

function dw_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $pdo = new PDO(
        "sqlsrv:Server={$config['server']};Database={$config['database']};Encrypt=false;TrustServerCertificate=true",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    return $pdo;
}

function dw_ensure(PDO $pdo): void
{
    static $ready = false;
    if ($ready || $pdo->inTransaction()) {
        return;
    }

    $exists = $pdo->query("SELECT OBJECT_ID('dbo.Postep', 'U')")->fetchColumn();
    if (!$exists) {
        $pdo->exec("
            CREATE TABLE Poziomy (
                Numer int NOT NULL PRIMARY KEY,
                Xp_wymagane int NOT NULL,
                Punkty_klasy int NOT NULL
            );

            CREATE TABLE Postep (
                Id_uzytkownika int NOT NULL PRIMARY KEY,
                Xp int NOT NULL CONSTRAINT DF_Postep_Xp DEFAULT 0,
                Data_aktualizacji datetime2 NOT NULL CONSTRAINT DF_Postep_Data DEFAULT SYSUTCDATETIME(),
                CONSTRAINT FK_Postep_Uzytkownik FOREIGN KEY (Id_uzytkownika) REFERENCES [Użytkownicy](id)
            );

            CREATE TABLE Zdarzenia_xp (
                Id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
                Id_uzytkownika int NOT NULL,
                Zrodlo nvarchar(40) NOT NULL,
                Id_zrodla int NOT NULL,
                Xp int NOT NULL,
                Data_utworzenia datetime2 NOT NULL CONSTRAINT DF_Zdarzenia_xp_Data DEFAULT SYSUTCDATETIME(),
                CONSTRAINT FK_Zdarzenia_xp_Uzytkownik FOREIGN KEY (Id_uzytkownika) REFERENCES [Użytkownicy](id),
                CONSTRAINT UQ_Zdarzenia_xp UNIQUE (Id_uzytkownika, Zrodlo, Id_zrodla)
            );

            CREATE TABLE Klasy (
                Id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
                Nazwa nvarchar(80) NOT NULL CONSTRAINT UQ_Klasy_Nazwa UNIQUE,
                Drzewo nvarchar(40) NOT NULL,
                Nazwa_rodzica nvarchar(80) NULL,
                Koszt_punktow int NOT NULL,
                Bonus nvarchar(120) NOT NULL,
                Minimalny_poziom int NOT NULL,
                Startowa bit NOT NULL,
                Kolejnosc int NOT NULL
            );

            CREATE TABLE Wybor_klasy (
                Id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
                Id_uzytkownika int NOT NULL,
                Nazwa_klasy nvarchar(80) NOT NULL,
                Punkty int NOT NULL,
                Data_wyboru datetime2 NOT NULL CONSTRAINT DF_Wybor_klasy_Data DEFAULT SYSUTCDATETIME(),
                CONSTRAINT FK_Wybor_klasy_Uzytkownik FOREIGN KEY (Id_uzytkownika) REFERENCES [Użytkownicy](id)
            );

            CREATE TABLE Odznaki (
                Id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
                Kod nvarchar(40) NOT NULL CONSTRAINT UQ_Odznaki_Kod UNIQUE,
                Nazwa nvarchar(80) NOT NULL,
                Opis nvarchar(200) NOT NULL
            );

            CREATE TABLE Odznaki_uzytkownikow (
                Id_uzytkownika int NOT NULL,
                Id_odznaki int NOT NULL,
                Data_zdobycia datetime2 NOT NULL CONSTRAINT DF_Odznaki_uzytkownikow_Data DEFAULT SYSUTCDATETIME(),
                CONSTRAINT PK_Odznaki_uzytkownikow PRIMARY KEY (Id_uzytkownika, Id_odznaki),
                CONSTRAINT FK_Odznaki_uzytkownikow_Uzytkownik FOREIGN KEY (Id_uzytkownika) REFERENCES [Użytkownicy](id),
                CONSTRAINT FK_Odznaki_uzytkownikow_Odznaka FOREIGN KEY (Id_odznaki) REFERENCES Odznaki(Id)
            );

            CREATE TABLE Konkursy (
                Id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
                Nazwa nvarchar(120) NOT NULL,
                Opis nvarchar(400) NOT NULL,
                Data_start datetime2 NOT NULL,
                Data_koniec datetime2 NOT NULL,
                Aktywny bit NOT NULL,
                Nagroda nvarchar(200) NOT NULL
            );

            CREATE TABLE Udzial_konkursy (
                Id_konkursu int NOT NULL,
                Id_uzytkownika int NOT NULL,
                Punkty int NOT NULL CONSTRAINT DF_Udzial_konkursy_Punkty DEFAULT 0,
                Data_udzialu datetime2 NOT NULL CONSTRAINT DF_Udzial_konkursy_Data DEFAULT SYSUTCDATETIME(),
                CONSTRAINT PK_Udzial_konkursy PRIMARY KEY (Id_konkursu, Id_uzytkownika),
                CONSTRAINT FK_Udzial_konkursy_Konkurs FOREIGN KEY (Id_konkursu) REFERENCES Konkursy(Id),
                CONSTRAINT FK_Udzial_konkursy_Uzytkownik FOREIGN KEY (Id_uzytkownika) REFERENCES [Użytkownicy](id)
            );
        ");

        $poziom = $pdo->prepare('INSERT INTO Poziomy (Numer, Xp_wymagane, Punkty_klasy) VALUES (?, ?, 1)');
        for ($numer = 1; $numer <= 15; $numer++) {
            $poziom->execute([$numer, ($numer - 1) * 100]);
        }

        $klasa = $pdo->prepare('
            INSERT INTO Klasy (Nazwa, Drzewo, Nazwa_rodzica, Koszt_punktow, Bonus, Minimalny_poziom, Startowa, Kolejnosc)
            VALUES (?, ?, ?, 1, ?, ?, ?, ?)
        ');
        $klasy = [
            ['Wojownik', 'Wojownik', null, 'Darmowa dostawa', 1, 1, 1],
            ['Paladyn', 'Wojownik', 'Wojownik', 'Darmowa dostawa', 3, 0, 2],
            ['Czempion', 'Wojownik', 'Paladyn', 'Darmowa dostawa', 5, 0, 3],
            ['Berserk', 'Wojownik', 'Wojownik', 'Darmowa dostawa', 3, 0, 4],
            ['Niszczyciel', 'Wojownik', 'Berserk', 'Darmowa dostawa', 5, 0, 5],
            ['Mag', 'Mag', null, 'Zniżka na cyfrowe', 1, 1, 1],
            ['Arcymag', 'Mag', 'Mag', 'Zniżka na cyfrowe', 4, 0, 2],
            ['Kupiec', 'Kupiec', null, 'Wyższy zwrot', 1, 1, 1],
            ['Tropiciel', 'Kupiec', 'Kupiec', 'Wyższy zwrot', 1, 1, 2],
            ['Zabójca', 'Kupiec', 'Tropiciel', 'Wyższy zwrot', 1, 1, 3],
        ];
        foreach ($klasy as $wiersz) {
            $klasa->execute($wiersz);
        }

        $odznaka = $pdo->prepare('INSERT INTO Odznaki (Kod, Nazwa, Opis) VALUES (?, ?, ?)');
        foreach ([
            ['pierwsze_zamowienie', 'Pierwszy łup', 'Złożone pierwsze zamówienie'],
            ['recenzent', 'Kronikarz', 'Opublikowana opinia'],
            ['kolekcjoner', 'Strażnik skarbca', 'Pięć towarów w schowku'],
            ['poziom_3', 'Wędrowiec', 'Osiągnięty poziom 3'],
            ['poziom_5', 'Smoczy rycerz', 'Osiągnięty poziom 5'],
        ] as $wiersz) {
            $odznaka->execute($wiersz);
        }

        $pdo->prepare('
            INSERT INTO Konkursy (Nazwa, Opis, Data_start, Data_koniec, Aktywny, Nagroda)
            VALUES (?, ?, SYSUTCDATETIME(), DATEADD(day, 30, SYSUTCDATETIME()), 1, ?)
        ')->execute([
            'Polowanie na smoki',
            'Ranking punktów doświadczenia zdobytych w czasie konkursu.',
            'Odznaka na profilu i miejsce w rankingu',
        ]);

        dw_backfill($pdo);
    }

    $ready = true;
}

function dw_backfill(PDO $pdo): void
{
    $uzytkownicy = $pdo->query('SELECT id FROM [Użytkownicy]')->fetchAll(PDO::FETCH_COLUMN);
    $zamowienia = $pdo->prepare('SELECT Id FROM Zamowienia WHERE Id_klienta = ?');

    foreach ($uzytkownicy as $id) {
        $id = (int) $id;
        $zamowienia->execute([$id]);
        foreach ($zamowienia->fetchAll(PDO::FETCH_COLUMN) as $zamowienieId) {
            dw_award_xp($pdo, $id, 'zamowienie', (int) $zamowienieId, 100);
        }
        dw_touch_postep($pdo, $id);
        dw_sync_badges($pdo, $id);
    }
}

function dw_touch_postep(PDO $pdo, int $userId): void
{
    $pdo->prepare('
        IF NOT EXISTS (SELECT 1 FROM Postep WHERE Id_uzytkownika = ?)
        INSERT INTO Postep (Id_uzytkownika, Xp) VALUES (?, 0)
    ')->execute([$userId, $userId]);
}

function dw_award_xp(PDO $pdo, int $userId, string $source, int $sourceId, int $xp): void
{
    if (!$pdo->inTransaction()) {
        dw_ensure($pdo);
    }
    dw_touch_postep($pdo, $userId);

    $istnieje = $pdo->prepare('SELECT 1 FROM Zdarzenia_xp WHERE Id_uzytkownika = ? AND Zrodlo = ? AND Id_zrodla = ?');
    $istnieje->execute([$userId, $source, $sourceId]);
    if ($istnieje->fetchColumn()) {
        return;
    }

    $pdo->prepare('INSERT INTO Zdarzenia_xp (Id_uzytkownika, Zrodlo, Id_zrodla, Xp) VALUES (?, ?, ?, ?)')
        ->execute([$userId, $source, $sourceId, $xp]);
    $pdo->prepare('UPDATE Postep SET Xp = Xp + ?, Data_aktualizacji = SYSUTCDATETIME() WHERE Id_uzytkownika = ?')
        ->execute([$xp, $userId]);

    dw_sync_badges($pdo, $userId);
    dw_contest_add($pdo, $userId, $xp);
}

function dw_contest_add(PDO $pdo, int $userId, int $xp): void
{
    $konkurs = $pdo->query('
        SELECT TOP 1 Id FROM Konkursy
        WHERE Aktywny = 1 AND SYSUTCDATETIME() BETWEEN Data_start AND Data_koniec
        ORDER BY Id DESC
    ')->fetchColumn();
    if (!$konkurs) {
        return;
    }

    $pdo->prepare('
        IF EXISTS (SELECT 1 FROM Udzial_konkursy WHERE Id_konkursu = ? AND Id_uzytkownika = ?)
            UPDATE Udzial_konkursy SET Punkty = Punkty + ? WHERE Id_konkursu = ? AND Id_uzytkownika = ?
        ELSE
            INSERT INTO Udzial_konkursy (Id_konkursu, Id_uzytkownika, Punkty) VALUES (?, ?, ?)
    ')->execute([$konkurs, $userId, $xp, $konkurs, $userId, $konkurs, $userId, $xp]);
}

function dw_sync_badges(PDO $pdo, int $userId): void
{
    $poziom = dw_poziom($pdo, $userId);
    $licznik = $pdo->prepare('SELECT COUNT(*) FROM Zamowienia WHERE Id_klienta = ?');
    $licznik->execute([$userId]);
    $zamowienia = (int) $licznik->fetchColumn();

    $licznik = $pdo->prepare("SELECT COUNT(*) FROM opinie WHERE id_klienta = ? AND Status = 'zatwierdzony'");
    $licznik->execute([$userId]);
    $opinie = (int) $licznik->fetchColumn();

    $licznik = $pdo->prepare('SELECT COUNT(*) FROM Ulubione_towary WHERE Id_klienta = ?');
    $licznik->execute([$userId]);
    $schowek = (int) $licznik->fetchColumn();

    $warunki = [
        'pierwsze_zamowienie' => $zamowienia >= 1,
        'recenzent' => $opinie >= 1,
        'kolekcjoner' => $schowek >= 5,
        'poziom_3' => $poziom >= 3,
        'poziom_5' => $poziom >= 5,
    ];

    $wstaw = $pdo->prepare('
        INSERT INTO Odznaki_uzytkownikow (Id_uzytkownika, Id_odznaki)
        SELECT ?, Id FROM Odznaki WHERE Kod = ?
        AND NOT EXISTS (
            SELECT 1 FROM Odznaki_uzytkownikow ou
            JOIN Odznaki o ON o.Id = ou.Id_odznaki
            WHERE ou.Id_uzytkownika = ? AND o.Kod = ?
        )
    ');
    foreach ($warunki as $kod => $spełniony) {
        if ($spełniony) {
            $wstaw->execute([$userId, $kod, $userId, $kod]);
        }
    }
}

function dw_poziom(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('
        SELECT MAX(po.Numer)
        FROM Poziomy po
        JOIN Postep p ON p.Xp >= po.Xp_wymagane
        WHERE p.Id_uzytkownika = ?
    ');
    $stmt->execute([$userId]);
    return max(1, (int) $stmt->fetchColumn());
}

function dw_klasy(PDO $pdo): array
{
    dw_ensure($pdo);
    $mapa = [];
    foreach ($pdo->query('SELECT Nazwa, Drzewo, Nazwa_rodzica, Koszt_punktow, Bonus, Minimalny_poziom, Startowa, Kolejnosc FROM Klasy') as $wiersz) {
        $mapa[$wiersz['Nazwa']] = $wiersz;
    }
    return $mapa;
}

function dw_postep(PDO $pdo, int $userId): array
{
    dw_ensure($pdo);
    dw_touch_postep($pdo, $userId);
    dw_sync_badges($pdo, $userId);

    $xpStmt = $pdo->prepare('SELECT Xp FROM Postep WHERE Id_uzytkownika = ?');
    $xpStmt->execute([$userId]);
    $xp = (int) $xpStmt->fetchColumn();
    $poziom = dw_poziom($pdo, $userId);

    $nastepny = $pdo->prepare('SELECT Xp_wymagane FROM Poziomy WHERE Numer = ?');
    $nastepny->execute([$poziom + 1]);
    $progNastepnego = $nastepny->fetchColumn();
    $progObecny = ($poziom - 1) * 100;
    $xpWPoziomie = $xp - $progObecny;
    $xpDoNastepnego = $progNastepnego === false ? 0 : ((int) $progNastepnego - $progObecny);

    $przyznaneStmt = $pdo->prepare('SELECT SUM(Punkty_klasy) FROM Poziomy WHERE Numer <= ?');
    $przyznaneStmt->execute([$poziom]);
    $otrzymane = (int) $przyznaneStmt->fetchColumn();

    $wydaneStmt = $pdo->prepare('SELECT ISNULL(SUM(Punkty), 0) FROM Wybor_klasy WHERE Id_uzytkownika = ?');
    $wydaneStmt->execute([$userId]);
    $wydane = (int) $wydaneStmt->fetchColumn();

    $klasaStmt = $pdo->prepare('SELECT Klasa FROM [Użytkownicy] WHERE id = ?');
    $klasaStmt->execute([$userId]);
    $klasa = (string) ($klasaStmt->fetchColumn() ?: '');

    $miejsceStmt = $pdo->prepare("
        SELECT COUNT(*) + 1
        FROM Postep p
        JOIN [Użytkownicy] u ON u.id = p.Id_uzytkownika
        WHERE u.Rola = 'client' AND p.Xp > (SELECT Xp FROM Postep WHERE Id_uzytkownika = ?)
    ");
    $miejsceStmt->execute([$userId]);
    $rolaStmt = $pdo->prepare('SELECT Rola FROM [Użytkownicy] WHERE id = ?');
    $rolaStmt->execute([$userId]);
    $miejsce = $rolaStmt->fetchColumn() === 'client' ? (int) $miejsceStmt->fetchColumn() : null;

    $odznakiStmt = $pdo->prepare('
        SELECT o.Nazwa, o.Opis
        FROM Odznaki_uzytkownikow ou
        JOIN Odznaki o ON o.Id = ou.Id_odznaki
        WHERE ou.Id_uzytkownika = ?
        ORDER BY ou.Data_zdobycia, o.Id
    ');
    $odznakiStmt->execute([$userId]);

    $konkursStmt = $pdo->prepare('
        SELECT k.Nazwa, u.Punkty,
            (SELECT COUNT(*) + 1 FROM Udzial_konkursy x WHERE x.Id_konkursu = u.Id_konkursu AND x.Punkty > u.Punkty) AS Miejsce
        FROM Udzial_konkursy u
        JOIN Konkursy k ON k.Id = u.Id_konkursu
        WHERE u.Id_uzytkownika = ? AND k.Aktywny = 1
    ');
    $konkursStmt->execute([$userId]);
    $konkurs = $konkursStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    return [
        'xp' => $xp,
        'poziom' => $poziom,
        'xp_w_poziomie' => max(0, $xpWPoziomie),
        'xp_do_nastepnego' => (int) $xpDoNastepnego,
        'punkty' => max(0, $otrzymane - $wydane),
        'punkty_otrzymane' => $otrzymane,
        'klasa' => $klasa,
        'miejsce' => $miejsce,
        'odznaki' => $odznakiStmt->fetchAll(PDO::FETCH_ASSOC),
        'konkurs' => $konkurs,
    ];
}

function dw_ranking(PDO $pdo, int $limit = 5): array
{
    dw_ensure($pdo);
    $stmt = $pdo->prepare("
        SELECT TOP ($limit) u.id, u.[Imię] AS imie, u.Nazwisko, u.Klasa, p.Xp
        FROM Postep p
        JOIN [Użytkownicy] u ON u.id = p.Id_uzytkownika
        WHERE u.Rola = 'client'
        ORDER BY p.Xp DESC, u.id
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function dw_mozna_klase(array $klasa, array $postep): bool
{
    if ($postep['poziom'] < (int) $klasa['Minimalny_poziom'] || $postep['punkty'] < (int) $klasa['Koszt_punktow']) {
        return false;
    }
    if ($postep['klasa'] === '') {
        return (bool) $klasa['Startowa'];
    }
    return (string) $klasa['Nazwa_rodzica'] === $postep['klasa'];
}

function dw_wybierz_klase(PDO $pdo, int $userId, string $nazwa): string
{
    dw_ensure($pdo);
    $klasy = dw_klasy($pdo);
    if (!isset($klasy[$nazwa])) {
        return 'Ta gałąź jest jeszcze zamknięta.';
    }

    $postep = dw_postep($pdo, $userId);
    $klasa = $klasy[$nazwa];
    if ($postep['klasa'] === $nazwa) {
        return 'To już Twoja klasa.';
    }
    if ($postep['poziom'] < (int) $klasa['Minimalny_poziom']) {
        return 'Ta gałąź jest jeszcze zamknięta.';
    }
    if (!dw_mozna_klase($klasa, $postep)) {
        return $postep['punkty'] < (int) $klasa['Koszt_punktow']
            ? 'Brak punktów.'
            : 'Tej klasy nie można teraz wybrać.';
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE [Użytkownicy] SET Klasa = ? WHERE id = ?')->execute([$nazwa, $userId]);
        $pdo->prepare('INSERT INTO Wybor_klasy (Id_uzytkownika, Nazwa_klasy, Punkty) VALUES (?, ?, ?)')
            ->execute([$userId, $nazwa, (int) $klasa['Koszt_punktow']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return 'Nie udało się zapisać klasy.';
    }

    return 'Wybrano: ' . $nazwa;
}
