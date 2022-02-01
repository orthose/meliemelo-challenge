function quiz_page(title, instructions, fun_push_state, fun_quiz_ajax) {
  if (user_login !== "" && user_role !== "undefined") {
    push_state(fun_push_state);
    const page = $(`
      <h2>`+title+`</h2>
      <div id="filter_date"></div>
      <p>`+instructions+`</p>
      <div id="list_quiz"></div>
      `);
    $("main").html(page);
    fun_quiz_ajax();
  }
}

function quiz_current_page() {
  quiz_page(
    "Quiz en jeu", 
    "Choisissez un quiz parmi ceux disponibles.",
    function() {quiz_current_page();}, quiz_current
  );
}

function quiz_current_not_playable_page() {
  quiz_page(
    "Quiz en jeu créés par " + user_login,
    "Choisissez un quiz parmi ceux disponibles.",
    function() {quiz_current_not_playable_page();}, quiz_current_not_playable
  );
}

function quiz_archive_page() {
  quiz_page(
    "Quiz archivés",
    "Choisissez un quiz parmi ceux clôturés.",
    function() {quiz_archive_page();}, quiz_archive
  );
}

function quiz_stock_page() {
  quiz_page(
    "Quiz en stock",
    "Choisissez un quiz parmi ceux en stock.",
    function() {quiz_stock_page();}, quiz_stock
  );
}

function quiz_answered_page() {
  quiz_page(
    "Vos réponses aux quiz",
    "Choisissez un quiz parmi ceux auxquels vous avez répondu.",
    function() {quiz_answered_page();}, quiz_answered
  );
}

function edit_quiz_page() {
  quiz_page(
    "Quiz modifiables",
    "Choisissez un quiz parmi ceux que vous avez créés.",
    function() {edit_quiz_page();}, quiz_editable_modify
  );
}

function remove_quiz_page() {
  quiz_page(
    "Quiz supprimables",
    "Choisissez un quiz parmi ceux que vous avez créés.",
    function() {remove_quiz_page();}, quiz_editable_remove
  );
}

function stockable_quiz_page() {
  quiz_page(
    "Quiz ouverts",
    "Choisissez un quiz ouvert parmi ceux que vous avez créés.",
    function() {stockable_quiz_page();}, quiz_stockable
  );
}

function quiz_answered_others_page() {
  quiz_page(
    "Réponses des autres joueurs",
    "Choisissez un quiz parmi ceux archivés.",
    function() {quiz_answered_others_page();}, 
    function() {
      $("main h2").after($(`
        <p class="form">Joueur</p>
        <input type="text" id="player"><br>
        <button id="confirm_player">Confirmer</button>
        <p class="error" hidden></p>
        `)
      );
      $("main #confirm_player").on("click", function() {
        $("main div#list_quiz").html("");
        if ($("main #player").val() === "") {
          $("main .error").html("Veuillez entrer un pseudo de joueur.");
          $("main .error").show();
        }
        else {
          $("main .error").hide();
          quiz_answered_others();
        }
      });
    }
  );
}

function answer_quiz_page(quiz_id, type, title, question, responses) {
  if (user_login !== "" && user_role !== "undefined") {
    // On affiche la page de choix de quiz jouable
    push_state(function() {window.history.back();});
    const page = $(`
      <h2>` + title + `</h2>
      <div class="question">` + convert_md(question) + `</div>
      <form></form><br>
      <button>Envoyer la réponse</button>
      <p class="error" hidden></p>
      `);
    $("main").html(page);
    $("main button").on("click", function() { answer_quiz(type, quiz_id); });
    if (type === "checkbox_and" || type === "checkbox_or") {
      responses.forEach(function(response) {
        $("main form").append($(`
          <div><input type="checkbox" name="quiz" value="` + response + `"><label>` + convert_md(response) + `</label></div>
          `));
      });
    }
    else if (type === "radio") {
      responses.forEach(function(response) {
        $("main form").append($(`
          <div><input type="radio" name="quiz" value="` + response + `"><label>` + convert_md(response) + `</label></div>
          `));
      });
    }
    else if (type.startsWith("text")) {
      $("main form").append($(`<input type="text" name="quiz">`));
    }
  }
  // Coloration syntaxique éventuelle des balises code
  hljs.highlightAll();
}

