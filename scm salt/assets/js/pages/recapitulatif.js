const LOCALE_MAP = { fr: "fr-CH", de: "de-CH", it: "it-CH", en: "en-CH" };

function getCurrentLang() {
  return localStorage.getItem("salt-lang") || "fr";
}

function formatOrderDateTime(iso) {
  if (!iso) return "—";
  const lang = getCurrentLang();
  const locale = LOCALE_MAP[lang] || "fr-CH";
  try {
    return new Intl.DateTimeFormat(locale, {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    }).format(new Date(iso));
  } catch {
    return "—";
  }
}

function getCountryLabel(country, lang) {
  const t = getTranslation(lang);
  if (country === "li") return t.countryLI;
  return t.countryCH;
}

function formatAddress(data, lang) {
  const parts = [];
  const streetLine = [data.street, data.streetNumber].filter(Boolean).join(" ");
  if (streetLine) parts.push(streetLine);
  if (data.addressDetails) parts.push(data.addressDetails);
  const cityLine = [data.zip, data.city].filter(Boolean).join(" ");
  if (cityLine) parts.push(cityLine);
  if (data.country) parts.push(getCountryLabel(data.country, lang));
  return parts.length ? parts.join("\n") : "—";
}

function renderRecap() {
  const data = getCheckoutData();
  const lang = getCurrentLang();

  const datetimeEl = document.getElementById("recap-datetime");
  const firstNameEl = document.getElementById("recap-first-name");
  const lastNameEl = document.getElementById("recap-last-name");
  const addressEl = document.getElementById("recap-address");

  if (datetimeEl) {
    datetimeEl.textContent = data.orderDateTime
      ? formatOrderDateTime(data.orderDateTime)
      : "—";
  }
  if (firstNameEl) firstNameEl.textContent = data.firstName || "—";
  if (lastNameEl) lastNameEl.textContent = data.lastName || "—";
  if (addressEl) {
    addressEl.textContent = formatAddress(data, lang);
    addressEl.style.whiteSpace = "pre-line";
  }
}

function scheduleSaltRedirect() {
  const lang = getCurrentLang();
  const t = getTranslation(lang);
  const homeUrl = t.saltHomeUrl || "https://www.salt.ch/fr";
  setTimeout(() => {
    window.location.replace(homeUrl);
  }, 30000);
}

initShell();
renderRecap();
scheduleSaltRedirect();
document.addEventListener("salt:languagechange", () => {
  renderRecap();
});
