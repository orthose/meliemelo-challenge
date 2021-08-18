<?php

require_once("connect_mariadb.php");

/**
 * Création de quiz par un administrateur
 * @param responses: Tableau des réponses possibles, chaque réponse est de la forme
 * array("response" => "Un réponse au hasard", "valid" => true)
 * @return: JSON avec champ create_quiz_status = true si le quiz a bien été 
 * créé et le login du créateur
 **/
function create_quiz($open, $close, $difficulty, $points, $type, $title, $question, $responses) {
  
  // Création des métadonnées du quiz
  $sql = "SELECT create_quiz(:login, :open, :close, :difficulty, :points, :type, :title, :question)";
  $params = array(
    ":login" => get_login(), 
    ":open" => $open,
    ":close" => $close,
    ":difficulty" => $difficulty,
    ":points" => $points,
    ":type" => $type,
    ":title" => $title,
    ":question" => $question
  );
  $res = array("login" => get_login(), "create_quiz_status" => true);
  $fill_res = function($row, &$res) {
    $res["quiz_id"] = $row[0];
  };
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["create_quiz_status"] = false;
  };
  request_database(get_role(), $sql, $params, $res, $error_fun, $fill_res);
  
  // Ajout des réponses si la création n'a pas échoué
  if ($res["create_quiz_status"]) {
    $params = array(":quiz_id" => $res["quiz_id"]);
    foreach($responses as $response) {
      $sql = "CALL add_response(:quiz_id, :response, :valid)";
      $params[":response"] = $response["response"];
      $params[":valid"] = $response["valid"];
      request_database(get_role(), $sql, $params, $res, $error_fun);
      if (!$res["create_quiz_status"]) { break; }
    }
    // Mise en stock du quiz si toujours pas d'échec
    if ($res["create_quiz_status"]) {
      $sql = "CALL stock_quiz(:quiz_id)";
      $params = array(":quiz_id" => $res["quiz_id"]);
      request_database(get_role(), $sql, $params, $res, $error_fun);
    }
  }
  
  return $res;
}

/**
 * Suppression de quiz par l'administrateur en étant le créateur si en stock
 * @return: JSON avec champ create_quiz_status = true si le quiz a bien été 
 * créé, le login de l'utilisateur et l'id du quiz
 **/
function remove_quiz($quiz_id) {
  $sql = "CALL remove_quiz(:login, :quiz_id)";
  $params = array(":login" => get_login(), ":quiz_id" => $quiz_id);
  $res = array("login" => get_login(), "quiz_id" => $quiz_id, "remove_quiz_status" => true);
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["remove_quiz_status"] = false;
  };
  request_database(get_role(), $sql, $params, $res, $error_fun);
  return $res;
}

/**
 * Répondre à un quiz si le joueur n'en est pas le créateur
 * @param responses: Tableau des réponses de l'utilisateur
 * @return: JSON avec champ answer_quiz_status = true si pas d'erreur,
 * le login de l'utilisateur, l'id du quiz et la réponse choisie
 **/
function answer_quiz($quiz_id, $responses) {
  
  // Ajout des réponses
  $sql = "CALL answer_quiz(:login, :quiz_id, :response)";
  $params = array(":login" => get_login(), ":quiz_id" => $quiz_id);
  $res = array("login" => get_login(), "quiz_id" => $quiz_id, "answer_quiz_status" => true, "response" => $responses);
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["answer_quiz_status"] = false;
  };
  foreach ($responses as $response) {
    $params[":response"] = $response;
    request_database(get_role(), $sql, $params, $res, $error_fun);
    if (!$res["answer_quiz_status"]) { break; }
  }
  
  // Calcul du nombre de points
  if ($res["answer_quiz_status"]) {
    $sql = "SELECT check_answer(:login, :quiz_id)";
    $params = array(":login" => get_login(), ":quiz_id" => $quiz_id);
    $fill_res = function($row, &$res) {
      $res["points"] = $row[0];
    };
    request_database(get_role(), $sql, $params, $res, $error_fun, $fill_res);
  }
  
  return $res;
}

