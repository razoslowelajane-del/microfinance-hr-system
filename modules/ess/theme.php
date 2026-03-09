<?php
// theme.php (self-contained: CSS + button + JS)
?>
<style id="theme-toggle-style">
.theme-toggle {
  position: relative;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--surface);
  border: 1px solid var(--border-color);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all .2s ease;
  z-index: 2001;
  box-shadow: 0 1px 3px rgba(0,0,0,.10);
  flex-shrink: 0;
  appearance: none;
  -webkit-appearance: none;
}
.theme-toggle:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 15px rgba(0,0,0,.12);
}
.theme-toggle .sun-icon,
.theme-toggle .moon-icon {
  width: 18px;
  height: 18px;
  color: var(--text-muted);
  position: absolute;
  transition: opacity .18s ease;
}
/* Default visibility */
.theme-toggle .sun-icon { opacity: 1; }
.theme-toggle .moon-icon { opacity: 0; }

/* Dark mode specific visibility */
body.dark-mode .theme-toggle .sun-icon { opacity: 0; }
body.dark-mode .theme-toggle .moon-icon { opacity: 1; }
</style>

<button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle theme">
  <i data-lucide="sun" class="sun-icon"></i>
  <i data-lucide="moon" class="moon-icon"></i>
</button>

<script>
(function () {
  if (window.__THEME_TOGGLE_INIT__) return;
  window.__THEME_TOGGLE_INIT__ = true;

  // 1. Agarang i-apply ang theme bago pa mag-load ang body content
  const savedTheme = localStorage.getItem("theme");
  if (savedTheme === "dark") {
    document.body.classList.add("dark-mode");
  }

  function initThemeToggle() {
    const body = document.body;
    const btn = document.getElementById("themeToggle");
    if (!btn) return;

    // Render lucide icons
    if (window.lucide) window.lucide.createIcons();

    btn.addEventListener("click", () => {
      const isDark = body.classList.toggle("dark-mode");
      localStorage.setItem("theme", isDark ? "dark" : "light");
      if (window.lucide) window.lucide.createIcons();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initThemeToggle);
  } else {
    initThemeToggle();
  }
})();
</script>