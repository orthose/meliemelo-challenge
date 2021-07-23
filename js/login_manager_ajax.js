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
        welcome_page();
      }
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      $("main p.error").show();
      $("main p.error").html("Une erreur inattendue est survenue.");
    })
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
      welcome_page();
    }
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    return false;
  })
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
        welcome_page();
      }
      else {
        $("main p.error").show();
        $("main p.error").html("Authentification incorrecte, veuillez réessayer.");
      }
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      $("main p.error").show();
      $("main p.error").html("Une erreur inattendue est survenue.");
    })
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
    if (json["disconnection_status"]) {
      user_login = "";
      user_role = "undefined";
      document.location.href = "index.php";
    }
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
  })
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
        welcome_page();
      }
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      $("main p.error").show();
      $("main p.error").html("Une erreur inattendue est survenue.");
    })
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
        $("main p.error").html("Le suppression du compte a échoué.");
      }
      else {
        disconnection();
      }
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      $("main p.error").show();
      $("main p.error").html("Une erreur inattendue est survenue.");
    })
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
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
  })
}