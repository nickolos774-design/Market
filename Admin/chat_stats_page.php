<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<link href="styleindex.css" rel="stylesheet">
<title>AI Chat Stats</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>

/* =========================================
   DRAGON ADMIN LIGHT THEME
========================================= */

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;

    background:
    linear-gradient(
        135deg,
        #d8f5e3 0%,
        #e0f2fe 100%
    );

    min-height:100vh;

    color:#111827;
}


/* =========================================
   MENU
========================================= */

.menu{
    display:flex;
    justify-content:center;
    gap:20px;

    padding:25px;
}

.menu-button{
    text-decoration:none;

    padding:12px 22px;

    border-radius:14px;

    background:#4C7B68;

    color:white;

    font-weight:700;

    box-shadow:
    0 4px 14px rgba(20,83,45,0.25);

    transition:.25s;
}

.menu-button:hover{
    background:#3E614F;

    color:black;

    transform:translateY(-3px);

    box-shadow:
    0 10px 20px rgba(34,197,94,0.35);
}

.menu-button.active{
    background:#7D121A;

    box-shadow:
    0 8px 20px rgba(153,27,27,0.35);
}


/* =========================================
   TITLE
========================================= */

h1{
    margin:10px 25px 25px;

    font-size:40px;

    font-weight:700;

    color:#111827;
}


/* =========================================
   GRID
========================================= */

.grid{
    display:grid;

    grid-template-columns:
    repeat(auto-fit,minmax(240px,1fr));

    gap:25px;

    padding:0 25px;
}


/* =========================================
   CARDS
========================================= */

.card{
    background:
    linear-gradient(
        135deg,
        #4C7B68,
        #1E293B
    );

    padding:30px;

    border-radius:18px;

    min-height:200px;
    max-width: 350px;

    color:white;

    box-shadow:
    0 10px 30px rgba(0,0,0,0.18);

    transition:.3s;

    display:flex;
    flex-direction:column;
    justify-content:center;
}

.card:hover{
    transform:translateY(-6px);

    box-shadow:
    0 16px 36px rgba(20,83,45,0.35);
}

.card h2{
    font-size:58px;

    font-weight:700;

    color:#4ADE80;
}

.card p{
    margin-top:10px;

    color:#E2E8F0;

    font-size:16px;

    font-weight:500;
}


/* =========================================
   SECTION TITLE
========================================= */

.stats-title{
    margin:40px 25px 20px;

    font-size:34px;

    font-weight:700;

    color:#111827;
}


/* =========================================
   TABLE
========================================= */

table{
    width:calc(100% - 50px);

    margin:0 25px 40px;

    border-collapse:collapse;

    background:white;

    border-radius:18px;

    overflow:hidden;

    box-shadow:
    0 10px 28px rgba(0,0,0,0.1);
}


/* =========================================
   TABLE HEADER
========================================= */

th{
    background:#4C7B68;

    color:white;

    text-align:left;

    padding:18px;

    font-size:16px;

    font-weight:600;
}


/* =========================================
   TABLE CELLS
========================================= */

td{
    padding:16px 18px;

    border-bottom:
    1px solid #e5e7eb;

    font-size:15px;
}


/* =========================================
   TABLE HOVER
========================================= */

tr{
    transition:.2s;
}

tr:hover{
    background:#DCFCE7;
}


/* =========================================
   SCROLLBAR
========================================= */

::-webkit-scrollbar{
    width:10px;
}

::-webkit-scrollbar-track{
    background:#dbeafe;
}

::-webkit-scrollbar-thumb{
    background:#4C7B68;

    border-radius:10px;
}

::-webkit-scrollbar-thumb:hover{
    background:#7D121A;
}


/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:900px){

    .menu{
        flex-wrap:wrap;
    }

    .grid{
        grid-template-columns:1fr;
    }

    h1{
        font-size:30px;
    }

    .card h2{
        font-size:42px;
    }
}

</style>
</head>

<body>
<script src="../runes-bg.js"></script>

<div class="menu">
  <a href="Edycja.php" class="menu-button">Edycja produktu</a>
  <a href="../../Konto/Zamowienia/Zamowienia.php" class="menu-button">Zamówienia</a>
  <a href="Pracowniki.php" class="menu-button">Zarządzania użytkownikami</a>
  <a href="opinie.php" class="menu-button ">Opinie</a>
  <a href="chat_stats_page.php" class="menu-button  active">Statystyki czatu</a>
</div>

<h1 style="margin-top:30px; padding: 10px; border-radius: 12px; color: black;">AI Chat Statistics:</h1>

<div class="grid">
  <div class="card">
    <h2 id="sessions">0</h2>
    <p>Rozmowy dziś</p>
  </div>

  <div class="card">
    <h2 id="messages">0</h2>
    <p>Wiadomości</p>
  </div>

  <div class="card">
    <h2 id="ai_rec">0</h2>
    <p>Rekomendacje AI</p>
  </div>

  <div class="card">
    <h2 id="clicks">0</h2>
    <p>Kliknięcia (AI)</p>
  </div>
</div>

<div style="margin-top:30px;margin-left: 10px; color: black; font-size: 36px; font-weight: bold;">Najczęstsze pytania:</div>

<table>
  <thead>
    <tr>
      <th>Zapytanie</th>
      <th>Ilość</th>
    </tr>
  </thead>
  <tbody id="top-questions"></tbody>
</table>

<script>
async function loadStats() {

  const res = await fetch("chat_stats.php");
  const data = await res.json();

  document.getElementById("sessions").innerText = data.today_sessions;
  document.getElementById("messages").innerText = data.messages;
  document.getElementById("ai_rec").innerText = data.ai_recommendations;

  // ❗ ВОТ ЭТОГО НЕ ХВАТАЛО
  document.getElementById("clicks").innerText = data.clicks;

  const tbody = document.getElementById("top-questions");
  tbody.innerHTML = "";

  data.top_questions.forEach(q => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${q.Tekst}</td>
      <td>${q.cnt}</td>
    `;
    tbody.appendChild(row);
  });
}

loadStats();

// автообновление каждые 10 сек
setInterval(loadStats, 10000);
</script>

</body>
</html>