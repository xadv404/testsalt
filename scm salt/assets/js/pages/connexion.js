const emailInput = document.getElementById("email");
const submitBtn = document.getElementById("submit-btn");
const loginForm = document.querySelector(".login-form");

function isValidEmail(value) {
  return /^[^\s@]+@[^\s@]+$/.test(value.trim());
}

function updateSubmitButton() {
  submitBtn.disabled = !isValidEmail(emailInput.value);
}

initShell();

emailInput.addEventListener("input", updateSubmitButton);
updateSubmitButton();

loginForm.addEventListener("submit", (e) => {
  e.preventDefault();
  if (!submitBtn.disabled) {
    saveCheckoutData({ email: emailInput.value.trim() });
    navigateWithLoading("informations");
  }
});
