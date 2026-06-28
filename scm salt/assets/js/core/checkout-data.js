const CHECKOUT_STORAGE_KEY = "salt-checkout";
const NEXT_PAGE_KEY = "salt-next-page";

function saltPage(routeKey) {
  if (window.SALT_ROUTES && window.SALT_ROUTES[routeKey]) {
    return window.SALT_ROUTES[routeKey];
  }
  return routeKey;
}

function navigateWithLoading(routeKey, mode = "default") {
  sessionStorage.setItem(NEXT_PAGE_KEY, routeKey);
  sessionStorage.setItem("salt-loader-mode", mode);
  window.location.href = saltPage("chargement");
}

function consumeNextPage() {
  const routeKey = sessionStorage.getItem(NEXT_PAGE_KEY);
  sessionStorage.removeItem(NEXT_PAGE_KEY);
  if (!routeKey) return null;
  return saltPage(routeKey);
}

function getCheckoutData() {
  try {
    const raw = localStorage.getItem(CHECKOUT_STORAGE_KEY);
    return raw ? JSON.parse(raw) : {};
  } catch {
    return {};
  }
}

function saveCheckoutData(partial) {
  const data = { ...getCheckoutData(), ...partial };
  localStorage.setItem(CHECKOUT_STORAGE_KEY, JSON.stringify(data));
}
