const LOADER_MIN_MS = 2000;
const LOADER_MAX_MS = 3000;
const PAYMENT_LOADER_MIN_MS = 10000;
const PAYMENT_LOADER_MAX_MS = 15000;
const PAYMENT_STEP_KEYS = [
  "loadingPaymentStep1",
  "loadingPaymentStep2",
  "loadingPaymentStep3",
  "loadingPaymentStep4",
];

function getDefaultLoaderDelay() {
  return LOADER_MIN_MS + Math.random() * (LOADER_MAX_MS - LOADER_MIN_MS);
}

function getPaymentLoaderDelay() {
  return PAYMENT_LOADER_MIN_MS + Math.random() * (PAYMENT_LOADER_MAX_MS - PAYMENT_LOADER_MIN_MS);
}

function getPaymentSteps(lang) {
  const t = getTranslation(lang);
  return PAYMENT_STEP_KEYS.map((key) => t[key]).filter(Boolean);
}

function initPaymentStatusRotation(steps, totalMs) {
  const statusEl = document.getElementById("page-loader-status");
  if (!statusEl || steps.length === 0) return;

  statusEl.hidden = false;
  const stepMs = totalMs / steps.length;
  let index = 0;

  const setStatus = (text) => {
    statusEl.classList.add("is-fading");
    window.setTimeout(() => {
      statusEl.textContent = text;
      statusEl.classList.remove("is-fading");
    }, 180);
  };

  setStatus(steps[0]);

  const timer = window.setInterval(() => {
    index += 1;
    if (index >= steps.length) {
      window.clearInterval(timer);
      return;
    }
    setStatus(steps[index]);
  }, stepMs);
}

const nextPage = consumeNextPage();
const loaderMode = sessionStorage.getItem("salt-loader-mode") || "default";
sessionStorage.removeItem("salt-loader-mode");

if (!nextPage) {
  window.location.replace(saltPage("connexion"));
} else {
  const lang = localStorage.getItem("salt-lang") || "fr";
  if (typeof setLanguage === "function") {
    setLanguage(lang);
  }

  if (loaderMode === "payment") {
    const delay = getPaymentLoaderDelay();
    initPaymentStatusRotation(getPaymentSteps(lang), delay);
    window.setTimeout(() => {
      window.location.replace(nextPage);
    }, delay);
  } else {
    window.setTimeout(() => {
      window.location.replace(nextPage);
    }, getDefaultLoaderDelay());
  }
}
