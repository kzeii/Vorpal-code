const password = document.getElementById("password");
const toggle = document.getElementById("togglePassword");

toggle.onclick = () => {
  const show = password.type === "password";
  password.type = show ? "text" : "password";
  toggle.ariaPressed = show;
  toggle.ariaLabel = show ? "Hide password" : "Show password";
  toggle.innerHTML = `<i class="bi bi-eye${show ? "-slash" : ""}" aria-hidden="true"></i>`;
};

