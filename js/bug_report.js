function bug_report_page() {
  if (user_login !== "" && user_role !== "undefined") {
    push_state(function() {bug_report_page();});
    const page = $(`
      <h2>Report de bogue</h2>
      <p>
        Lorsque vous reportez un bogue, pensez à bien préciser dans quelles circonstances
        vous l'avez rencontré, et de manière générale toute information qui pourrait
        aider à sa résolution. Cette page peut également servir de lieu d'échange sur les
        améliorations qui pourraient être apportées à l'application.
      </p>
      <p class="form">Bogue à reporter</p>
      <p>Décrivez votre problème dans le cadre ci-dessous.</p>
      <textarea id="bug_report"></textarea>
      <button onclick="bug_report()">Envoyer le rapport de bogue</button>
      <p class="error" hidden></p>
      `);
    $("main").html(page);
    bug_reported();
  }
}

function bug_reported() {
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "bug_reported"
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    json["bug_reported"].forEach(function(bug) {
      const jquery_bug = $(`
        <div class="bug_report" id="` + bug["id"] + `">
        <table>
        <tr><th>Login</th><th>Date</th></tr>
        <tr><td>` + bug["login"] + `</td><td>` + bug["date"] + `</td></tr>
        </table>
        <p class="bug_msg">` + bug["bug"] + `</p>
        </div>
        `);
      bug["responses"].forEach(function(response) {
        const jquery_response = $(`
          <table>
          <tr><th>Login</th><th>Date</th></tr>
          <tr><td>` + response["login"] + `</td><td>` + response["date"] + `</td></tr>
          </table>
          <p class="bug_response">` + response["response"] + `</p>
          `);
        jquery_bug.append(jquery_response);
      });
      const answer = $(`
        <textarea></textarea>
        <button onclick="answer_bug(` + bug["id"] + `)">Répondre</button>
        `);
      jquery_bug.append(answer); 
      $("main").append(jquery_bug);
    });
    session_is_alive(json);
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    document.location.href = "index.php";
  })
}

function bug_report() {
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "bug_report",
      "bug": $("textarea#bug_report").val()
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    if (!json["bug_report_status"]){
      $("p.error").show();
      $("p.error").html("L'enregistrement du rapport de bogue a échoué.");
    }
    else { bug_report_page(); }
    session_is_alive(json);
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    document.location.href = "index.php";
  })
}

function answer_bug(id) {
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "answer_bug",
      "id": id,
      "response": $("#"+id+" textarea").val()
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    if (!json["answer_bug_status"]){
      $("p.error").show();
      $("p.error").html("L'enregistrement de la réponse a échoué.");
    }
    else { bug_report_page(); }
    session_is_alive(json);
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    document.location.href = "index.php";
  })
}