<?php

/*******************************************/
/* Fonctions du système d'authentification */
/*******************************************/

require("connect_mariadb.php");

/**
 * Le couple (login, passwd) est-il valide ?
 * @return: JSON avec le champ connection_status = true
 * si l'authentification est valide, et les champs user_role et login
 **/
function connection($login, $passwd) {
  
  // Préparation de la requête d'authentification
  $sql = "SELECT authentication_is_valid(:login, :passwd), get_role(:login)";
  $params = array(":login" => $login, ":passwd" => $passwd);
  $res = array();
  // Ne renvoie qu'un seul résultat car login unique
  $fill_res = function($row, &$res) {
    $res["connection_status"] = $row[0] === "1";
    if ($res["connection_status"]) { $res["user_role"] = $row[1]; }
  };
  
  // Appel du schéma de requête de base de données
  request_database("undefined_user", $sql, $params, $res, $fill_res);
  
  // Démarrage de la session
  if ($res["connection_status"]) {
    $_SESSION["login"] = $login;
    $_SESSION["role"] = $res["user_role"];
    $res["login"] = $login;
  }
  return json_encode($res);
}

// Déconnexion et fermeture de session
function disconnection() {
  $res = false;
  if (isset($_SESSION["login"])) { unset($_SESSION["login"]); $res = true; }
  if (isset($_SESSION["role"])) { unset($_SESSION["role"]); $res = true; }
  if ($res) { session_destroy(); }
  return json_encode(array("disconnection_status" => $res));
}

// L'utilisateur avec ce login existe-t-il déjà ?
function exists($login) {
  $sql = "SELECT login_exists(:login)";
  $params = array(":login" => $login);
  $res = array("login" => $login);
  // Ne renvoie qu'un seul résultat car login unique
  $fill_res = function($row, &$res) {
    $res["already_used"] = $row[0] === "1";
  };
  request_database("undefined_user", $sql, $params, $res, $fill_res);
  return json_encode($res);
}

/**
 * Enregistrement d'un nouvel utilisateur
 * La base de données refusera les login déjà pris
 * où les mots de passe de moins de 8 caractères
 * @return: JSON avec champ successful_registration = true
 * si l'enregistrement est un succès et le login associé
 **/
function register($login, $passwd) {
  $sql = "CALL register_new_user(:login, :passwd)";
  $params = array(":login" => $login, ":passwd" => $passwd);
  $res = array("login" => $login, "successful_registration" => true);
  $error_fun = function($request, &$res) {
    $res["successful_registration"] = false;
  };
  request_database("undefined_user", $sql, $params, $res, NULL, $error_fun);
  return json_encode($res);
}

?>