function show_quiz_page(state, quiz_id, type, title, question, responses) {
  function button_quiz_page(state) {
    if (state === "archive") {
      return `<button onclick="quiz_archive_page()">Quiz archivés</button>`;
    }
    else if (state === "stock") {
      return `<button onclick="quiz_stock_page()">Quiz en stock</button>`;
    }
    else if (state === "current_not_playable") {
      return `<button onclick="quiz_current_not_playable_page()">Quiz jouables par les autres</button>`;
    }
    else if (state === "answered") {
      return `<button onclick="quiz_answered_page()">Voir mes réponses</button>`;
    }
    else if (state === "answered_others") {
      return `<button>Voir les réponses des autres</button>`;
    }
  }
  
  if (user_login !== "" && user_role !== "undefined") {
    // On ne peut pas savoir si on a regardé les quiz stockés ou archivés
    push_state(function() {window.history.back();});
    const player = $("#player").val(); // Pour answered_others
    const page = $(`
      <h2>` + title + `</h2>
      <div class="question">` + convert_md(question) + `</div>
      <form></form><br>
      ` + button_quiz_page(state) + `
      `);
    $("main").html(page);
    
    // Remplissage automatique du pseudo du joueur
    if (state === "answered_others") {
      $("main button").on("click", function() {
        quiz_answered_others_page();
        $("#player").val(player);
      });
    }
    
    if (state === "answered" || state === "answered_others") {
      $("main form").addClass("answered");
    }
    
    function class_response(valid) {
      if (valid == 0) {
        return "invalid"; 
      }
      else if (valid == 1) {
        return "valid";
      }
    }
    
    if (type === "checkbox_and" || type === "checkbox_or") {
      responses.forEach(function([response, valid]) {
        $("main form").append($(`
          <div><input type="checkbox" name="quiz" value="` + response + `"><label class="` + class_response(valid) + `">` + convert_md(response) + `</label></div>
          `));
      });
    }
    else if (type === "radio") {
      responses.forEach(function([response, valid]) {
        $("main form").append($(`
          <div><input type="radio" name="quiz" value="` + response + `"><label class="` + class_response(valid) + `">` + convert_md(response) + `</label></div>
          `));
      });
    }
    else if (type.startsWith("text")) {
      $("main form").append($(`<input type="text" name="quiz" value="` + responses[0][0] + `" disabled>`));
    }
    
    if (!type.startsWith("text") && (state === "answered" || state === "answered_others")) {
      $(".valid").prev().attr("checked", "");
    }
  }
  // Coloration syntaxique éventuelle des balises code
  hljs.highlightAll();
}

function show_range_value(tag) {
  let text = $(tag).prev().text();
  $(tag).prev().html(text.replace(new RegExp("[0-9]+"), $(tag).val()));
}

function add_choice(tag) {
  if ($(tag).prev().prev().val() !== "") {
    $(tag).before($(`
      <input class="response" type="text" name="quiz">
      <select name="quiz">
        <option value="0">Faux</option>
        <option value="1">Vrai</option>
      </select>
      `));
  }
}

function create_quiz_page() {
  if (user_login !== "" && user_role !== "undefined") {
    push_state(function() {create_quiz_page();});
    const page = $(`
      <h2>Création de quiz</h2>
      <p>Veuillez compléter tous les champs ci-dessous.</p>
      <div class="create_quiz">
      <p class="form">Titre</p>
      <input id="title" type="text" name="quiz"><br>
      <p class="form">Question</p>
      <textarea id="question" name="quiz"></textarea><br><br>
      <p>La date d'ouverture doit être antérieure à la date de fermeture.</p>
      <p class="form">Date d'ouverture</p>
      <input id="open" type="date" name="quiz"><br>
      <p class="form">Date de fermeture</p>
      <input id="close" type="date" name="quiz"><br>
      <p>Le nombre de points total correspond à la difficulté multipliée par le nombre de points.</p>
      <p class="form">Difficulté (1)</p>
      <input id="difficulty" onchange="show_range_value(this)" type="range" name="quiz" min="1" max="10" value="1"><br>
      <p class="form">Points (1)</p>
      <input id="points" onchange="show_range_value(this)" type="range" name="quiz" min="0" max="10" value="1"><br>
      <p class="form">Type</p>
      <select id="type" name="quiz">
        <option value="checkbox_and">Choix multiples [conjonction]</option>
        <option value="checkbox_or">Choix multiples [disjonction]</option>
        <option value="radio">Choix unique</option>
        <option value="text_strong">Texte [vérification exacte]</option>
        <option value="text_weak">Texte [vérification tolérante]</option>
        <option value="text_regex">Texte [expression régulière]</option>
      </select><br>
      <p>
        Un quiz valide doit obligatoirement avoir au moins une réponse. 
        Le type de quiz choisi conditionne le nombre de réponses à ajouter.
      </p>
      <p class="form">Réponses</p>
      <input class="response" type="text" name="quiz">
      <select name="quiz">
        <option value="0">Faux</option>
        <option value="1">Vrai</option>
      </select>
      <button onclick="add_choice(this)">Ajouter une réponse</button><br>
      <button onclick="create_quiz()">Créer le quiz</button>
      </div>
      <p class="error" hidden></p>
      `);
    // Remplissage automatique des dates
    auto_init_date(page, "#open", "#close");
    page.find("#open").change(function() {
      auto_fill_close_date("#open", "#close");
    })
    $("main").html(page);
  }
}

