<?php

/**
 * Création de quiz par un administrateur
 * @param responses: Tableau des réponses possibles, chaque réponse est de la forme
 * array("response" => "Un réponse au hasard", "valid" => true)
 * @return: JSON avec champ creation_quiz_status = true si le quiz a bien été 
 * créé et le login du créateur
 **/
function create_quiz($login, $open, $close, $difficulty, $points, $type, $title, $question, $responses) {
  
  // Création des métadonnées du quiz
  $sql = "SELECT create_quiz(:login, :open, :close, :difficulty, :points, :type, :title, :question)";
  $params = array(
    ":login" => $login, 
    ":open" => $open,
    ":close" => $close,
    ":difficulty" => $difficulty,
    ":points" => $points,
    ":type" => $type,
    ":title" => $title,
    ":question" => $question
  );
  $res = array("login" => $login, "creation_quiz_status" => true);
  $fill_res = function($row, &$res) {
    $res["quiz_id"] = $row[0];
  };
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["creation_quiz_status"] = false;
  };
  request_database($_SESSION["role"], $sql, $params, $res, $error_fun, $fill_res);
  
  // Ajout des réponses si la création n'a pas échoué
  if ($res["creation_quiz_status"]) {
    $params = array(":quiz_id" => $res["quiz_id"]);
    foreach($responses as $response) {
      $sql = "CALL add_response(:quiz_id, :response, :valid)";
      $params[":response"] = $response["response"];
      $params[":valid"] = $response["valid"];
      request_database($_SESSION["role"], $sql, $params, $res, $error_fun);
    }
    // Mise en stock du quiz si toujours pas d'échec
    if ($res["creation_quiz_status"]) {
      $sql = "CALL stock_quiz(:quiz_id)";
      $params = array(":quiz_id" => $res["quiz_id"]);
      request_database($_SESSION["role"], $sql, $params, $res, $error_fun);
    }
  }
  
  // Échec de la création de quiz
  if (!$res["creation_quiz_status"] && isset($res["quiz_id"])) {
    $sql = "DELETE FROM Quiz WHERE id = :quiz_id";
    $params = array(":quiz_id" => $res["quiz_id"]);
    request_database("main_user", $sql, $params, $res);
    $sql = "DELETE FROM QuizResponses WHERE id = :quiz_id";
    $params = array(":quiz_id" => $res["quiz_id"]);
    request_database("main_user", $sql, $params, $res);
    unset($res["quiz_id"]);
  }
  
  return json_encode($res);
}

/**
 * Suppression de quiz par l'administrateur en étant le créateur si en stock
 * @return: JSON avec champ creation_quiz_status = true si le quiz a bien été 
 * créé, le login de l'utilisateur et l'id du quiz
 **/
function remove_quiz($login, $quiz_id) {
  $sql = "CALL remove_quiz(:login, :quiz_id)";
  $params = array(":login" => $login, ":quiz_id" => $quiz_id);
  $res = array("login" => $login, "quiz_id" => $quiz_id, "remove_quiz_status" => true);
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["remove_quiz_status"] = false;
  };
  request_database($_SESSION["role"], $sql, $params, $res, $error_fun);
  return json_encode($res);
}

?>