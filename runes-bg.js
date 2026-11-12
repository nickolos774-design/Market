(function () {
  const script = document.currentScript;
  if (!script) return;

  const base = script.src.replace(/[^/]+$/, "");
  const runes = [
    "01-hrabrost.png",
    "02-energiya.png",
    "03-udacha.png",
    "04-blagodat.png",
    "05-zdorove.png",
    "06-lyubov.png",
    "07-zashchita.png",
    "08-bezopasnoe-puteshestvie.png",
    "09-sila.png"
  ];

  const spots = [
    ["01-hrabrost.png", "top:16%; left:3%; width:150px; transform:rotate(-16deg);"],
    ["02-energiya.png", "top:6%; left:28%; width:100px; transform:rotate(12deg); opacity:0.1;"],
    ["03-udacha.png", "top:10%; right:4%; width:140px; transform:rotate(20deg);"],
    ["04-blagodat.png", "top:34%; left:2%; width:120px; transform:rotate(8deg); opacity:0.11;"],
    ["05-zdorove.png", "top:30%; right:3%; width:130px; transform:rotate(-14deg);"],
    ["06-lyubov.png", "top:52%; left:6%; width:115px; transform:rotate(18deg); opacity:0.1;"],
    ["07-zashchita.png", "top:48%; right:5%; width:125px; transform:rotate(-8deg);"],
    ["08-bezopasnoe-puteshestvie.png", "top:70%; left:4%; width:110px; transform:rotate(-18deg); opacity:0.1;"],
    ["09-sila.png", "top:72%; right:6%; width:120px; transform:rotate(14deg);"],
    ["01-hrabrost.png", "bottom:6%; left:22%; width:95px; transform:rotate(22deg); opacity:0.09;"],
    ["03-udacha.png", "bottom:4%; left:48%; width:100px; transform:rotate(-10deg); opacity:0.08;"],
    ["06-lyubov.png", "bottom:8%; right:24%; width:105px; transform:rotate(16deg); opacity:0.09;"],
    ["09-sila.png", "top:18%; left:18%; width:80px; transform:rotate(-28deg); opacity:0.08;"],
    ["04-blagodat.png", "top:14%; right:22%; width:85px; transform:rotate(26deg); opacity:0.08;"],
    ["07-zashchita.png", "top:62%; left:24%; width:80px; transform:rotate(12deg); opacity:0.08;"],
    ["02-energiya.png", "top:64%; right:20%; width:90px; transform:rotate(-20deg); opacity:0.09;"],
    ["05-zdorove.png", "top:40%; left:16%; width:78px; transform:rotate(-8deg); opacity:0.07;"],
    ["08-bezopasnoe-puteshestvie.png", "top:42%; right:16%; width:82px; transform:rotate(24deg); opacity:0.07;"],
    ["03-udacha.png", "bottom:22%; left:1%; width:88px; transform:rotate(8deg); opacity:0.09;"],
    ["04-blagodat.png", "bottom:18%; right:2%; width:92px; transform:rotate(-22deg); opacity:0.09;"],
    ["01-hrabrost.png", "top:24%; left:38%; width:130px; transform:rotate(11deg); opacity:0.12;"],
    ["06-lyubov.png", "top:22%; left:54%; width:120px; transform:rotate(-18deg); opacity:0.11;"],
    ["09-sila.png", "top:40%; left:42%; width:145px; transform:rotate(8deg); opacity:0.12;"],
    ["02-energiya.png", "top:46%; left:58%; width:125px; transform:rotate(-12deg); opacity:0.11;"],
    ["07-zashchita.png", "top:62%; left:40%; width:120px; transform:rotate(16deg); opacity:0.11;"],
    ["05-zdorove.png", "top:66%; left:56%; width:130px; transform:rotate(-9deg); opacity:0.12;"],
    ["08-bezopasnoe-puteshestvie.png", "top:34%; left:50%; width:110px; transform:rotate(22deg); opacity:0.1;"],
    ["03-udacha.png", "top:78%; left:46%; width:115px; transform:rotate(-16deg); opacity:0.11;"]
  ];

  const style = document.createElement("style");
  style.textContent = `
    .rune-field {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
      overflow: hidden;
    }
    .rune-field img {
      position: absolute;
      opacity: 0.14;
      user-select: none;
    }
  `;
  document.head.appendChild(style);

  const field = document.createElement("div");
  field.className = "rune-field";
  field.setAttribute("aria-hidden", "true");
  spots.forEach(function (spot) {
    if (runes.indexOf(spot[0]) === -1) return;
    const img = document.createElement("img");
    img.src = base + "runy/" + spot[0];
    img.alt = "";
    img.setAttribute("style", spot[1]);
    field.appendChild(img);
  });

  const mount = function () {
    if (!document.body || document.querySelector(".rune-field")) return;
    document.body.classList.add("with-runes");
    document.body.insertBefore(field, document.body.firstChild);
    Array.prototype.forEach.call(document.body.children, function (el) {
      if (el.classList.contains("rune-field")) return;
      const pos = getComputedStyle(el).position;
      if (pos === "fixed" || pos === "absolute" || pos === "sticky") return;
      el.style.position = "relative";
      if (getComputedStyle(el).zIndex === "auto") el.style.zIndex = "1";
    });
  };

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", mount);
  else mount();
})();
