<?php

require("config.php");

// Renvoie le login de l'utilisateur actuel
function get_login() {
  if (!isset($_SESSION["login"])) {
    return "";
  }
  else {
    return $_SESSION["login"];
  }
}

// Renvoie le rôle de l'utilisateur actuel
function get_role() {
  if (!isset($_SESSION["role"])) {
    return "undefined";
  }
  else {
    return $_SESSION["role"];
  }
}

// Fonction par défaut en cas d'échec de requête
function error_fun_default($request, &$res) {
  global $config;
  // Mise à jour du résultat en cas d'échec de la requête
  if ($config["debug"]) {
    $res["request_database_error"] = $request->errorInfo();
  }
};

// Fonction pour éviter d'avoir à regarder $config depuis un autre fichier
function error_debug($key, $value, &$res) {
  global $config;
  if ($config["debug"]) {
    $res[$key] = $value;
  }
}

/**
 * Connexion à la base de données
 * Utile pour effectuer des requêtes complexes manuellement
 * @param user: Clé utilisateur à choisir parmi (undefined, player, admin)
 * @param res: Tableau des résultats passé par référence 
 * Peut être complété par des messages d'erreur si debug = true
 * @return: Objet PDO pour nouvelle session à la base
 * NULL si échec de connexion à la base
 **/
function connect_database($user, &$res) {
  global $config;
  try {
    $pdo = new PDO(
      'mysql:host='.$config["host"].';dbname='.$config["dbname"], 
      $config["login_".$user], $config["passwd_".$user]
    );
  }
  // En cas d'échec de connexion à la base
  catch (PDOException $error) {
    $pdo = NULL;
    if ($config["debug"]) {
      $res = array("connection_database_error" => $error->getMessage());
    }
  }
  finally {
    return $pdo;
  }
}

/**
 * Modèle pour requête unitaire à la base de données
 * @param user: Clé utilisateur à choisir parmi (undefined, player, admin)
 * @param sql: Chaîne de caractères de la requête SQL
 * @param params: Tableau des paramètres à binder avec la requête
 * @param res: Tableau des résultats passé par référence 
 * Peut être complété par des messages d'erreur si debug = true
 * @param fill_res: Fonction de signature fill_res($row, &$res) pour modifier 
 * le résultat en fonction des résultats éventuels de la requête. 
 * Ne pas utiliser si pas de résultat attendu.
 * @param error_fun: Fonction de signature error_fun(&$res) exécutée 
 * en cas d'erreur de requête 
 **/
function request_database($user, $sql, &$params, &$res, $error_fun = NULL, $fill_res = NULL) {
  
  // Connexion à la base de données
  $pdo = connect_database($user, $res);
  // Echec de connexion à la base
  if ($pdo === NULL) { return; }
    
  // Préparation de la requête
  $request = $pdo->prepare($sql);
    
  // Bindage des paramètres
  foreach($params as $param => $_) {
    $request->bindParam($param, $params[$param]);
  } 
    
  // Exécution de la requête
  if (!$request->execute()) {
    // Exécution de la fonction d'erreur
    if (is_null($error_fun)) {
      error_fun_default($request, $res);
    } 
    else {
      $error_fun($request, $res);
    } 
  }
  else {
    // Récupération des résultats éventuels de la requête
    foreach($request as $row) {
      $fill_res($row, $res);
    }
  }
    
  // Fermeture de la connexion à la base
  $pdo = NULL;
}

?>
