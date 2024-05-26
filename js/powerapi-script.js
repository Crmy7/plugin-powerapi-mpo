// Define a function to handle the form submission
function handleFormSubmit(event) {
  event.preventDefault();

  // Verify reCAPTCHA
  const recaptchaResponse = grecaptcha.getResponse();
  if (!recaptchaResponse) {
    // Display an error message if reCAPTCHA is not verified
    const errorMessage = document.getElementById("error-message");
    // errorMessage.textContent = 'Veuillez cocher la case reCAPTCHA.';
    errorMessage.style.display = "block";
    return;
  }

  // Get the user's email and password from the form
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  // Make the POST request to the PowerAPI server to get the token
  const url = "https://api.powerapi.com/oauth/token";
  const headers = {
    "Content-Type": "application/vnd.api+json",
    Accept: "application/vnd.api+json",
  };
  const data = {
    scope: "trusted public",
    grant_type: "password",
    email: email,
    password: password,
  };

  fetch(url, {
    method: "POST",
    headers: headers,
    body: JSON.stringify(data),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((json) => {
      // Token retrieved successfully, embed the PowerAPI iframe with the token in the URL
      const token = json.access_token;

      // Check if the iframe already exists and has a valid token in its URL
      const existingIframe = document.querySelector(
        "#powerapi-container iframe"
      );
      if (existingIframe && existingIframe.src.includes(token)) {
        // If the iframe already exists and has the correct token, display it and hide the login form
        const containerForm = document.querySelector(".container-form");
        containerForm.style.zIndex = "-1";
        const body = document.getElementsByTagName("body")[0];
        body.style.overflow = "auto";
        const powerapiContainer = document.getElementById("powerapi-container");
        powerapiContainer.style.zIndex = "100";
        powerapiContainer.style.visibility = "visible";
        return;
      }

      // If the iframe doesn't exist or has an incorrect token, embed the iframe with the correct token
      embedPowerAPIFrame(token);

      // Change the z-index of the container-form to -1
      const containerForm = document.querySelector(".container-form");
      containerForm.style.zIndex = "-1";

      // Change the z-index of the powerapi-container to 1
      const powerapiContainer = document.getElementById("powerapi-container");
      powerapiContainer.style.zIndex = "100";
      powerapiContainer.style.visibility = "visible";
      const body = document.getElementsByTagName("body")[0];
      body.style.overflow = "hidden";

      // Hide the error message if it was previously displayed
      const errorMessage = document.getElementById("error-message");
      errorMessage.style.display = "none";
    })
    .catch((error) => {
      // Handle the error (e.g., show an error message to the user)
      console.error("Error:", error);

      // Display the error message
      const errorMessage = document.getElementById("error-message");
      // errorMessage.textContent = 'La connexion a échoué. Veuillez vérifier vos informations de connexion.';
      errorMessage.style.display = "block";
    });
}

// Add an event listener to the form to call the handleFormSubmit function on submit
document
  .getElementById("login-form")
  .addEventListener("submit", handleFormSubmit);

function embedPowerAPIFrame(token) {
  const BASE_URL = "https://app.powerapi.com";
  const cssParam =
    "https://ma-presence.online/wp-content/themes/ma-presence-online/css/PowerAPIStyles.css";
  const url = new URL(BASE_URL);
  url.searchParams.append("access_token", token);

  // Append the CSS parameter to the URL manually (without URL encoding)
  url.search = url.search + "&tab=reviews" + "&css=" + cssParam;

  // Generate the iframe element with the token in the URL
  const iframe = document.createElement("iframe");
  iframe.title = "PowerAPI";
  iframe.src = url.toString();
  iframe.style.height = "100%"; // Set the iframe height to 100% of its container
  iframe.setAttribute(
    "sandbox",
    "allow-same-origin allow-forms allow-scripts allow-popups"
  ); // Add the 'sandbox' attribute
  iframe.setAttribute("allow", "clipboard-write");

  // Clear any previous content in the container
  const container = document.getElementById("powerapi-container");
  container.innerHTML = "";

  // Append the iframe to the container
  container.appendChild(iframe);

  // Add a logout button to the top left corner of the iframe
  const logoutButton = document.createElement("button");
  logoutButton.classList.add("btn-logout");
  logoutButton.textContent = "se déconnecter";
  logoutButton.style.position = "absolute";
  logoutButton.style.top = "2.50em";
  logoutButton.style.right = "26%";
  logoutButton.addEventListener("click", () => {
    // When the logout button is clicked, remove the iframe and reset the login form
    container.innerHTML = "";
    const containerForm = document.querySelector(".container-form");
    containerForm.style.zIndex = "3";
    const powerapiContainer = document.getElementById("powerapi-container");
    powerapiContainer.style.zIndex = "-1";
    powerapiContainer.style.visibility = "hidden";
    const errorMessage = document.getElementById("error-message");
    errorMessage.style.display = "none";
    const body = document.getElementsByTagName("body")[0];
    body.style.overflow = "auto";
  });
  container.appendChild(logoutButton);
}
