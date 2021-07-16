<?php

/**
 * Création de quiz par un administrateur
 * @return: JSON avec champ creation_quiz_status = true si le quiz a bien été 
 * créé et le login du créateur
 **/
function create_quiz($login, $open, $close, $difficulty, $points, $type, $title, $question) {
  $sql = "CALL create_quiz(:login, :open, :close, :difficulty, :points, :type, :title, :question)";
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
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["creation_quiz_status"] = false;
  };
  request_database($_SESSION["role"], $sql, $params, $res, $error_fun);
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