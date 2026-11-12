(function () {
  const header = document.querySelector("header");
  if (!header) return;

  const compactAt = 40;

  function updateHeaderCompact() {
    header.classList.toggle("scrolled", window.scrollY > compactAt);
  }

  updateHeaderCompact();
  window.addEventListener("scroll", updateHeaderCompact, { passive: true });
})();
