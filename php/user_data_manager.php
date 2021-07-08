<?php

/*******************************************/
/* Fonctions du système de gestion des     */
/* données utilisateur                     */
/*******************************************/

require("config.php");

// Renvoie tout le fichier des données utilisateur
function get_user_data() {
  return json_decode(file_get_contents($config["user_data_file"]), true);
}

// Met à jour tout le fichier des données utilisateur
function set_user_data($data) {
  file_put_contents($config["user_data_file"], json_encode($data, JSON_PRETTY_PRINT));
}

// Initialisation des données utilisateur
// Renvoie false si l'utilisateur existe déjà
function init_user_data($login) {
  $data = get_user_data();
  $res = !array_key_exists($login, $data);
  if ($res) {
    $new_user = array(
      "role" => "player",
      "answered" => [],
      "created" => [],
      "points" = 0,
      "success" = 0,
      "fail" = 0,
    );
    $data[$login] = $new_user;
    set_user_data($data);
  }
  return $res;
}

// Ajout d'une réponse de quiz
// Si points > 0 alors on considère succès
// Si points = 0 alors on considère échec
function add_answer($login, $quiz_id, $points) {
  // Vérification que le joueur n'a pas créé le quiz
}

// Ajout d'un identifiant de quiz créé
function add_creation($login, $quiz_id) {
  
}

?>