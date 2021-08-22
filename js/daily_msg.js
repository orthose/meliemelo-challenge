function set_daily_msg_page() {
  if (user_login !== "" && user_role !== "undefined") {
    push_state(17);
    const page = $(`
      <h2>Modification du message d'accueil</h2>
      <p class="form">Nouveau message</p>
      <textarea id="daily_msg"></textarea>
      <button onclick="set_daily_msg()">Enregistrer le message</button>
      <p class="error" hidden></p>
      `);
    $("main").html(page);
  }
}

function set_daily_msg() {
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "set_daily_msg",
      "msg": $("#daily_msg").val()
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    if (!json["set_daily_msg_status"]) {
      $("main p.error").show();
      $("main p.error").html("L'enregistrement du message a échoué.");
    }
    else {
      $("main p.error").show();
      $("main p.error").attr("class", "success")
      $("main p.success").html("Le message a bien été enregistré.");
    }
    session_is_alive(json);
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    document.location.href = "index.php";
  })
}