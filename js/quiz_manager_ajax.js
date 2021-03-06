function list_quiz(state, string_quiz_button, fun_quiz_button, num_page = 0) {
  
  function explain_difficulty(difficulty) {
    switch(Number(difficulty)) {
      case 1: return "Stupide";
      case 2: return "Trivial";
      case 3: return "Novice";
      case 4: return "Facile";
      case 5: return "Normal";
      case 6: return "Intermédiaire";
      case 7: return "Difficile";
      case 8: return "Confirmé";
      case 9: return "Expert";
      case 10: return "Impossible";
    }
  }
  
  function list_quiz_ajax() {
    const data = {
      "request": "quiz_" + state,
      "num_page": num_page
    };
    if (state === "answered_others") {
      data.login = $("#player").val();
    }
    $.ajax({
      method: "POST",
      url: config["serverURL"] + "/meliemelo-challenge/requests.php",
      dataType: "json",
      data: data
    }).done(function(json) {
      if (config["debug"]) { console.log(json); }
      if (json["quiz"].length === 0) {
        $("main div#list_quiz").html("<p class='info'>Aucun quiz trouvé</p>")
      }
      json["quiz"].forEach(function(row) {
        const line = $(`<div class='select_quiz_folded' id="` + row[0] + `">`);
        if (state === "answered") {
          let button = $(`<button onclick="show_quiz(this)">` + row[7] + `</button>`);
          if (row[9] == 0) {
            button.addClass("invalid");
          }
          else if (row[9] == 1) {
            button.addClass("valid");
          }
          line.append(button);
        }
        else {
          line.append($(`<button onclick="show_quiz(this)">` + row[7] + `</button>`));
        }
        line.append($(`
          <div hidden>
          <table>
          <tr><th>Numéro</th><th>Auteur</th></tr>
          <tr><td>` + row[0] + `</td><td>` + row[1] + `</td></tr>
          <tr><th>Ouverture</th><th>Fermeture</th></tr>
          <tr><td>` + row[2] + `</td><td>` + row[3] + `</td></tr>
          <tr><th>Difficulté</th><th>Points</th></tr>
          <tr><td>` + explain_difficulty(row[4]) + `</td><td>` + row[5] + `</td></tr>
          </table>
          ` + string_quiz_button + `
          </div>
          `));
        fun_quiz_button(line, row, json, state);
        $("main div#list_quiz").append(line);
      });
      $("main")[0].scrollIntoView();
      session_is_alive(json);
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      document.location.href = "index.php";
    });
  }
  
  // Sélection de la page de quiz à requêter
  const previous = $(`<button id="previous_page">Page&nbsp;précédente</button>`).on("click", function() {
    if (0 < num_page) {
      $("main div#list_quiz").html(""); num_page--; 
      $("main span#num_page").html(num_page + 1); list_quiz_ajax();
    }
  });
  const next = $(`<button id="next_page">Page&nbsp;suivante</button>`).on("click", function() {
    if ($("main div#list_quiz div").length !== 0) {
      $("main div#list_quiz").html(""); num_page++;
      $("main span#num_page").html(num_page + 1); list_quiz_ajax();
    }
  });
  const show_num_page = $(`<span id="num_page">1</span>`);
  $("main div#select_page").html(previous.add(show_num_page).add(next));
   
  list_quiz_ajax();
}

const show_quiz_button = `<button class="show">Voir la réponse</button>`;
const fun_show_quiz_button = function(line, row, json, state) {
  line.find("button.show").on("click", function() { 
    show_quiz_page(state, row[0], row[6], row[7], row[8], json["responses"][row[0]]); 
  });
};

function quiz_current() {
  list_quiz("current", `<button class="answer">Répondre</button>`, 
  function(line, row, json) {
    line.find("button.answer").on("click", function() { 
      answer_quiz_page(row[0], row[6], row[7], row[8], json["responses"][row[0]]); 
    });
  });
}

function quiz_archive() {
  list_quiz("archive", show_quiz_button, fun_show_quiz_button);
}

function quiz_stock() {
  list_quiz("stock", show_quiz_button, fun_show_quiz_button);
}

function quiz_editable_remove() {
  list_quiz("editable", `<button class="remove">Supprimer</button><p class="error" hidden></p>`,
  function(line, row) { 
    line.find("button.remove").on("click", function() { 
      remove_quiz(this, row[0]); 
    });
  });
}

function quiz_editable_modify() {
  list_quiz("editable", `<button class="edit">Modifier</button>`,
  function(line, row, json) { 
    line.find("button.edit").on("click", function() { 
      modify_quiz_page(row[0], row[2], row[3], row[4], row[5], row[6], row[7], row[8], json["responses"][row[0]]); 
    });
  });
}

function quiz_current_not_playable() {
  list_quiz("current_not_playable", show_quiz_button, fun_show_quiz_button);
}

function quiz_answered() {
  list_quiz("answered", show_quiz_button, fun_show_quiz_button);
}

function quiz_answered_others() {
  list_quiz("answered_others", show_quiz_button, fun_show_quiz_button);
}

