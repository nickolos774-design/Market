<?php

function dw_profile_menu(string $base, string $logout = '', bool $withLevel = true): void
{
    static $styled = false;
    if (!$styled) {
        $styled = true;
        echo '<style>
.profile-drop{position:relative;display:inline-flex;flex-direction:column;align-items:center;}
.profile-drop-menu{display:none;position:absolute;top:100%;left:50%;transform:translateX(-50%);min-width:180px;padding:10px 0 8px;background:#1a1a1a;border-radius:8px;box-shadow:0 8px 16px rgba(0,0,0,.3);z-index:1200;text-align:left;}
.profile-drop:hover .profile-drop-menu,.profile-drop:focus-within .profile-drop-menu{display:block;}
.profile-drop-menu a{display:block;padding:10px 16px;color:#f0f0f0 !important;font-family:"Julee",cursive;font-size:22px !important;line-height:1.2;text-decoration:none;white-space:nowrap;}
.profile-drop-menu a:hover{background:#333;color:#fff !important;}
.level-sketch{width:16vw;min-width:180px;margin-top:4px;font-family:"Julee",cursive;}
.level-line{display:flex;align-items:center;gap:6px;color:#fff;font-size:1.05vw;line-height:1;}
.level-track,.reward-track{background:#213922;border-radius:8px;overflow:hidden;}
.level-track{flex:1;height:0.7vw;min-height:8px;}
.level-fill{display:block;height:100%;background:#76B896;}
.reward-track{display:flex;gap:3px;height:0.55vw;min-height:7px;margin-top:4px;background:transparent;}
.reward-track span{flex:1;border-radius:8px;background:#3E614F;}
.reward-track span.got{background:#7D121A;}
header.scrolled .level-sketch{width:140px;min-width:0;margin-top:2px;}
header.scrolled .level-line{font-size:11px;gap:4px;}
header.scrolled .level-track,header.scrolled .reward-track{height:6px;}
header.scrolled .reward-track{margin-top:2px;}
header .user-name-row{display:inline-flex;align-items:center;gap:10px;max-width:100%;}
header .user-avatar{display:inline-flex;align-items:center;justify-content:center;width:80px;height:80px;min-width:80px;margin:0;border-radius:0;object-fit:cover;flex:0 0 auto;background:#1E5946;color:#fff;font-size:36px;line-height:1;text-align:center;font-family:"Julee",cursive;transform:translateY(-16px);}
header.scrolled .user-avatar{width:48px;height:48px;min-width:48px;font-size:22px;transform:none;}
</style>';
    }

    $avatar = dw_avatar_markup($base);
    echo '<script>(function(){var drop=document.currentScript.closest(".profile-drop");if(!drop||drop.querySelector(".user-avatar"))return;var holder=document.createElement("span");holder.innerHTML=' . json_encode($avatar) . ';var img=holder.firstElementChild;if(!img)return;var name=drop.querySelector(".user-name");if(name){var row=document.createElement("span");row.className="user-name-row";name.parentNode.insertBefore(row,name);row.appendChild(img);row.appendChild(name);return;}var link=drop.querySelector(":scope > a");if(link){link.classList.add("user-name-row");link.insertBefore(img,link.firstChild);return;}var row=document.createElement("span");row.className="user-name-row";row.appendChild(img);while(drop.firstChild&&drop.firstChild.nodeType===3){row.appendChild(drop.firstChild);}drop.insertBefore(row,drop.firstChild);})();</script>';

    if ($withLevel) {
        dw_level_sketch();
    }

    if ($logout === '') {
        if ($base === '' || str_contains($base, '../../Moje dane/')) {
            $logout = '../../../Regestracja and login/Php_files/logout.php';
        } elseif (str_starts_with($base, '../Konto/')) {
            $logout = 'logout.php';
        } else {
            $logout = '../../Regestracja and login/Php_files/logout.php';
        }
    }

    $prefix = $base === '' ? '' : rtrim($base, '/') . '/';
    $links = [
        'Hodowla' => $prefix . 'Hodowla.php',
        'Awatary' => $prefix . 'Awatary.php',
        'Klasy' => $prefix . 'Klasy.php',
        'Gry' => $prefix . 'Gry.php',
        'Ranking' => $prefix . 'Ranking.php',
        'Wyloguj się' => $logout,
    ];

    echo '<div class="profile-drop-menu">';
    foreach ($links as $label => $href) {
        echo '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '">' . $label . '</a>';
    }
    echo '</div>';
}

function dw_avatar_markup(string $base): string
{
    $foto = trim((string) ($_SESSION['user']['foto'] ?? ''));
    if ($foto === '' && !empty($_SESSION['user']['id'])) {
        require_once __DIR__ . '/grywalizacja.php';
        $stmt = dw_pdo()->prepare('SELECT Foto FROM [Użytkownicy] WHERE id = ?');
        $stmt->execute([(int) $_SESSION['user']['id']]);
        $foto = trim((string) $stmt->fetchColumn());
        $_SESSION['user']['foto'] = $foto;
    }

    $dir = dirname(__DIR__) . '/Konto/Moje dane/Php_files/';
    $relative = str_replace('\\', '/', $foto);
    if ($relative === '' || !is_file($dir . $relative)) {
        $letter = mb_substr((string) ($_SESSION['user']['first_name'] ?? '?'), 0, 1, 'UTF-8');
        return '<span class="user-avatar" aria-hidden="true">' . htmlspecialchars($letter) . '</span>';
    }

    $prefix = $base === '' ? '' : rtrim($base, '/') . '/';
    return '<img class="user-avatar" src="' . htmlspecialchars($prefix . $relative, ENT_QUOTES) . '" alt="">';
}

function dw_level_sketch(): void
{
    if (empty($_SESSION['user']['id'])) {
        return;
    }

    require_once __DIR__ . '/grywalizacja.php';
    $postep = dw_postep(dw_pdo(), (int) $_SESSION['user']['id']);
    $_SESSION['klasa'] = $postep['klasa'];
    $szerokosc = $postep['xp_do_nastepnego'] > 0
        ? (int) round($postep['xp_w_poziomie'] / $postep['xp_do_nastepnego'] * 100)
        : 100;
    ?>
<div class="level-sketch">
  <div class="level-line">
    <span>Poz. <?= (int) $postep['poziom'] ?></span>
    <div class="level-track" title="Doświadczenie do następnego poziomu">
      <span class="level-fill" style="width: <?= $szerokosc ?>%"></span>
    </div>
    <span><?= (int) $postep['xp_w_poziomie'] ?>/<?= (int) max(1, $postep['xp_do_nastepnego']) ?></span>
  </div>
  <div class="reward-track" title="Punkty klasy za poziomy">
    <?php for ($i = 1; $i <= 4; $i++): ?>
      <span<?= $postep['poziom'] >= $i ? ' class="got"' : '' ?>></span>
    <?php endfor; ?>
  </div>
</div>
    <?php
}
