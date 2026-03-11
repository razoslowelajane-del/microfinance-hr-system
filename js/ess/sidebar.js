// js/sidebar.js (SIDEBAR-ONLY)
// Include this in sidebar.php globally.

document.addEventListener("DOMContentLoaded", () => {
  const sidebarToggle = document.getElementById("sidebarToggle");
  const sidebar = document.getElementById("sidebar");
  const mobileMenuBtn = document.getElementById("mobileMenuBtn");

  // Collapse toggle (desktop)
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener("click", () => {
      sidebar.classList.toggle("collapsed");
      localStorage.setItem(
        "sidebarCollapsed",
        sidebar.classList.contains("collapsed") ? "true" : "false"
      );
    });
  }

  // Restore collapsed state
  if (sidebar && localStorage.getItem("sidebarCollapsed") === "true") {
    sidebar.classList.add("collapsed");
  }

  // Mobile open/close
  if (mobileMenuBtn && sidebar) {
    mobileMenuBtn.addEventListener("click", () => {
      sidebar.classList.toggle("mobile-open");
    });
  }

  // Submenu logic
  document.querySelectorAll(".nav-item.has-submenu").forEach((item) => {
    item.addEventListener("click", () => {
      const module = item.getAttribute("data-module");
      const submenu = document.getElementById(`submenu-${module}`);
      if (submenu) submenu.classList.toggle("active");
      item.classList.toggle("active");
    });
  });

  // Optional: close sidebar when clicking outside (mobile)
  document.addEventListener("click", (e) => {
    if (!sidebar) return;
    if (!sidebar.classList.contains("mobile-open")) return;

    const clickedInsideSidebar = e.target.closest("#sidebar");
    const clickedMenuBtn = e.target.closest("#mobileMenuBtn");
    if (!clickedInsideSidebar && !clickedMenuBtn) {
      sidebar.classList.remove("mobile-open");
    }
  });
});