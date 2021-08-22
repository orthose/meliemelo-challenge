// Enregistrement d'une page sur la pile
function push_state(num_page) {
  let book = [
    "Accueil",
    "Création compte",
    "Connexion",
    "Menu principal",
    "Modification mot de passe",
    "Suppression compte",
    "Modification rôle",
    "Classement",
    "Quiz jouables",
    "Quiz archivés", 
    "Quiz stockés",
    "Répondre quiz",
    "Voir quiz",
    "Créer quiz",
    "Supprimer quiz",
    "Quiz jouables par les autres",
    "Voir mes réponses",
    "Modification message du jour",
    "Reporter bogue"
  ];
  if (window.history.state !== null && window.history.state.page != num_page) {
    history.pushState({"page": num_page}, book[num_page]);
  }
}

// Fontion de gestion du retour en arrière
// ATTENTION: La page du dessus de la pile est supprimée
// et on regarde celle du dessous
window.onpopstate = function(event) {
  // Fond de pile atteint si null
  if (event.state !== null) {
    let state = event.state;
    if (state.page === 0) {
      window.location.href = "index.php";
    }
    else if (state.page === 1) {
      register_page();
    }
    else if (state.page === 2) {
      window.location.href = "index.php";
    }
    else if (state.page === 3) {
      main_menu_page();
    }
    else if (state.page === 4) {
      set_password_page();
    }
    else if (state.page === 5) {
      unregister_page();
    }
    else if (state.page === 6) {
      set_role_page();
    }
    else if (state.page === 7) {
      high_score_page();
    }
    else if (state.page === 8) {
      quiz_current_page();
    }
    else if (state.page === 9) {
      quiz_archive_page();
    }
    else if (state.page === 10) {
      quiz_stock_page();
    }
    else if (state.page === 11) {
      // On affiche la page de choix de quiz jouable
      // tout en consommant un autre état de la pile
      window.history.back();
    }
    else if (state.page === 12) {
      // On ne peut pas savoir si on a regardé
      // les quiz stockés ou archivés
      window.history.back();
    }
    else if (state.page === 13) {
      create_quiz_page();
    }
    else if (state.page === 14) {
      remove_quiz_page();
    }
    else if (state.page === 15) {
      quiz_current_not_playable_page();
    }
    else if (state.page === 16) {
      quiz_answered_page();
    }
    else if (state.page === 17) {
      set_daily_msg_page();
    }
    else if (state.page === 18) {
      bug_report_page();
    }
  }
};

// Enregistrement du fond de pile des pages (page d'accueil)
history.pushState({"page": 0}, "Accueil");