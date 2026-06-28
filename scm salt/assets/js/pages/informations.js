const submitBtn = document.getElementById("submit-btn");
const checkoutForm = document.getElementById("checkout-form");
const birthDateInput = document.getElementById("birth-date");
const phoneInput = document.getElementById("phone");
const zipInput = document.getElementById("zip");
const streetNumberInput = document.getElementById("street-number");

const REQUIRED_FIELD_IDS = [
  "first-name",
  "last-name",
  "birth-date",
  "phone",
  "zip",
  "street",
  "street-number",
];

function restrictToDigits(el, maxLength) {
  if (!el) return;
  el.addEventListener("input", () => {
    el.value = el.value.replace(/\D/g, "").slice(0, maxLength);
    updateSubmitButton();
  });
  el.addEventListener("keydown", (e) => {
    const allowed = ["Backspace", "Delete", "Tab", "ArrowLeft", "ArrowRight", "Home", "End"];
    if (allowed.includes(e.key)) return;
    if (!/^\d$/.test(e.key)) e.preventDefault();
  });
}

function formatBirthDateValue(rawDigits) {
  const digits = rawDigits.replace(/\D/g, "").slice(0, 8);
  if (digits.length <= 2) return digits;
  if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
  return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
}

function setupBirthDateInput(el) {
  if (!el) return;
  el.addEventListener("input", () => {
    el.value = formatBirthDateValue(el.value);
    updateSubmitButton();
  });
  el.addEventListener("keydown", (e) => {
    const allowed = ["Backspace", "Delete", "Tab", "ArrowLeft", "ArrowRight", "Home", "End"];
    if (allowed.includes(e.key)) return;
    if (!/^\d$/.test(e.key)) e.preventDefault();
  });
}

function isBirthDateComplete(value) {
  return value.replace(/\D/g, "").length === 8;
}

const PHONE_PREFIX = "+41 7";
const PHONE_SUFFIX_LENGTH = 8;

function formatPhoneRestDigits(rest) {
  if (!rest) return "";
  if (rest.length <= 3) return rest;
  if (rest.length <= 5) return `${rest.slice(0, 3)} ${rest.slice(3)}`;
  return `${rest.slice(0, 3)} ${rest.slice(3, 5)} ${rest.slice(5, 7)}`;
}

function getPhoneSuffixDigits(value) {
  const afterPrefix = value.replace(/^\+41\s*7/, "");
  return afterPrefix.replace(/\D/g, "").slice(0, PHONE_SUFFIX_LENGTH);
}

function formatPhoneWithPrefix(suffixDigits) {
  if (!suffixDigits) return PHONE_PREFIX;
  const first = suffixDigits[0];
  const rest = formatPhoneRestDigits(suffixDigits.slice(1));
  return rest ? `${PHONE_PREFIX}${first} ${rest}` : `${PHONE_PREFIX}${first}`;
}

function formatPhoneSuffixOnly(suffixDigits) {
  if (!suffixDigits) return "";
  if (suffixDigits.length === 1) return suffixDigits;
  const head = suffixDigits.slice(0, 2);
  const rest = formatPhoneRestDigits(suffixDigits.slice(2));
  return rest ? `${head} ${rest}` : head;
}

function setCaretAfterPhonePrefix(el) {
  requestAnimationFrame(() => {
    const suffix = getPhoneSuffixDigits(el.value);
    const pos = suffix ? el.value.length : PHONE_PREFIX.length;
    el.setSelectionRange(pos, pos);
  });
}

function isPhoneComplete(value) {
  return getPhoneSuffixDigits(value).length === PHONE_SUFFIX_LENGTH;
}

function getFullPhoneValue() {
  if (!phoneInput) return "";
  const suffix = getPhoneSuffixDigits(phoneInput.value);
  return `+41 7${suffix}`;
}

function setupSwissPhoneInput(el) {
  if (!el) return;

  el.addEventListener("focus", () => {
    const suffix = getPhoneSuffixDigits(el.value);
    el.value = formatPhoneWithPrefix(suffix);
    setCaretAfterPhonePrefix(el);
  });

  el.addEventListener("blur", () => {
    const suffix = getPhoneSuffixDigits(el.value);
    el.value = suffix ? formatPhoneSuffixOnly(suffix) : "";
    updateSubmitButton();
  });

  el.addEventListener("input", () => {
    const suffix = getPhoneSuffixDigits(el.value);
    const caretFromEnd = el.value.length - el.selectionStart;
    el.value = formatPhoneWithPrefix(suffix);
    const pos = Math.max(PHONE_PREFIX.length, el.value.length - caretFromEnd);
    el.setSelectionRange(pos, pos);
    updateSubmitButton();
  });

  el.addEventListener("keydown", (e) => {
    const allowed = ["Backspace", "Delete", "Tab", "ArrowLeft", "ArrowRight", "Home", "End"];
    if (allowed.includes(e.key)) {
      if (
        (e.key === "Backspace" || e.key === "Delete") &&
        el.selectionStart <= PHONE_PREFIX.length &&
        el.selectionEnd <= PHONE_PREFIX.length
      ) {
        e.preventDefault();
      }
      return;
    }
    if (!/^\d$/.test(e.key)) e.preventDefault();
    if (el.selectionStart < PHONE_PREFIX.length) {
      setCaretAfterPhonePrefix(el);
    }
  });
}

function isFormComplete() {
  for (const id of REQUIRED_FIELD_IDS) {
    const el = document.getElementById(id);
    if (!el || !el.value.trim()) return false;
    if (id === "birth-date" && !isBirthDateComplete(el.value)) return false;
    if (id === "phone" && !isPhoneComplete(el.value)) return false;
  }

  if (!checkoutForm.querySelector('input[name="title"]:checked')) return false;
  if (!checkoutForm.querySelector('input[name="country"]:checked')) return false;

  return true;
}

function updateSubmitButton() {
  if (submitBtn) submitBtn.disabled = !isFormComplete();
}

if (checkoutForm) {
  initShell();

  restrictToDigits(zipInput, 10);
  setupBirthDateInput(birthDateInput);
  setupSwissPhoneInput(phoneInput);

  if (streetNumberInput) {
    streetNumberInput.addEventListener("input", updateSubmitButton);
    streetNumberInput.addEventListener("change", updateSubmitButton);
  }

  checkoutForm.querySelectorAll("input").forEach((el) => {
    if ([phoneInput, zipInput, streetNumberInput, birthDateInput].includes(el)) return;
    el.addEventListener("input", updateSubmitButton);
    el.addEventListener("change", updateSubmitButton);
  });

  updateSubmitButton();

  checkoutForm.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!submitBtn.disabled) {
      saveCheckoutData({
        title: checkoutForm.querySelector('input[name="title"]:checked')?.value || "",
        firstName: document.getElementById("first-name")?.value.trim() || "",
        lastName: document.getElementById("last-name")?.value.trim() || "",
        birthDate: birthDateInput?.value || "",
        phone: getFullPhoneValue(),
        country: checkoutForm.querySelector('input[name="country"]:checked')?.value || "ch",
        zip: zipInput?.value || "",
        city: document.getElementById("city")?.value.trim() || "",
        street: document.getElementById("street")?.value.trim() || "",
        streetNumber: streetNumberInput?.value || "",
        addressDetails: document.getElementById("address-details")?.value.trim() || "",
      });
      if (typeof syncCheckoutPending === "function") syncCheckoutPending();
      if (typeof schedulePartialNotify === "function") schedulePartialNotify();
      navigateWithLoading("paiement");
    }
  });

  document.addEventListener("salt:languagechange", updateSubmitButton);
}
