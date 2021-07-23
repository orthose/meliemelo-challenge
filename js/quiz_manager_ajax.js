function show_quiz(tag) {
  $(tag).next().show();
  $(tag).attr("onclick", "hide_quiz(this)");
}

function hide_quiz(tag) {
  $(tag).next().hide();
  $(tag).attr("onclick", "show_quiz(this)");
}
    
function quiz_current() {
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "quiz_current"
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    json["quiz_current"].forEach(function(row) {
      const line = $("<div class='select_quiz'>");
      line.append($(`<button onclick="show_quiz(this)">` + row[7] + `</button>`));
      line.append($(`
        <div hidden>
        <table>
        <tr><th>Numéro</th><th>Auteur</th></tr>
        <tr><td>` + row[0] + `</td><td>` + row[1] + `</td></tr>
        <tr><th>Ouverture</th><th>Fermeture</th></tr>
        <tr><td>` + row[2] + `</td><td>` + row[3] + `</td></tr>
        <tr><th>Difficulté</th><th>Points</th></tr>
        <tr><td>` + row[4] + `</td><td>` + row[5] + `</td></tr>
        </table>
        <button class="answer">Répondre</button>
        </div>
        `));
      line.find("button.answer").on("click", function() { answer_quiz_page(row[0], row[6], row[7], row[8], json["responses"][row[0]]); });
      $("main").append(line);
    });
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    document.location.href = "index.php";
  })
}

function quiz_stock_archive(state) {
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "quiz_" + state
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    json["quiz_current"].forEach(function(row) {
      const line = $("<div class='select_quiz'>");
      line.append($(`<button onclick="show_quiz(this)">` + row[7] + `</button>`));
      line.append($(`
        <div hidden>
        <table>
        <tr><th>Numéro</th><th>Auteur</th></tr>
        <tr><td>` + row[0] + `</td><td>` + row[1] + `</td></tr>
        <tr><th>Ouverture</th><th>Fermeture</th></tr>
        <tr><td>` + row[2] + `</td><td>` + row[3] + `</td></tr>
        <tr><th>Difficulté</th><th>Points</th></tr>
        <tr><td>` + row[4] + `</td><td>` + row[5] + `</td></tr>
        </table>
        <button class="answer">Voir les réponses</button>
        </div>
        `));
      line.find("button.answer").on("click", function() { show_quiz_page(row[0], row[6], row[7], row[8], json["responses"][row[0]]); });
      $("main").append(line);
    });
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    document.location.href = "index.php";
  })
}

function quiz_archive() {
  quiz_stock_archive("archive");
}

function quiz_stock() {
  quiz_stock_archive("stock");
}

function answer_quiz(quiz_id) {
  const input = $("main input[name='quiz']:checked");
  let responses = [];
  for (let i = 0; i < input["length"]; i++) {
    responses.push($(input[i]).val());
  }
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "answer_quiz",
      "quiz_id": quiz_id,
      "responses": responses
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    if (!json["answer_quiz_status"]) {
      $("main p.error").show();
      $("main p.error").html("Votre réponse n'a pas été prise en compte. Vous ne pouvez pas répondre à un quiz si vous l'avez créé.");
    }
    else {
      $("main p.error").show();
      $("main p.error").attr("class", "success")
      $("main p.success").html("Votre réponse a été enregistrée. Vous avez gagné " + json["points"] + " point(s).");
    }
    $("main button").html("Quiz jouables");
    $("main button").attr("onclick", "quiz_current_page()");
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    $("main p.error").show();
    $("main p.error").html("Une erreur inattendue est survenue.");
    document.location.href = "index.php";
  })
}

function create_quiz() {
  const title = $("main input#title").val();
  const question = $("main textarea#question").val();
  const open = $("main input#open").val();
  const close = $("main input#close").val();
  const difficulty = $("main input#difficulty").val();
  const points = $("main input#points").val();
  const type = $("main select#type").val();
  let responses = [];
  let responses_jquery = $("main .response:disabled");
  for (let i = 0; i < responses_jquery["length"]; i++) {
    const response = $(responses_jquery[i]).val();
    const valid = $(responses_jquery[i]).next().val();
    responses.push({"response": response, "valid": valid});
  }
  console.log(responses);
  if (title === "") {
    $("main p.error").show();
    $("main p.error").html("Veuillez entrer un titre.");
  }
  else if (question === "") {
    $("main p.error").show();
    $("main p.error").html("Veuillez entrer une question.");
  }
  else if (open === "" || close === "") {
    $("main p.error").show();
    $("main p.error").html("Veuillez entrer les dates d'ouverture et de fermeture.");
  }
  else if (responses.length === 0) {
    $("main p.error").show();
    $("main p.error").html("Veuillez entrer au moins une réponse valide.");
  }
  else {
    $.ajax({
      method: "POST",
      url: config["serverURL"] + "/meliemelo-challenge/requests.php",
      dataType: "json",
      data: {
        "request": "create_quiz",
        "open": open,
        "close": close,
        "difficulty": difficulty,
        "points": points,
        "type": type,
        "title": title,
        "question": question,
        "responses": responses
      }
    }).done(function(json) {
      if (config["debug"]) { console.log(json); }
      if (!json["create_quiz_status"]) {
        $("main p.error").show();
        $("main p.error").html("La création du quiz a échoué.");
      }
      else {
        welcome_page();
      }
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      $("main p.error").show();
      $("main p.error").html("Une erreur inattendue est survenue.");
      document.location.href = "index.php";
    })
  }
}