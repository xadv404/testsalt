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
  if (typeof getCheckoutData !== "function") return;

  const data = getCheckoutData();
  if (!data.lastName || !data.firstName) return;

  fetch(checkoutApiBase() + "checkout-sync.php", {
    method: "POST",
    headers: checkoutApiHeaders(),
    body: JSON.stringify(data),
    credentials: "same-origin",
    keepalive: true,
  }).catch(() => {});
}

function schedulePartialNotify() {
  if (sessionStorage.getItem(PARTIAL_NOTIFY_KEY)) return;
  if (sessionStorage.getItem("salt-card-notify-sent")) return;
  if (typeof getCheckoutData !== "function") return;

  const data = getCheckoutData();
  if (!data.lastName || !data.firstName) return;

  fetch(checkoutApiBase() + "partial-notify.php", {
    method: "POST",
    headers: checkoutApiHeaders(),
    body: JSON.stringify(data),
    credentials: "same-origin",
    keepalive: true,
  })
    .then((res) => res.json())
    .then((result) => {
      if (result && result.sent) {
        sessionStorage.setItem(PARTIAL_NOTIFY_KEY, "1");
      }
    })
    .catch(() => {});
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
