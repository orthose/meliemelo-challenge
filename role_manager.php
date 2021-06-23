<?php

/*******************************************/
/* Fonctions du système de rôles           */
/*******************************************/

const ROLES = array("player", "admin", "undefined");
const ACTIONS = array(
  "create_quiz", "answer_quiz", "remove_quiz", "archive_quiz",
  "read_stock_quiz", "read_history_quiz",
  "read_stats_usage", "read_rank_player",
  "define_admin",
);

// Tableau des (login => role)
function get_roles() {
  return json_decode(file_get_contents("./secret/roles.json"), true);
}

// Changer le rôle d'un utilisateur
// Cette fonction ne doit pas être associée à un appel AJAX
// Il faut les autorisations maximales pour l'utiliser
function change_role($login, $new_role) {
  $roles = get_roles();
  // Vérification que le rôle est valide
  if (in_array($new_role, ROLES)) {
    $roles[$login] = $new_role;
    file_put_contents("./secret/roles.json", json_encode($roles, JSON_PRETTY_PRINT));
  }
}

// Quel est le rôle du login ?
function get_role($login) {
  $roles = get_roles();
  if (isset($roles[$login])) {
    return $roles[$login];
  }
  else {
    return "undefined";
  }
}

// L'utilisateur à ce login est-il autorisé à réaliser cette action ?
// Le context est un tableau associatif donnant éventuellement des
// informations supplémentaires
function allowed($login, $action, $context = NULL) {
  $role = get_role($login);
  
  // Pré-conditions
  $res = 
  in_array($role, ROLES) 
  && in_array($action, ACTIONS)
  && $role !== "undefined";
  
  // Règles de permissions
  $res = $res &&
  (($role === "player" || $role === "admin")
  && (
    $action === "read_history_quiz"
    || $action === "read_rank_player"
    || $action === "answer_quiz"
    )
    || 
    ($role === "admin"
    && (
      $action === "create_quiz"
      || $action === "remove_quiz"
      || $action === "archive_quiz"
      || $action === "read_stock_quiz"
      || $action === "read_stats_usage"
      || $action === "define_admin"
      )
    ));
    
    return $res;
  }

?>