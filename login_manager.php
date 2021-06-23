<?php

/*******************************************/
/* Fonctions du système d'authentification */
/*******************************************/

require("role_manager.php");

// Le couple (login, passwd) est-il valide ?
function connection($login, $passwd) {
  // Simples quotes 'passwd' pour protéger injection bash
  $cmd = "htpasswd -vb ./secret/passwd.txt $login '$passwd'";
  system($cmd, $res);
  // Code d'erreur = 0 si bon mot de passe
  // Code d'erreur = 3 si mauvais mot de passe
  $res = ($res === 0);
  // Démarrage de la session
  if ($res) {
    $_SESSION["login"] = $login;
    $_SESSION["role"] = get_role($login);
  }
  return json_encode(array("connection_status" => $res));
}

// Déconnexion et fermeture de session
function disconnection() {
  if (isset($_SESSION["login"])) { unset($_SESSION["login"]); }
  if (isset($_SESSION["role"])) { unset($_SESSION["role"]); }
  session_destroy();
}

// L'utilisateur avec ce login existe-t-il déjà ?
function exists($login) {
  $cmd = "cut -f1 -d: ./secret/passwd.txt";
	$all_users = shell_exec($cmd);
	$all_users = preg_split("/\n/", $all_users);
	return in_array($login, $all_users);
}

// Enregistrement d'un nouvel utilisateur
// Différents code d'erreur
function register($login) {
  // Taille minimale du password = 8
  $pattern_passwd = "/[\w\s]{8}[\w\s]*/";
  $res = array();
  // Le mot de passe correspond-il au pattern ?
  if (!preg_match($pattern_passwd, $passwd)) {
	   $res["register_code"] = -1;
  }
  // Le format de l'email est-il valide ?
  else if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
	  $res["register_code"] = -2;
  }
  // Le login est-il déjà pris ?
  else if (exists($login)) {
	  $res["register_code"] = -3;
  }
  // Enregistrement de l'utilisateur
  else {
	  // Simples quotes 'passwd' pour protéger injection bash
	  $cmd = "htpasswd -b ../secret/passwd.txt $login '$passwd'";
    // Code d'erreur = 0 si succès de mise à jour
	  // Code d'erreur in [1-7] si problème rencontré
	  $test = system($cmd, $res["register_code"]);
  }
  return json_encode($res);
}

?>