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
    $("main button").on("click", function() { answer_quiz(quiz_id); });
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
          <label for="quiz"><input type="radio" name="quiz" value="` + response + `">` + response + `</label>
          `));
      });
    }
    else if (type === "text") {
      $("main form").append($(`<input type="text" name="quiz">`));
    }
  }
}

function show_quiz_page(quiz_id, type, title, question, responses) {
  if (user_login !== "" && user_role !== "undefined") {
    const page = $(`
      <h2>` + title + `</h2>
      <p>` + question + `</p>
      <form></form><br>
      <button onclick="quiz_archive_page()">Quiz archivés</button>
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