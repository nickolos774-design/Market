<?php
session_start();

if (!isset($_SESSION['user']['id'])) {
    header('Location: ../../../Regestracja and login/Html_files/Login.html');
    exit;
}

$user_id = (int) $_SESSION['user']['id'];
require_once dirname(__DIR__, 3) . '/secure/grywalizacja.php';

try {
    $pdo = dw_pdo();
} catch (PDOException $e) {
    die('Ошибка подключения');
}

$drzewa = [
    [
        'tytul' => 'Wojownik',
        'bonus' => 'Darmowa dostawa',
        'kolor' => '#1E5946',
        'galezie' => [
            [
                ['id' => 'Wojownik', 'otwarte' => true],
                ['id' => 'Paladyn', 'otwarte' => false],
                ['id' => 'Czempion', 'otwarte' => false],
            ],
            [
                ['id' => 'Wojownik', 'otwarte' => true],
                ['id' => 'Berserk', 'otwarte' => false],
                ['id' => 'Niszczyciel', 'otwarte' => false],
            ],
        ],
    ],
    [
        'tytul' => 'Mag',
        'bonus' => 'Zniżka na cyfrowe',
        'kolor' => '#3E614F',
        'galezie' => [
            [
                ['id' => 'Mag', 'otwarte' => true],
                ['id' => 'Arcymag', 'otwarte' => false],
            ],
        ],
    ],
    [
        'tytul' => 'Kupiec',
        'bonus' => 'Wyższy zwrot',
        'kolor' => '#7D121A',
        'galezie' => [
            [
                ['id' => 'Kupiec', 'otwarte' => true],
                ['id' => 'Tropiciel', 'otwarte' => true],
                ['id' => 'Zabójca', 'otwarte' => true],
            ],
        ],
    ],
];

$klasy = dw_klasy($pdo);
$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choose_class'])) {
    $info = dw_wybierz_klase($pdo, $user_id, (string) $_POST['choose_class']);
}

