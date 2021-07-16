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
    $_SESSION["role"] = $res["user_role"]."_user";
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

/**
 * L'utilisateur avec ce login existe-t-il déjà ?
 * @return: JSON avec le champ already_used = true si le login est déjà pris
 * et le login associé
 **/
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
 * @return: JSON avec champ registration_status = true
 * si l'enregistrement est un succès et le login associé
 **/
function register($login, $passwd) {
  $sql = "CALL register_new_user(:login, :passwd)";
  $params = array(":login" => $login, ":passwd" => $passwd);
  $res = array("login" => $login, "registration_status" => true);
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["registration_status"] = false;
  };
  request_database("undefined_user", $sql, $params, $res, NULL, $error_fun);
  return json_encode($res);
}

/**
 * Suppression d'un utilisateur inscrit
 * @return: JSON avec champ unregistration_status = true
 * si la suppression réussie et le login associé
 **/
function unregister($login, $passwd) {
  $sql = "CALL unregister_user(:login, :passwd)";
  $params = array(":login" => $login, ":passwd" => $passwd);
  $res = array("login" => $login, "unregistration_status" => true);
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["unregistration_status"] = false;
  };
  request_database($_SESSION["role"], $sql, $params, $res, NULL, $error_fun);
  return json_encode($res);
}

/**
 * Modification du mot de passe, nécessite le mot de passe actuel
 * @return: JSON avec champ setting_password_status = true si la mise 
 * à jour est un succès et le login associé
 **/
function set_password($login, $actual_passwd, $new_passwd) {
  $sql = "CALL set_password(:login, :actual_passwd, :new_passwd)";
  $params = array(
    ":login" => $login, 
    ":actual_passwd" => $actual_passwd,
    ":new_passwd" => $new_passwd);
  $res = array("login" => $login, "setting_password_status" => true);
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["setting_password_status"] = false;
  };
  request_database($_SESSION["role"], $sql, $params, $res, NULL, $error_fun);
  return json_encode($res);
}

/**
 * Modification du rôle d'un utilisateur
 * @param new_role: "admin", "player"
 * @return: JSON avec champ setting_role_status = true si la mise 
 * à jour est un succès et le login associé
 **/
function set_role($login, $new_role) {
  $sql = "CALL set_role(:login, :new_role)";
  $params = array(":login" => $login, ":new_role" => $new_role);
  $res = array("login" => $login, "setting_role_status" => true);
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["setting_role_status"] = false;
  };
  request_database($_SESSION["role"], $sql, $params, $res, NULL, $error_fun);
  return json_encode($res);
}

?>