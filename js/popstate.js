// Enregistrement d'une page sur la pile
// ATTENTION : Pas de prise en charge des fermetures
// fun_page : unit -> unit
function push_state(fun_page) {
  if (window.history.state !== null && window.history.state != fun_page) {
    // Sérialisation de la fonction de page
    history.pushState(fun_page.toString(), "");
  }
}

// Fontion de gestion du retour en arrière
window.onpopstate = function(event) {
  // Fond de pile atteint si null
  if (event.state !== null) {
    // Désérialisation de la fonction de page
    const fun_page = new Function("return " + event.state)();
    fun_page();
  }
};

// Enregistrement du fond de pile des pages (page d'accueil)
history.pushState((function() {window.location.href = "index.php";}).toString(), "");