$postep = dw_postep($pdo, $user_id);
$_SESSION['klasa'] = $postep['klasa'];
$poziom = $postep['poziom'];
$punkty = $postep['punkty'];
$wybranaKlasa = $postep['klasa'];
$ranking = dw_ranking($pdo);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Klasy</title>
  <link href="https://fonts.googleapis.com/css2?family=Julee&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      background: rgba(169, 127, 130, 0.88);
      color: #fff;
      font-family: 'Julee', cursive;
    }
    .top {
      width: min(1100px, calc(100% - 24px));
      margin: 18px auto 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .top a {
      color: #fff;
      background: #1E5946;
      text-decoration: none;
      padding: 8px 14px;
      border-radius: 8px;
    }
    .trees {
      width: min(1100px, calc(100% - 24px));
      margin: 16px auto 32px;
      display: grid;
      grid-template-columns: 1.4fr 0.8fr 0.8fr;
      gap: 14px;
    }
    .tree {
      border-radius: 16px;
      padding: 16px 12px 22px;
      min-height: 460px;
    }
    .tree h2 {
      margin: 0;
      text-align: center;
      font-size: 28px;
    }
    .bonus {
      margin: 4px 0 16px;
      text-align: center;
      color: #f4ece8;
    }
    .branches {
      display: flex;
      justify-content: center;
      gap: 18px;
    }
    .branch {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .node {
      width: 92px;
      min-height: 72px;
      border: 3px solid #f4ece8;
      border-radius: 16px;
      background: rgba(0, 0, 0, 0.18);
      color: #fff;
      font-family: 'Julee', cursive;
      cursor: pointer;
    }
    .node small {
      display: block;
      font-size: 12px;
      color: #f4ece8;
    }
    .node.chosen {
      border-color: #D73435;
      background: #8D242E;
    }
    .node.locked,
    .node:disabled {
      opacity: 0.55;
      cursor: default;
    }
    .link {
      width: 3px;
      height: 22px;
      background: #f4ece8;
    }
    .note {
      width: min(1100px, calc(100% - 24px));
      margin: 0 auto 24px;
      color: #213922;
    }
    .board {
      width: min(1100px, calc(100% - 24px));
      margin: 0 auto 28px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    .board section {
      background: #1E5946;
      border-radius: 16px;
      padding: 14px 16px;
    }
    .board h2 { margin: 0 0 8px; font-size: 24px; }
    .board p, .board li { margin: 4px 0; }
    .badge {
      display: inline-block;
      margin: 4px 6px 0 0;
      padding: 4px 10px;
      border-radius: 999px;
      background: #8D242E;
    }
    @media (max-width: 800px) {
      .trees { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<script src="../../../runes-bg.js"></script>
  <div class="top">
    <a href="Moje dane.php">Wróć do danych</a>
    <span>Poziom <?= (int) $poziom ?> · Punkty <?= (int) $punkty ?></span>
  </div>
  <?php if ($info): ?><p class="note"><?= htmlspecialchars($info) ?></p><?php endif; ?>

  <form class="trees" method="post">
    <?php foreach ($drzewa as $drzewo): ?>
      <section class="tree" style="background: <?= htmlspecialchars($drzewo['kolor']) ?>;">
        <h2><?= htmlspecialchars($drzewo['tytul']) ?></h2>
        <p class="bonus"><?= htmlspecialchars($drzewo['bonus']) ?></p>
        <div class="branches">
          <?php foreach ($drzewo['galezie'] as $galaz): ?>
            <div class="branch">
              <?php foreach ($galaz as $i => $wezel): ?>
                <?php if ($i > 0): ?><span class="link"></span><?php endif; ?>
                <?php
                  $wiersz = $klasy[$wezel['id']];
                  $mozna = dw_mozna_klase($wiersz, $postep);
                  $napis = (int) $wiersz['Minimalny_poziom'] <= $poziom
                    ? ((int) $wiersz['Koszt_punktow'] . ' pkt')
                    : ('poziom ' . (int) $wiersz['Minimalny_poziom']);
                ?>
                <button
                  class="node<?= $wybranaKlasa === $wezel['id'] ? ' chosen' : '' ?><?= $mozna || $wybranaKlasa === $wezel['id'] ? '' : ' locked' ?>"
                  type="submit"
                  name="choose_class"
                  value="<?= htmlspecialchars($wezel['id']) ?>"
                  <?= $mozna ? '' : 'disabled' ?>
                >
                  <?= htmlspecialchars($wezel['id']) ?>
                  <small><?= htmlspecialchars($napis) ?></small>
                </button>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </form>

  <div class="board">
    <section id="odznaki">
      <h2>Odznaki</h2>
      <?php if (!$postep['odznaki']): ?>
        <p>Jeszcze żadnej odznaki.</p>
      <?php else: ?>
        <?php foreach ($postep['odznaki'] as $odznaka): ?>
          <span class="badge" title="<?= htmlspecialchars($odznaka['Opis']) ?>"><?= htmlspecialchars($odznaka['Nazwa']) ?></span>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if ($postep['konkurs']): ?>
        <p><?= htmlspecialchars($postep['konkurs']['Nazwa']) ?>: miejsce <?= (int) $postep['konkurs']['Miejsce'] ?>, <?= (int) $postep['konkurs']['Punkty'] ?> pkt</p>
      <?php endif; ?>
    </section>
    <section id="ranking">
      <h2>Ranking<?= $postep['miejsce'] ? ' · Twoje miejsce ' . (int) $postep['miejsce'] : '' ?></h2>
      <ol>
        <?php foreach ($ranking as $i => $gracz): ?>
          <li><?= htmlspecialchars($gracz['imie'] . ' ' . $gracz['Nazwisko']) ?> — <?= (int) $gracz['Xp'] ?> XP<?= $gracz['Klasa'] ? ' · ' . htmlspecialchars($gracz['Klasa']) : '' ?></li>
        <?php endforeach; ?>
      </ol>
    </section>
  </div>
</body>
</html>
