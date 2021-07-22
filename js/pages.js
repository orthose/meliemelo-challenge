function register_page() {
  const page = $(`
    <p>Entrez un pseudonyme</p>
    <input type="text">
    <p>Entrez un mot de passe (plus de 8 caractères)</p>
    <input type="password">
    <p>Entrez à nouveau le mot de passe</p>
    <input type="password"><br>
    <button onclick="register()">Création du Compte</button>
    <p class="error" hidden></p>
    `);
  $("main").html(page);
}

function connection_page() {
  check_session();
  const page = $(`
    <p>Entrez votre login</p>
    <input type="text">
    <p>Entrez votre mot de passe</p>
    <input type="password"><br>
    <button onclick="connection()">Connexion</button>
    <p class="error" hidden></p>
    `);
  $("main").html(page);
}

function welcome_page() {
  if (user_login !== "" && user_role !== "undefined") {
    $("section#manage_account").append($(`
      <p></p>
      <button onclick="disconnection()">Se Déconnecter</button>
      <button onclick="set_password_page()">Changer de Mot de Passe</button>
      <button onclick="unregister_page()">Supprimer le Compte</button>
      `));
    let page = `
      <p>Sélectionnez une action parmi celles ci-dessous.</p>
      <button onclick="high_score_page()">Classement des joueurs</button>
      <button onclick="quiz_current_page()">Quiz jouables</button>
      <button onclick="quiz_archive_page()">Quiz archivés</button>
      `;
    if (user_role === "player") {
      $("section#manage_account p").html("Bienvenue <strong>" + user_login + "</strong> heureux de vous revoir, amusez-vous bien !");
    }
    else if (user_role === "admin") {
      $("section#manage_account p").html("Bienvenue <strong>" + user_login + "</strong> heureux de vous revoir ! Quels quiz allez-vous inventer aujourd'hui ?");
      page += `
        <button onclick="quiz_stock_page()">Quiz en stock</button>
        <button onclick="create_quiz_page()">Créer un quiz</button>
        <button onclick="remove_quiz_page()">Supprimer un quiz</button>
        <button onclick="set_role_page()">Changer le rôle d'un utilisateur</button>
        `;
    }
    $("main").html($(page));
  }
}