function quiz_stockable() {
  list_quiz("stockable", 
  `<p>Date d'ouverture</p>
  <input class="open" type="date">
  <p>Date de fermeture</p>
  <input class="close" type="date"><br>
  <button class="stock">Stocker</button>
  <p class="error" hidden></p>`,
  function(line, row) {
    // Remplissage automatique des dates
    auto_init_date(line, "input.open", "input.close");
    line.find("input.open").change(function() { 
      auto_fill_close_date("main #"+row[0]+" input.open", "main #"+row[0]+" input.close"); });
    line.find("button.stock").on("click", function() { stock_quiz(this, row[0]); });
  });
}

function answer_quiz(type, quiz_id) {
  function input_type(type) {
    if (type.startsWith("text")) {
      return $("main input[type='text']");
    }
    else {
      return $("main input[name='quiz']:checked");
    }
  }
  const input = input_type(type);
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
      $("main p.error").html("Votre réponse n'a pas été prise en compte.");
    }
    else {
      $("main p.error").show();
      if (json["points"] > 0) {
        $("main p.error").attr("class", "success");
        $("main p.success").html("Bravo&nbsp;! Vous gagnez " + json["points"] + " point(s).");
      } else {
        $("main p.error").html("Dommage&nbsp;! Mauvaise réponse&hellip;");
      }
    }
    $("main button").replaceWith($(`<button onclick="quiz_current_page()">Quiz jouables</button>`));
    session_is_alive(json);
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    document.location.href = "index.php";
  })
}

function get_fields_quiz(overwrite_responses=false, ignore_empty_responses=false) {
  let res = {};
  const title = $("main input#title").val();
  const question = $("main textarea#question").val();
  const open = $("main input#open").val();
  const close = $("main input#close").val();
  const difficulty = $("main input#difficulty").val();
  const points = $("main input#points").val();
  const type = $("main select#type").val();
  let responses = [];
  let responses_jquery = overwrite_responses ? $("main .response") : 
  $("main .response:not(div#original_responses .response)");
  for (let i = 0; i < responses_jquery["length"]; i++) {
    const response = $(responses_jquery[i]).val();
    if (response !== "") {
      const valid = $(responses_jquery[i]).next().val();
      responses.push({"response": response, "valid": valid});
    }
  }
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
  else if (responses.length === 0 && !ignore_empty_responses) {
    $("main p.error").show();
    $("main p.error").html("Veuillez entrer au moins une réponse.");
  }
  else {
    res = {
      "open": open,
      "close": close,
      "difficulty": difficulty,
      "points": points,
      "type": type,
      "title": title,
      "question": question,
      "responses": responses
    };
  }
  return res;
}

function create_quiz() {
  const data = get_fields_quiz(); 
  if (Object.keys(data).length !== 0) {
    data["request"] = "create_quiz";
    $.ajax({
      method: "POST",
      url: config["serverURL"] + "/meliemelo-challenge/requests.php",
      dataType: "json",
      data: data
    }).done(function(json) {
      if (config["debug"]) { console.log(json); }
      if (!json["create_quiz_status"]) {
        $("main p.error").show();
        $("main p.error").html("La création du quiz a échoué.");
      }
      else {
        main_menu_page();
      }
      session_is_alive(json);
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      document.location.href = "index.php";
    })
  }
}

function modify_quiz(quiz_id) {
  const overwrite = $("main #modify_responses input[type='checkbox']").is(":checked");
  const data = get_fields_quiz(overwrite, true);
  if (Object.keys(data).length !== 0) {
    data["request"] = "edit_quiz";
    data["quiz_id"] = quiz_id;
    data["overwrite"] = Number(overwrite);
    $.ajax({
      method: "POST",
      url: config["serverURL"] + "/meliemelo-challenge/requests.php",
      dataType: "json",
      data: data
    }).done(function(json) {
      if (config["debug"]) { console.log(json); }
      if (!json["edit_quiz_status"]) {
        $("main p.error").show();
        $("main p.error").html("La modification du quiz a échoué.");
      }
      else {
        main_menu_page();
      }
      session_is_alive(json);
    }).fail(function(e) {
      if (config["debug"]) { console.log(e); }
      document.location.href = "index.php";
    })
  }
}

function remove_quiz(tag, quiz_id) {
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "remove_quiz",
      "quiz_id": quiz_id
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    if (!json["remove_quiz_status"]) {
      $(tag).next().show();
      $(tag).next().html("La suppression du quiz a échoué.");
    }
    else {
      $("main #"+quiz_id).remove();
    }
    session_is_alive(json);
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    document.location.href = "index.php";
  })
}

function stock_quiz(tag, quiz_id) {
  $.ajax({
    method: "POST",
    url: config["serverURL"] + "/meliemelo-challenge/requests.php",
    dataType: "json",
    data: {
      "request": "stock_quiz",
      "quiz_id": quiz_id,
      "open_date": $(tag).parent().find("input.open").val(),
      "close_date": $(tag).parent().find("input.close").val()
    }
  }).done(function(json) {
    if (config["debug"]) { console.log(json); }
    if (!json["stock_quiz_status"]) {
      const error_tag = $(tag).parent().find("p.error");
      error_tag.show();
      error_tag.html("Le stockage du quiz a échoué.");
    }
    else {
      $("main #"+quiz_id).remove();
    }
    session_is_alive(json);
  }).fail(function(e) {
    if (config["debug"]) { console.log(e); }
    document.location.href = "index.php";
  });
}