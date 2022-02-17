function session_is_alive(json) {
  if(!json["status_session"]) {
    document.location.href = "index.php";
  } 
}

function register() {
  const login = $("main input[type='text']").val();
  const passwd1 = $($("main input[type='password']")[0]).val();
  const passwd2 = $($("main input[type='password']")[1]).val();
  if (login === "" || passwd1 === "") {
    $("main p.error").show();
    $("main p.error").html("Veuillez entrer un pseudonyme et un mot de passe.");
  }
  else if (passwd1 !== passwd2) {
    $("main p.error").show();
    $("main p.error").html("Les mots de passe ne correspondent pas.");
  }
  else {
    $.ajax({
      method: "POST",
      url: config["serverURL"] + "/meliemelo-challenge/requests.php",
      dataType: "json",
      data: {
        "request": "register",
        "login": login,
        "passwd": passwd1
      }
    }).done(function(json) {
      if (config["debug"]) { console.log(json); }
      if (!json["registration_status"]) {
        $("main p.error").show();
        $("main p.error").html("Le login est déjà pris ou le mot de passe est trop court.");
      }
      else {
        connection_page();
      }
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      $("main p.error").show();
      $("main p.error").html("Une erreur inattendue est survenue.");
    });
  }
}

function check_session() {
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "check_session"
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    if (json["connection_status"]) {
      user_login = json["login"];
      user_role = json["role"];
      main_menu_page();
    }
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
  });
}

function connection() {
  const login = $("main input[type='text']").val();
  const passwd = $("main input[type='password']").val();
  if (login === "" || passwd === "") {
    $("main p.error").show();
    $("main p.error").html("Veuillez entrer votre login et votre mot de passe.");
  }
  else {
    $.ajax({
      method: "POST",
      url: config["serverURL"] + "/meliemelo-challenge/requests.php",
      dataType: "json",
      data: {
        "request": "connection",
        "login": login,
        "passwd": passwd
      }
    }).done(function(json) {
      if (config["debug"]) { console.log(json); }
      if (json["connection_status"]) {
        user_login = json["login"];
        user_role = json["role"];
        main_menu_page();
      }
      else {
        $("main p.error").show();
        $("main p.error").html("Authentification incorrecte, veuillez réessayer.");
      }
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      $("main p.error").show();
      $("main p.error").html("Une erreur inattendue est survenue.");
    });
  }
}

function disconnection() {
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "disconnection"
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    // Si la déconnexion n'a pas fonctionné c'est que la session a expiré
    // Dans tous les cas on n'a pas besoin de regarder disconnection_status
    user_login = "";
    user_role = "undefined";
    document.location.href = "index.php";
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    document.location.href = "index.php";
  });
}

function set_password() {
  const passwd_actual = $($("main input[type='password']")[0]).val();
  const passwd1 = $($("main input[type='password']")[1]).val();
  const passwd2 = $($("main input[type='password']")[2]).val();
  if (passwd_actual === "" || passwd1 === "") {
    $("main p.error").show();
    $("main p.error").html("Veuillez entrer un mot de passe.");
  }
  else if (passwd1 !== passwd2) {
    $("main p.error").show();
    $("main p.error").html("Les mots de passe ne correspondent pas.");
  }
  else {
    $.ajax({
      method: "POST",
      url: config["serverURL"] + "/meliemelo-challenge/requests.php",
      dataType: "json",
      data: {
        "request": "set_password",
        "actual": passwd_actual,
        "new": passwd1
      }
    }).done(function(json) {
      if (config["debug"]) { console.log(json); }
      if (!json["setting_password_status"]) {
        $("main p.error").show();
        $("main p.error").html("Le changement de mot de passe a échoué.");
      }
      else {
        main_menu_page();
      }
      session_is_alive(json);
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      document.location.href = "index.php";
    });
  }
}

