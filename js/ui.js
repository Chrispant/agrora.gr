(function () {
  function ensureBackdrop() {
    let backdrop = document.getElementById("mobileMenuBackdrop");
    if (!backdrop) {
      backdrop = document.createElement("div");
      backdrop.id = "mobileMenuBackdrop";
      backdrop.className = "mobile-menu-backdrop";
      document.body.appendChild(backdrop);
    }
    return backdrop;
  }

  function initMobileMenu() {
    const burger = document.querySelector(".burger");
    const menu = document.getElementById("mobileMenu");
    const closeBtn = document.getElementById("mobileMenuClose");

    if (!burger || !menu) return;

    const backdrop = ensureBackdrop();

    const openMenu = () => {
      menu.classList.add("open");
      backdrop.classList.add("open");
      menu.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
    };

    const closeMenu = () => {
      menu.classList.remove("open");
      backdrop.classList.remove("open");
      menu.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
    };

    burger.addEventListener("click", (e) => {
      e.stopPropagation();
      if (menu.classList.contains("open")) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    closeBtn?.addEventListener("click", closeMenu);

    backdrop.addEventListener("click", closeMenu);

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && menu.classList.contains("open")) {
        closeMenu();
      }
    });

    // Αφήνουμε τα links να πλοηγηθούν φυσικά
    menu.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        closeMenu();
      });
    });
  }
	
	function ensureFiltersBackdrop() {
  let backdrop = document.getElementById("filtersSheetBackdrop");
  if (!backdrop) {
    backdrop = document.createElement("div");
    backdrop.id = "filtersSheetBackdrop";
    backdrop.className = "filters-sheet-backdrop";
    document.body.appendChild(backdrop);
  }
  return backdrop;
}

function initMobileFiltersSheet() {
  const openBtn = document.getElementById("openFiltersBtn");
  const closeBtn = document.getElementById("closeFiltersBtn");
  const sheet = document.querySelector(".filters-sidebar");

  if (!openBtn || !sheet) return;

  const backdrop = ensureFiltersBackdrop();

  const openSheet = () => {
    sheet.classList.add("is-open");
    backdrop.classList.add("open");
    document.body.style.overflow = "hidden";
  };

  const closeSheet = () => {
    sheet.classList.remove("is-open");
    backdrop.classList.remove("open");
    document.body.style.overflow = "";
  };

  openBtn.addEventListener("click", openSheet);
  closeBtn?.addEventListener("click", closeSheet);
  backdrop.addEventListener("click", closeSheet);

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && sheet.classList.contains("is-open")) {
      closeSheet();
    }
  });

  const filtersForm = sheet.querySelector("form");
  filtersForm?.addEventListener("submit", () => {
    closeSheet();
  });
}

  document.addEventListener("DOMContentLoaded", initMobileMenu);
	initMobileFiltersSheet();
})();