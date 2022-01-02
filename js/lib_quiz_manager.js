// Conversion en string d'un objet Date
function date_to_string(date) {
  const day = ("0" + date.getDate().toString()).slice(-2);
  const month = ("0" + (date.getMonth() + 1).toString()).slice(-2);
  const year = date.getFullYear().toString();
  return year + "-" + month + "-" + day;
}

// Remplissage automatique de la date de fermeture
function auto_fill_close_date(tag_open_date, tag_close_date) {
  const openDate = new Date($(tag_open_date).val());
  const nextWeek = new Date(openDate.setDate(openDate.getDate() + 7));
  $(tag_close_date).val(date_to_string(nextWeek));
}

// Initialisation automatique des dates
function auto_init_date(page, tag_open_date, tag_close_date) {
  const currentDate = new Date();
  page.find(tag_open_date).val(date_to_string(currentDate));
  const nextWeek = new Date(currentDate.setDate(currentDate.getDate() + 7));
  page.find(tag_close_date).val(date_to_string(nextWeek));
}

// Fonctions pour interface utilisateur interactive 
function show_quiz(tag) {
  $(tag).next().show();
  $(tag).parent().attr("class", "select_quiz_unfolded");
  $(tag).attr("onclick", "hide_quiz(this)");
}

function hide_quiz(tag) {
  $(tag).next().hide();
  $(tag).parent().attr("class", "select_quiz_folded");
  $(tag).attr("onclick", "show_quiz(this)");
}