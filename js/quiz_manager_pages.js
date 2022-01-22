/*function quiz_page(title, instructions) {
  
} */

function quiz_current_page() {
  if (user_login !== "" && user_role !== "undefined") {
    push_state(function() {quiz_current_page();});
    const page = $(`
      <h2>Quiz en jeu</h2>
      <p>Choisissez un quiz parmi ceux disponibles.</p>
      <div id="list_quiz"></div>
      `);
    $("main").html(page);
    quiz_current();
  }
}

function quiz_current_not_playable_page() {
  if (user_login !== "" && user_role !== "undefined") {
    push_state(function() {quiz_current_not_playable_page();});
    const page = $(`
      <h2>Quiz en jeu créés par ` + user_login + `</h2>
      <p>Choisissez un quiz parmi ceux disponibles.</p>
      <div id="list_quiz"></div>
      `);
    $("main").html(page);
    quiz_current_not_playable();
  }
}

function quiz_archive_page() {
  if (user_login !== "" && user_role !== "undefined") {
    push_state(function() {quiz_archive_page();});
    const page = $(`
      <h2>Quiz archivés</h2>
      <p>Choisissez un quiz parmi ceux clôturés.</p>
      <div id="list_quiz"></div>
      `);
    $("main").html(page);
    quiz_archive();
  }
}

function quiz_stock_page() {
  if (user_login !== "" && user_role !== "undefined") {
    push_state(function() {quiz_stock_page();});
    const page = $(`
      <h2>Quiz en stock</h2>
      <p>Choisissez un quiz parmi ceux en stock.</p>
      <div id="list_quiz"></div>
      `);
    $("main").html(page);
    quiz_stock();
  }
}

function quiz_answered_page() {
  if (user_login !== "" && user_role !== "undefined") {
    push_state(function() {quiz_answered_page();});
    const page = $(`
      <h2>Vos réponses aux quiz</h2>
      <p>Choisissez un quiz parmi ceux auxquels vous avez répondu.</p>
      <div id="list_quiz"></div>
      `);
    $("main").html(page);
    quiz_answered();
  }
}

function remove_quiz_page() {
  if (user_login !== "" && user_role !== "undefined") {
    push_state(function() {remove_quiz_page();});
    const page = $(`
      <h2>Quiz supprimables</h2>
      <p>Choisissez un quiz parmi ceux que vous avez créés.</p>
      <div id="list_quiz"></div>
      `);
    $("main").html(page);
    quiz_editable_remove();
  }
}

function stockable_quiz_page() {
  if (user_login !== "" && user_role !== "undefined") {
    push_state(function() {stockable_quiz_page();});
    const page = $(`
      <h2>Quiz ouverts</h2>
      <p>Choisissez un quiz ouvert parmi ceux que vous avez créés.</p>
      <div id="list_quiz"></div>
      `);
    $("main").html(page);
    quiz_stockable();
  }
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
    else if (type === "text") {
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
  }
  
  if (user_login !== "" && user_role !== "undefined") {
    // On ne peut pas savoir si on a regardé les quiz stockés ou archivés
    push_state(function() {window.history.back();});
    const page = $(`
      <h2>` + title + `</h2>
      <div class="question">` + convert_md(question) + `</div>
      <form></form><br>
      ` + button_quiz_page(state) + `
      `);
    $("main").html(page);
    
    if (state === "answered") {
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
    else if (type === "text") {
      $("main form").append($(`<input type="text" name="quiz" value="` + responses[0][0] + `" disabled>`));
    }
    
    if (state === "answered") {
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
    $(tag).prev().prev().css("border", "medium solid mediumblue");
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
        <option value="text">Texte</option>
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