const LANG_KEY = "salt-lang";
const THEME_KEY = "salt-theme";

function applySavedTheme() {
  try {
    document.body.classList.toggle(
      "light-theme",
      localStorage.getItem(THEME_KEY) === "light"
    );
  } catch {
    /* ignore storage errors */
  }
}

function setTheme(theme) {
  try {
    localStorage.setItem(THEME_KEY, theme);
  } catch {
    /* ignore storage errors */
  }
  document.body.classList.toggle("light-theme", theme === "light");
}

function getTitleKey() {
  const page = document.body.dataset.page;
  if (page === "page2") return "page2Title";
  if (page === "page3") return "page3Title";
  if (page === "page4") return "page4Title";
  if (page === "loading") return "loadingTitle";
  return "pageTitle";
}

function setLanguage(lang) {
  if (!translations[lang]) return;

  localStorage.setItem(LANG_KEY, lang);

  const t = getTranslation(lang);
  document.documentElement.lang = lang;
  document.title = t[getTitleKey()];

  const langCurrent = document.getElementById("lang-current");
  if (langCurrent) langCurrent.textContent = langLabels[lang];

  document.querySelectorAll("[data-i18n]").forEach((el) => {
    const key = el.getAttribute("data-i18n");
    if (t[key]) el.textContent = t[key];
  });

  document.querySelectorAll("[data-i18n-html]").forEach((el) => {
    const key = el.getAttribute("data-i18n-html");
    if (t[key]) el.innerHTML = t[key];
  });

  document.querySelectorAll("[data-i18n-placeholder]").forEach((el) => {
    const key = el.getAttribute("data-i18n-placeholder");
    if (t[key]) el.placeholder = t[key];
  });

  document.querySelectorAll("[data-i18n-aria]").forEach((el) => {
    const key = el.getAttribute("data-i18n-aria");
    if (t[key]) el.setAttribute("aria-label", t[key]);
  });

  document.querySelectorAll("[data-i18n-href]").forEach((el) => {
    const key = el.getAttribute("data-i18n-href");
    if (t[key]) el.href = t[key];
  });

  const langMenu = document.getElementById("lang-menu");
  if (langMenu) {
    langMenu.querySelectorAll("[data-lang]").forEach((btn) => {
      btn.setAttribute("aria-selected", btn.dataset.lang === lang ? "true" : "false");
    });
  }
}

function initShell() {
  const themeToggle = document.getElementById("theme-toggle");
  const langDropdown = document.getElementById("lang-dropdown");
  const langToggle = document.getElementById("lang-toggle");
  const langMenu = document.getElementById("lang-menu");

  if (themeToggle) {
    themeToggle.addEventListener("click", () => {
      const isLight = !document.body.classList.contains("light-theme");
      setTheme(isLight ? "light" : "dark");
    });
  }

  if (langToggle && langMenu && langDropdown) {
    const openLangMenu = () => {
      langMenu.hidden = false;
      langToggle.setAttribute("aria-expanded", "true");
      langDropdown.classList.add("lang-dropdown--open");
    };

    const closeLangMenu = () => {
      langMenu.hidden = true;
      langToggle.setAttribute("aria-expanded", "false");
      langDropdown.classList.remove("lang-dropdown--open");
    };

    langToggle.addEventListener("click", (e) => {
      e.stopPropagation();
      if (langMenu.hidden) openLangMenu();
      else closeLangMenu();
    });

    langMenu.querySelectorAll("[data-lang]").forEach((btn) => {
      btn.addEventListener("click", () => {
        setLanguage(btn.dataset.lang);
        closeLangMenu();
        document.dispatchEvent(new CustomEvent("salt:languagechange"));
      });
    });

    document.addEventListener("click", (e) => {
      if (!langDropdown.contains(e.target)) closeLangMenu();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeLangMenu();
    });
  }

  setLanguage(localStorage.getItem(LANG_KEY) || "fr");
  initBrandBarShrink();
}

function initBrandBarShrink() {
  const brandBar = document.querySelector(".brand-bar");
  const topBar = document.querySelector(".top-bar");
  if (!brandBar || !topBar) return;

  const setTopBarHeight = () => {
    document.documentElement.style.setProperty(
      "--top-bar-height",
      `${topBar.offsetHeight}px`
    );
  };

  const updateScrollState = () => {
    brandBar.classList.toggle("brand-bar--compact", window.scrollY > 12);
  };

  setTopBarHeight();
  updateScrollState();

  window.addEventListener("resize", setTopBarHeight);
  window.addEventListener("scroll", updateScrollState, { passive: true });
}

applySavedTheme();
