function quiz_current_page() {
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>Quiz en jeu</h2>
      <p>Choisissez un quiz parmi ceux disponibles.</p>
      `);
    $("main").html(page);
    quiz_current();
  }
}

function quiz_archive_page() {
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>Quiz archivés</h2>
      <p>Choisissez un quiz parmi ceux clôturés.</p>
      `);
    $("main").html(page);
    quiz_archive();
  }
}

function quiz_stock_page() {
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>Quiz en stock</h2>
      <p>Choisissez un quiz parmi ceux en stock.</p>
      `);
    $("main").html(page);
    quiz_stock();
  }
}

function answer_quiz_page(quiz_id, type, title, question, responses) {
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>` + title + `</h2>
      <p>` + question + `</p>
      <form></form><br>
      <button>Envoyer la réponse</button>
      <p class="error" hidden></p>
      `);
    $("main").html(page);
    $("main button").on("click", function() { answer_quiz(type, quiz_id); });
    if (type === "checkbox") {
      responses.forEach(function(response) {
        $("main form").append($(`
          <div><input type="checkbox" name="quiz" value="` + response + `"><label>` + response + `</label></div>
          `));
      });
    }
    else if (type === "radio") {
      responses.forEach(function(response) {
        $("main form").append($(`
          <div><input type="radio" name="quiz" value="` + response + `"><label>` + response + `</label></div>
          `));
      });
    }
    else if (type === "text") {
      $("main form").append($(`<input type="text" name="quiz">`));
    }
  }
}

function show_quiz_page(state, quiz_id, type, title, question, responses) {
  
  function button_quiz_page(state) {
    if (state === "archive") {
      return `<button onclick="quiz_archive_page()">Quiz archivés</button>`;
    }
    else if (state === "stock") {
      return `<button onclick="quiz_stock_page()">Quiz en stock</button>`;
    }
  }
  
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>` + title + `</h2>
      <p>` + question + `</p>
      <form></form><br>
      ` + button_quiz_page(state) + `
      `);
    $("main").html(page);
    
    function class_response(valid) {
      if (valid == 0) {
        return "invalid"; 
      }
      else if (valid == 1) {
        return "valid";
      }
    }
    
    if (type === "checkbox") {
      responses.forEach(function([response, valid]) {
        $("main form").append($(`
          <div><input type="checkbox" name="quiz" value="` + response + `"><label class="` + class_response(valid) + `">` + response + `</label></div>
          `));
      });
    }
    else if (type === "radio") {
      responses.forEach(function([response, valid]) {
        $("main form").append($(`
          <div><input type="radio" name="quiz" value="` + response + `"><label class="` + class_response(valid) + `">` + response + `</label></div>
          `));
      });
    }
    else if (type === "text") {
      $("main form").append($(`<input type="text" name="quiz" value="` + responses[0][0] + `" disabled>`));
    }
  }
}

function show_range_value(tag) {
  let text = $(tag).prev().text();
  $(tag).prev().html(text.replace(new RegExp("[0-9]+"), $(tag).val()));
}

function add_choice(tag) {
  if ($(tag).prev().prev().val() !== "") {
    $(tag).prev().prev().attr("disabled", "");
    $(tag).prev().attr("disabled", "");
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
    const page = $(`
      <h2>Création de quiz</h2>
      <p>Veuillez compléter tous les champs ci-dessous.</p>
      <div class="create_quiz">
      <p>Titre</p>
      <input id="title" type="text" name="quiz"><br>
      <p>Question</p>
      <textarea id="question" name="quiz"></textarea><br><br>
      <p>Date d'ouverture</p>
      <input id="open" type="date" name="quiz"><br>
      <p>Date de fermeture</p>
      <input id="close" type="date" name="quiz"><br>
      <p>Difficulté (1)</p>
      <input id="difficulty" onchange="show_range_value(this)" type="range" name="quiz" min="1" max="10" value="1"><br>
      <p>Points (1)</p>
      <input id="points" onchange="show_range_value(this)" type="range" name="quiz" min="0" max="10" value="1"><br>
      <p>Type</p>
      <select id="type" name="quiz">
        <option value="checkbox">Choix multiples</option>
        <option value="radio">Choix unique</option>
        <option value="text">Texte</option>
      </select><br>
      <p>Réponses</p>
      <input class="response" type="text" name="quiz">
      <select name="quiz">
        <option value="0">Faux</option>
        <option value="1">Vrai</option>
      </select>
      <button onclick="add_choice(this)">Ajouter une réponse</button><br>
      <button onclick="create_quiz()">Créer le quiz</button>
      </div>
      <p class="error" hidden></p>
      <ul> 
        <li>La date d'ouverture doit être antérieure à la date de fermeture.</li>
        <li>Un quiz valide doit obligatoirement avoir au moins une réponse vraie.</li>
        <li>Le nombre de points total correspond à la difficulté multipliée par le nombre de points.</li>
      </ul>
      `);
    $("main").html(page);
  }
}

function remove_quiz_page() {
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>Quiz en stock</h2>
      <p>Choisissez un quiz parmi ceux en stock.</p>
      `);
    $("main").html(page);
    quiz_stock_remove();
  }
}