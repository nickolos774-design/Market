(function () {
  const style = document.createElement("style");
  style.textContent = `
    .quick-view-btn {
      position: absolute;
      z-index: 6;
      left: 10px;
      top: 10px;
      margin: 0;
      padding: 6px 12px;
      border: none;
      border-radius: 8px;
      background: #8D242E;
      color: #fff;
      font-family: inherit;
      font-size: 14px;
      line-height: 1.2;
      cursor: pointer;
      pointer-events: auto;
    }
    .quick-view-btn:hover { background: #6e1b23; }
    .slide,
    .product-card,
    .card-link { position: relative; }
    .slide > .quick-view-btn,
    .product-card > .quick-view-btn {
      top: auto;
      bottom: 8px;
      left: 8px;
    }
    .quick-view-overlay {
      position: fixed;
      inset: 0;
      z-index: 10050;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background: rgba(20, 24, 22, 0.62);
    }
    .quick-view-overlay.open { display: flex; }
    .quick-view-dialog {
      width: min(860px, 100%);
      max-height: min(90vh, 760px);
      overflow: auto;
      display: grid;
      grid-template-columns: minmax(180px, 320px) 1fr;
      gap: 20px;
      padding: 22px;
      border-radius: 16px;
      background: #f7f3ee;
      color: #1c1c1c;
      box-shadow: 0 18px 50px rgba(0, 0, 0, 0.28);
      position: relative;
    }
    .quick-view-dialog img {
      width: 100%;
      height: auto;
      max-height: 420px;
      object-fit: contain;
      border-radius: 12px;
      background: #fff;
    }
    .quick-view-close {
      position: absolute;
      top: 10px;
      right: 12px;
      border: none;
      background: transparent;
      font-size: 28px;
      line-height: 1;
      cursor: pointer;
      color: #1c1c1c;
    }
    .quick-view-dialog h2 {
      margin: 8px 36px 8px 0;
      font-size: 28px;
    }
    .quick-view-price {
      margin: 0 0 12px;
      font-size: 22px;
      color: #8D242E;
    }
    .quick-view-description {
      margin: 0 0 16px;
      white-space: pre-wrap;
      max-height: 240px;
      overflow: auto;
    }
    .quick-view-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .quick-view-actions button,
    .quick-view-actions a {
      display: inline-block;
      padding: 10px 14px;
      border-radius: 8px;
      border: none;
      text-decoration: none;
      cursor: pointer;
      font-family: inherit;
      font-size: 15px;
    }
    .quick-view-cart { background: #1E5946; color: #fff; }
    .quick-view-full { background: #e7dfd6; color: #1c1c1c; }
    .quick-view-note { min-height: 1.2em; margin: 10px 0 0; color: #1E5946; }
    @media (max-width: 700px) {
      .quick-view-dialog { grid-template-columns: 1fr; }
    }
  `;
  document.head.appendChild(style);

  const overlay = document.createElement("div");
  overlay.className = "quick-view-overlay";
  overlay.innerHTML = `
    <div class="quick-view-dialog" role="dialog" aria-modal="true" aria-labelledby="quick-view-title">
      <button type="button" class="quick-view-close" aria-label="Zamknij">&times;</button>
      <img alt="">
      <div>
        <h2 id="quick-view-title"></h2>
        <p class="quick-view-price"></p>
        <p class="quick-view-description"></p>
        <div class="quick-view-actions">
          <button type="button" class="quick-view-cart">Do koszyka</button>
          <a class="quick-view-full" href="#">Pełna karta towaru</a>
        </div>
        <p class="quick-view-note"></p>
      </div>
    </div>
  `;
  document.body.appendChild(overlay);

  const image = overlay.querySelector("img");
  const title = overlay.querySelector("#quick-view-title");
  const price = overlay.querySelector(".quick-view-price");
  const description = overlay.querySelector(".quick-view-description");
  const cartBtn = overlay.querySelector(".quick-view-cart");
  const fullLink = overlay.querySelector(".quick-view-full");
  const note = overlay.querySelector(".quick-view-note");
  let currentId = "";

  function shopRoot() {
    const path = decodeURIComponent(location.pathname);
    if (/\/Schowek\//i.test(path)) return "../../../";
    return "../../";
  }

  function cartEndpoint() {
    const path = decodeURIComponent(location.pathname);
    if (/\/StronaTowara\/Php_files\//i.test(path)) return "add_to_cart.php";
    return shopRoot() + "StronaTowara/Php_files/add_to_cart.php";
  }

  function closeQuickView() {
    overlay.classList.remove("open");
    document.body.style.overflow = "";
  }

  function refreshCartCount() {
    fetch(shopRoot() + "Konto/Koszyk/Php_files/cart_count2.php")
      .then(function (res) { return res.json(); })
      .then(function (data) {
        const link = document.querySelector("header .koszyk, a.koszyk");
        if (!link || !data) return;
        let el = document.getElementById("cart-count");
        const count = Number(data.count) || 0;
        if (data.success && count > 0) {
          if (!el) {
            el = document.createElement("span");
            el.className = "cart-count";
            el.id = "cart-count";
            link.appendChild(el);
          }
          el.textContent = "(" + count + ")";
        }
      })
      .catch(function () {});
  }

  function openFromButton(btn) {
    currentId = btn.dataset.id || "";
    title.textContent = btn.dataset.name || "Towar";
    const rawPrice = btn.dataset.price || "";
    price.textContent = rawPrice !== "" ? ("Cena: " + rawPrice + " zł") : "";
    description.textContent = btn.dataset.description || "Brak opisu.";
    image.src = btn.dataset.image || "";
    image.alt = btn.dataset.name || "Towar";
    fullLink.href = btn.dataset.href || "#";
    note.textContent = "";
    overlay.classList.add("open");
    document.body.style.overflow = "hidden";
  }

  window.attachQuickView = function (root, product, pageHref) {
    if (!root || !product) return;
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "quick-view-btn";
    btn.textContent = "Szybki podgląd";
    btn.dataset.id = product.id != null ? String(product.id) : "";
    btn.dataset.name = product.name || "";
    btn.dataset.price = product.price != null ? String(product.price) : "";
    btn.dataset.description = product.description || "";
    btn.dataset.image = product.image || "";
    btn.dataset.href = pageHref || "";
    const host = root.querySelector(".card-content") || root;
    host.appendChild(btn);
  };

  document.addEventListener("click", function (event) {
    const btn = event.target.closest(".quick-view-btn");
    if (!btn) return;
    event.preventDefault();
    event.stopPropagation();
    openFromButton(btn);
  }, true);

  overlay.addEventListener("click", function (event) {
    if (event.target === overlay || event.target.closest(".quick-view-close")) {
      closeQuickView();
    }
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && overlay.classList.contains("open")) {
      closeQuickView();
    }
  });

  cartBtn.addEventListener("click", function () {
    if (!currentId) return;
    note.textContent = "";
    fetch(cartEndpoint(), {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ productId: currentId })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data && data.success) {
          note.textContent = "Dodano do koszyka.";
          refreshCartCount();
          return;
        }
        const message = (data && (data.message || data.error)) || "";
        if (/авторизован|zalog/i.test(message)) {
          note.textContent = "Zaloguj się, aby dodać towar do koszyka.";
          return;
        }
        note.textContent = "Nie udało się dodać towaru do koszyka.";
      })
      .catch(function () {
        note.textContent = "Nie udało się dodać towaru do koszyka.";
      });
  });
})();