function modify_quiz_page(quiz_id, open, close, difficulty, points, type, title, question, responses) {
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>Modification de quiz</h2>
      <p>Veuillez compléter tous les champs ci-dessous.</p>
      <div class="create_quiz">
      <p class="form">Titre</p>
      <input id="title" type="text" name="quiz" value="`+title+`"><br>
      <p class="form">Question</p>
      <textarea id="question" name="quiz">`+question+`</textarea><br><br>
      <p>La date d'ouverture doit être antérieure à la date de fermeture.</p>
      <p class="form">Date d'ouverture</p>
      <input id="open" type="date" name="quiz" value="`+open+`"><br>
      <p class="form">Date de fermeture</p>
      <input id="close" type="date" name="quiz" value="`+close+`"><br>
      <p>Le nombre de points total correspond à la difficulté multipliée par le nombre de points.</p>
      <p class="form">Difficulté (`+difficulty+`)</p>
      <input id="difficulty" onchange="show_range_value(this)" type="range" name="quiz" min="1" max="10" value="`+difficulty+`"><br>
      <p class="form">Points (`+points+`)</p>
      <input id="points" onchange="show_range_value(this)" type="range" name="quiz" min="0" max="10" value="`+points+`"><br>
      <p class="form">Type</p>
      <select id="type" name="quiz">
        <option value="checkbox_and">Choix multiples [conjonction]</option>
        <option value="checkbox_or">Choix multiples [disjonction]</option>
        <option value="radio">Choix unique</option>
        <option value="text_strong">Texte [vérification exacte]</option>
        <option value="text_weak">Texte [vérification tolérante]</option>
        <option value="text_regex">Texte [expression régulière]</option>
      </select><br>
      <p>
        Un quiz valide doit obligatoirement avoir au moins une réponse. 
        Le type de quiz choisi conditionne le nombre de réponses à ajouter.
      </p>
      <p class="form">Réponses</p>
      <p class="warning">
        Si vous choisissez de modifier les réponses déjà enregistrées, les réponses des joueurs
        pour ce quiz seront supprimées définitivement. Ce n'est pas un problème si le quiz
        n'a jamais été mis en jeu. 
      </p>
      <div id="modify_responses"><input type="checkbox"><label>Modifier les réponses</label></div>
      <div id="original_responses"></div>
      <input class="response" type="text" name="quiz">
      <select name="quiz">
        <option value="0">Faux</option>
        <option value="1">Vrai</option>
      </select>
      <button onclick="add_choice(this)">Ajouter une réponse</button><br>
      <button onclick="modify_quiz(`+quiz_id+`)">Modifier le quiz</button>
      </div>
      <p class="error" hidden></p>
      `);
      
    // Remplissage automatique des dates
    page.find("#open").change(function() {
      auto_fill_close_date("#open", "#close");
    })
    $("main").html(page);
    
    // Sélection du type de quiz
    $("main select#type").val(type);
    
    // Ajout des réponses déjà entrées
    responses.forEach(function(response) {
      $("main #original_responses").append(
        $(`<input class="response" type="text" name="quiz">`).val(response[0]).prop("disabled", true)
        .add($(`
        <select name="quiz" disabled>
          <option value="0">Faux</option>
          <option value="1">Vrai</option>
        </select>`).val(response[1]).prop("disabled", true)
      ));
    });
    
    // Modification des réponses
    $("main #modify_responses input[type='checkbox']").on("change", function(event) {
      if ($("main #modify_responses input[type='checkbox']").is(":checked")) {
        $("main #original_responses input").prop("disabled", false);
        $("main #original_responses select").prop("disabled", false);
      } else {
        $("main #original_responses input").prop("disabled", true);
        $("main #original_responses select").prop("disabled", true);
      }
    });
  }
}