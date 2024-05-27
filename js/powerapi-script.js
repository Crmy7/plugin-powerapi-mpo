// Définir une fonction pour gérer la soumission du formulaire
function handleFormSubmit(event) {
  event.preventDefault();

  // Vérifier reCAPTCHA
  const recaptchaResponse = grecaptcha.getResponse();
  if (!recaptchaResponse) {
    // Afficher un message d'erreur si reCAPTCHA n'est pas vérifié
    const errorMessage = document.getElementById("error-message");
    errorMessage.style.display = "block";
    return;
  }

  // Obtenir l'e-mail et le mot de passe de l'utilisateur à partir du formulaire
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  // Effectuer la requête POST vers le serveur PowerAPI pour obtenir le jeton
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
        throw new Error("La réponse réseau n'était pas correcte");
      }
      return response.json();
    })
    .then((json) => {
      // Jeton récupéré avec succès, intégrer l'iframe PowerAPI avec le jeton dans l'URL
      const token = json.access_token;

      // Vérifier si l'iframe existe déjà et a un jeton valide dans son URL
      const existingIframe = document.querySelector(
        "#powerapi-container iframe"
      );
      if (existingIframe && existingIframe.src.includes(token)) {
        // Si l'iframe existe déjà et a le bon jeton, l'afficher et masquer le formulaire de connexion
        const containerForm = document.querySelector(".container-form");
        containerForm.style.zIndex = "-1";
        const body = document.getElementsByTagName("body")[0];
        body.style.overflow = "auto";
        const powerapiContainer = document.getElementById("powerapi-container");
        powerapiContainer.style.zIndex = "999";
        powerapiContainer.style.visibility = "visible";
        return;
      }

      // Si l'iframe n'existe pas ou a un jeton incorrect, intégrer l'iframe avec le jeton correct
      embedPowerAPIFrame(token);

      // Changer le z-index de container-form à -1
      const containerForm = document.querySelector(".container-form");
      containerForm.style.zIndex = "-1";

      // Changer le z-index de powerapi-container à 1
      const powerapiContainer = document.getElementById("powerapi-container");
      powerapiContainer.style.zIndex = "999";
      powerapiContainer.style.visibility = "visible";
      const body = document.getElementsByTagName("body")[0];
      body.style.overflow = "hidden";

      // Masquer le message d'erreur s'il a été précédemment affiché
      const errorMessage = document.getElementById("error-message");
      errorMessage.style.display = "none";
    })
    .catch((error) => {
      // Gérer l'erreur (par exemple, afficher un message d'erreur à l'utilisateur)
      console.error("Erreur :", error);

      // Afficher le message d'erreur
      const errorMessage = document.getElementById("error-message");
      errorMessage.style.display = "block";
    });
}

// Ajouter un écouteur d'événements au formulaire pour appeler la fonction handleFormSubmit lors de la soumission
document
  .getElementById("login-form")
  .addEventListener("submit", handleFormSubmit);

function embedPowerAPIFrame(token) {
  const BASE_URL = "https://app.powerapi.com";
  const cssParam =
    "https://ma-presence.online/wp-content/plugins/powerapi-integration/css/mpo.css";
  const url = new URL(BASE_URL);
  url.searchParams.append("access_token", token);

  // Ajouter le paramètre CSS à l'URL manuellement (sans encodage d'URL)
  url.search = url.search + "&tab=reviews" + "&css=" + cssParam;

  // Générer l'élément iframe avec le jeton dans l'URL
  const iframe = document.createElement("iframe");
  iframe.title = "PowerAPI";
  iframe.src = url.toString();
  iframe.style.height = "100%";
  iframe.setAttribute(
    "sandbox",
    "allow-same-origin allow-forms allow-scripts allow-popups"
  ); // Ajouter l'attribut 'sandbox'
  iframe.setAttribute("allow", "clipboard-write");

  // Effacer tout contenu précédent dans
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
    containerForm.style.zIndex = "998";
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
