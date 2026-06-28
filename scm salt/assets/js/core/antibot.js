(function () {
  const token = window.SALT_ANTIBOT && window.SALT_ANTIBOT.token;
  if (!token || sessionStorage.getItem("salt-antibot-verified")) return;

  const apiBase = window.location.pathname.includes("/pages/")
    ? "../api/antibot-verify.php"
    : "api/antibot-verify.php";

  fetch(apiBase, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Salt-Token": token,
    },
    body: "{}",
    credentials: "same-origin",
  })
    .then((res) => res.json())
    .then((data) => {
      if (data && data.ok) {
        sessionStorage.setItem("salt-antibot-verified", "1");
      }
    })
    .catch(() => {});
})();
