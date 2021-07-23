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