// Routine à exécuter avec crontab de manière régulière
function cron_routine() {
  $sql = "SELECT cron_routine()";
  $params = array();
  $res = array("cron_routine_status" => true);
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["cron_routine_status"] = false;
  };
  $fill_res = function($row, &$res) {
    $res_json = json_decode($row[0], true);
    $res["stock"] = $res_json["stock"];
    $res["close"] = $res_json["close"];
  };
  request_database("undefined", $sql, $params, $res, $error_fun, $fill_res);
  return $res;
}

/**
 * Renvoie les quiz jouables (dans l'état current)
 * avec les infos principales et les réponses
 **/
function quiz_current() {
  // Sélection des infos principales
  $sql = "SELECT * FROM QuizCurrentView WHERE login_creator != :login AND NOT EXISTS (SELECT * FROM PlayerQuizAnswered WHERE login = :login AND PlayerQuizAnswered.id = QuizCurrentView.id)";
  $params = array(":login" => get_login());
  $res = array("quiz" => array(), "responses" => array());
  $fill_res = function($row, &$res) {
    array_push($res["quiz"], array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8]));
  };
  request_database(get_role(), $sql, $params, $res, NULL, $fill_res);
  // Sélection des réponses
  $sql = "SELECT id, response FROM QuizResponsesCurrentView";
  $fill_res = function($row, &$res) {
    if (!isset($res["responses"][$row[0]])) {
      $res["responses"][$row[0]] = array();
    }
    array_push($res["responses"][$row[0]], $row[1]);
  };
  request_database(get_role(), $sql, $params, $res, NULL, $fill_res);
  return $res;
}

// Factorisation de deux requête quasi-identiques
function quiz_stock_archive($table1, $table2, $params) {
  // Sélection des infos principales
  $sql = "SELECT * FROM ".$table1;
  $res = array("quiz" => array(), "responses" => array());
  $fill_res = function($row, &$res) {
    array_push($res["quiz"], array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8]));
  };
  request_database(get_role(), $sql, $params, $res, NULL, $fill_res);
  // Sélection des réponses
  $sql = "SELECT * FROM ".$table2;
  $fill_res = function($row, &$res) {
    if (!isset($res["responses"][$row[0]])) {
      $res["responses"][$row[0]] = array();
    }
    array_push($res["responses"][$row[0]], array($row[1], $row[2]));
  };
  request_database(get_role(), $sql, $params, $res, NULL, $fill_res);
  return $res;
}

// Quiz jouables par les autres (pas par son créateur)
function quiz_current_not_playable() {
  // Sélection des infos principales
  $sql = "SELECT * FROM QuizCurrentView WHERE login_creator = :login";
  $params = array(":login" => get_login());
  $res = array("quiz" => array(), "responses" => array());
  $fill_res = function($row, &$res) {
    array_push($res["quiz"], array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8]));
  };
  request_database(get_role(), $sql, $params, $res, NULL, $fill_res);
  // Sélection des réponses
  $sql = "SELECT * FROM QuizResponsesCurrentView WHERE login_creator = :login";
  $fill_res = function($row, &$res) {
    if (!isset($res["responses"][$row[0]])) {
      $res["responses"][$row[0]] = array();
    }
    array_push($res["responses"][$row[0]], array($row[1], $row[2]));
  };
  request_database(get_role(), $sql, $params, $res, NULL, $fill_res);
  return $res;
}

/**
 * Renvoie les quiz jouables (dans l'état archive)
 * avec les infos principales et les réponses valides et invalides
 **/
function quiz_archive() {
  return quiz_stock_archive("QuizArchiveView", "QuizResponsesArchiveView", array());
}

/**
 * Renvoie les quiz en stock (dans l'état stock)
 * avec les infos principales et les réponses valides et invalides
 **/
function quiz_stock() {
  return quiz_stock_archive("QuizStockView WHERE login_creator = :login", "QuizResponsesStockView WHERE login_creator = :login", array(":login" => get_login()));
}

?>