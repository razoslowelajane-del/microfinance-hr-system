<?php
// theme.php (self-contained: CSS + button + JS)
// Safe to include on many pages; guarded against double init.
?>

<style id="theme-toggle-style">
/* Theme Toggle Button - standalone styles */
.theme-toggle{
  position:relative;
  width:40px;
  height:40px;
  border-radius:10px;
  background:var(--surface, #fff);
  border:1px solid var(--border-color, #e5e7eb);
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  transition:all .2s ease;
  z-index:2001;
  box-shadow:0 1px 3px rgba(0,0,0,.10);
  flex-shrink:0;
  appearance:none;
  -webkit-appearance:none;
}
.theme-toggle:hover{
  transform:translateY(-1px);
  box-shadow:0 10px 15px rgba(0,0,0,.12);
}
.theme-toggle .sun-icon,
.theme-toggle .moon-icon{
  width:18px;
  height:18px;
  color:var(--text-secondary, #6b7280);
  position:absolute;
  transition:opacity .18s ease;
}
.theme-toggle .sun-icon{opacity:1}
.theme-toggle .moon-icon{opacity:0}

body.dark-mode .theme-toggle .sun-icon{opacity:0}
body.dark-mode .theme-toggle .moon-icon{opacity:1}

/* Default: inline (inside header-right) */
.theme-toggle.theme-inline{position:relative}
</style>

<button class="theme-toggle theme-inline" id="themeToggle" type="button" aria-label="Toggle theme">
  <i data-lucide="sun" class="sun-icon"></i>
  <i data-lucide="moon" class="moon-icon"></i>
</button>

<script>
(function () {
  // ✅ Prevent double-initialization if theme.php gets included twice
  if (window.__THEME_TOGGLE_INIT__) return;
  window.__THEME_TOGGLE_INIT__ = true;

  function initThemeToggle() {
    const body = document.body;
    const btn  = document.getElementById("themeToggle");
    if (!btn) return;

    // Load saved theme
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "dark") body.classList.add("dark-mode");

    // Render lucide icons (sun/moon) if lucide is available
    if (window.lucide && typeof window.lucide.createIcons === "function") {
      window.lucide.createIcons();
    }

    btn.addEventListener("click", () => {
      body.classList.toggle("dark-mode");
      localStorage.setItem("theme", body.classList.contains("dark-mode") ? "dark" : "light");

      // Refresh icons (optional but safe)
      if (window.lucide && typeof window.lucide.createIcons === "function") {
        window.lucide.createIcons();
      }
    });
  }

  // ✅ If DOM already loaded, init immediately; else wait.
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initThemeToggle);
  } else {
    initThemeToggle();
  }
})();
</script>