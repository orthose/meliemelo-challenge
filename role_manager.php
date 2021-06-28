<?php

/*******************************************/
/* Fonctions du système de rôles           */
/*******************************************/

require("config.php");

const ROLES = array("player", "admin", "undefined");
const ACTIONS = array(
  "create_quiz", "answer_quiz", "remove_quiz", "archive_quiz",
  "read_stock_quiz", "read_history_quiz",
  "read_stats_usage", "read_rank_player",
  "define_admin", "connection", "disconnection", "create_account",
);

// Changer le rôle d'un utilisateur
// Cette fonction ne doit pas être associée à un appel AJAX
// Il faut les autorisations maximales pour l'utiliser
function change_role($login, $new_role) {
  $data = get_user_data();
  // Vérification que le rôle est valide
  if (in_array($new_role, ROLES)) {
    $data[$login]["role"] = $new_role;
    set_user_data($data);
  }
}

// Quel est le rôle du login ?
function get_role($login) {
  $data = get_user_data();
  if (isset($data[$login])) {
    return $data[$login]["role"];
  }
  else {
    return "undefined";
  }
}

// L'utilisateur à ce login est-il autorisé à réaliser cette action ?
// Le context est un tableau associatif donnant éventuellement des
// informations supplémentaires
// Retirer le contexte si inutile ?? Gérer au cas par cas
function allowed($login, $action, $context = NULL) {
  $role = get_role($login);
  
  // Pré-conditions vérification que les rôles et actions sont valides
  $res = 
  in_array($role, ROLES) 
  && in_array($action, ACTIONS);
  
  // Règles de permissions
  $res = $res && (
    // Action accessible à n'importe qui
    (($role === "player" || $role === "admin" || $role === "undefined")
    && $action === "connection")
    ||
    // Actions accessibles à des utilisateurs authentifiés
    (($role === "player" || $role === "admin")
    && (
      $action === "read_history_quiz"
      || $action === "read_rank_player"
      || $action === "answer_quiz"
      || $action === "disconnection"
      ))
    || 
    // Actions accessibles à l'administrateur
    ($role === "admin"
    && (
      $action === "create_quiz"
      || $action === "remove_quiz"
      || $action === "archive_quiz"
      || $action === "read_stock_quiz"
      || $action === "read_stats_usage"
      || $action === "define_admin"
      ))
    ||
    // Action accessible spécifiquement à un utilisateur non-authentifié
    ($role === "undefined" && $action === "create_account")
    );
    
    return $res;
  }

?>