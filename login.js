const loginForm = document.getElementById("loginForm");
const usernameInput = document.getElementById("username");
const passwordInput = document.getElementById("password");
const message = document.getElementById("message");

const validUsername = "ajitsingh73020";
const validPassword = "ajit73020";

loginForm.addEventListener("submit", function(event) {
  event.preventDefault();

  const username = usernameInput.value.trim();
  const password = passwordInput.value.trim();

  if (username === "" || password === "") {
    message.textContent = "Enter your username and password";
    message.style.color = "red";
  } else if (username === validUsername && password === validPassword) {
    localStorage.setItem("loggedInUser", username);
    window.location.href = "home.html";
  } else {
    message.textContent = "Invalid username or password";
    message.style.color = "red";
  }
});
