function register_page() {
  push_state(1);
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
  push_state(2);
  check_session();
  const page = $(`
    <p>Entrez votre login</p>
    <input type="text">
    <p>Entrez votre mot de passe</p>
    <input type="password" onkeypress="(function(event) {if (event.keyCode === 13) {connection();}})(event)"><br>
    <button onclick="connection()">Connexion</button>
    <p class="error" hidden></p>
    `);
  $("main").html(page);
}

function main_menu_page() {
  push_state(3);
  if (user_login !== "" && user_role !== "undefined") {
    $("section#manage_account").html($(`
      <p></p>
      <button onclick="main_menu_page()">Menu principal</button>
      <button onclick="disconnection()">Se déconnecter</button>
      <button onclick="set_password_page()">Changer de mot de passe</button>
      <button onclick="unregister_page()">Supprimer le compte</button>
      `));
    let page = `
      <p>Sélectionnez une action parmi celles ci-dessous.</p>
      <hr>
      <button onclick="high_score_page()">Classement</button>
      <button onclick="bug_report_page()">Reporter un bogue</button>
      <hr>
      <button onclick="quiz_current_page()">Quiz jouables</button>
      <button onclick="quiz_archive_page()">Quiz archivés</button>
      <button onclick="quiz_answered_page()">Voir mes réponses</button>
      <hr>
      `;
    if (user_role === "player") {
      $("section#manage_account p").html("Bienvenue <strong>" + user_login + "</strong> heureux de vous revoir, amusez-vous bien&nbsp;!");
    }
    else if (user_role === "admin") {
      $("section#manage_account p").html("Bienvenue <strong>" + user_login + "</strong> heureux de vous revoir&nbsp;! Quels quiz allez-vous inventer aujourd'hui&nbsp;?");
      page += `
        <button onclick="quiz_stock_page()">Quiz en stock</button>
        <button onclick="quiz_current_not_playable_page()">Quiz jouables par les autres</button>
        <hr>
        <button onclick="create_quiz_page()">Créer un quiz</button>
        <button onclick="remove_quiz_page()">Supprimer un quiz</button>
        <hr>
        <button onclick="set_daily_msg_page()">Changer le message du jour</button>
        <button onclick="set_role_page()">Changer de rôle</button>
        <hr>
        `;
    }
    $("main").html($(page));
  }
}

function set_password_page() {
  push_state(4);
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>Changement de mot de passe</h2>
      <p>Entrez votre mot de passe actuel</p>
      <input type="password">
      <p>Entrez votre nouveau mot de passe</p>
      <input type="password">
      <p>Confirmez votre nouveau mot de passe</p>
      <input type="password"><br>
      <button onclick="set_password()">Changer le mot de passe</button>
      <p class="error" hidden></p>
      `);
    $("main").html(page);
  }
}

function unregister_page() {
  push_state(5);
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>Suppression du compte</h2>
      <p class="warning">Attention, si vous continuez vous perdrez toutes vos données. La suppression de votre compte est définitive. Notez que la suppression du compte est impossible si vous avez créé des quiz.</p>
      <p>Entrez votre mot de passe</p>
      <input type="password"><br>
      <button onclick="unregister()">Supprimer le compte</button>
      <p class="error" hidden></p>
      `);
    $("main").html(page);
  }
}

function set_role_page() {
  push_state(6);
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>Changement de rôle</h2>
      <p class="form">Utilisateur</p>
      <input type="text">
      <p class="form">Autorisations</p>
      <select>
        <option value="player">Joueur</option>
        <option value="admin">Administrateur</option>
      </select>
      <button onclick="set_role()">Confirmer</button>
      <p id="info_status" hidden></p>
      `);
    $("main").html(page);
  }
}

function high_score_page() {
  push_state(7);
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>Classement des joueurs</h2>
      <table>
      <tr><th>Login</th><th>Points</th><th>Succès</th><th>Échecs</th></tr>
      </table>
      `);
    $("main").html(page);
    high_score();
  }
}