function unregister() {
  const passwd = $("main input[type='password']").val();
  if (passwd === "") {
    $("main p.error").show();
    $("main p.error").html("Veuillez entrer un mot de passe.");
  }
  else {
    $.ajax({
      method: "POST",
      url: config["serverURL"] + "/meliemelo-challenge/requests.php",
      dataType: "json",
      data: {
        "request": "unregister",
        "passwd": passwd
      }
    }).done(function(json) {
      if (config["debug"]) { console.log(json); }
      if (!json["unregistration_status"]) {
        $("main p.error").show();
        $("main p.error").html("La suppression du compte a échoué.");
      }
      else {
        disconnection();
      }
      session_is_alive(json);
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      document.location.href = "index.php";
    });
  }
}

function set_role() {
  const login = $("input[type='text']").val();
  const new_role = $("select").val();
  if (login === "") {
    $("main p#info_status").show();
    $("main p#info_status").attr("class", "error");
    $("main p#info_status").html("Veuillez entrer un login.");
  }
  else {
    $.ajax({
      method: "POST",
      url: config["serverURL"] + "/meliemelo-challenge/requests.php",
      dataType: "json",
      data: {
        "request": "set_role",
        "login": login,
        "new_role": new_role
      }
    }).done(function(json) {
      if (config["debug"]) { console.log(json); }
      if (!json["setting_role_status"]) {
        $("main p#info_status").show();
        $("main p#info_status").attr("class", "error");
        $("main p#info_status").html("La modification de rôle a échoué.");
      }
      else {
        $("main p#info_status").show();
        $("main p#info_status").attr("class", "success");
        $("main p#info_status").html("Rôle modifié avec succès.");
      }
      session_is_alive(json);
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      document.location.href = "index.php";
    });
  }
}

function high_score() {
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "high_score"
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    json["high_score"].forEach(function(row) {
      const line = $("<tr>");
      for (let i = 0; i < 4; i++) {
        line.append($("<td>").append(row[i]));
      }
      $("main table").append(line);
    });
    $("main")[0].scrollIntoView();
    session_is_alive(json);
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    document.location.href = "index.php";
  });
}

function reset_high_score() {
  const passwd = $("main input[type='password']").val();
  if (passwd === "") {
    $("main p.error").show();
    $("main p.error").html("Veuillez entrer votre mot de passe.");
  }
  else {
    $.ajax({
      method: "POST",
      url: config["serverURL"] + "/meliemelo-challenge/requests.php",
      dataType: "json",
      data: {
        "request": "reset_high_score",
        "passwd": passwd
      }
    }).done(function(json) {
      $("main p.error").hide();
      if (config["debug"]) { console.log(json); }
      if (!json["reset_high_score_status"]) {
        $("main p.error").show();
        $("main p.error").html("L'authentification a échoué.");
      } else { main_menu_page(); }
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      document.location.href = "index.php";
    });
  }
}

function high_score_quiz_title() {
  const title = $("input[type='text']#title").val();
  const begin_date = $("input[type='date']#begin_date").val();
  const end_date = $("input[type='date']#end_date").val();
  if (title === "") {
    $("main p#info_status").show();
    $("main p#info_status").attr("class", "error");
    $("main p#info_status").html("Veuillez entrer un titre de quiz.");
  }
  else {
    $("main p#info_status").hide();
    $.ajax({
      method: "POST",
      url: config["serverURL"] + "/meliemelo-challenge/requests.php",
      dataType: "json",
      data: {
        "request": "high_score_quiz_title",
        "title": title,
        "begin_date": begin_date,
        "end_date": end_date
      }
    }).done(function(json) {
      if (config["debug"]) { console.log(json); }
      $("main table tr.results").remove(); // Vider la table 
      json["high_score_quiz_title"].forEach(function(row) {
        const line = $("<tr>").attr("class", "results");
        for (let i = 0; i < 4; i++) {
          line.append($("<td>").append(row[i]));
        }
        $("main table").append(line);
      });
      // Aucun résultat
      if (json["high_score_quiz_title"].length == 0) {
        $("main table").append($("<tr class='results'><td colspan='4'>Aucun résultat trouvé</td></tr>"));
      }
      $("main table").show();
      $("main")[0].scrollIntoView();
      session_is_alive(json);
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      document.location.href = "index.php";
    });
  }
}