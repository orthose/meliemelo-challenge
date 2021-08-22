<?php

require_once("connect_mariadb.php");

// Renvoie l'ensemble des bogues reportés
function bug_reported() {
  $bug_reported = json_decode(file_get_contents("public_data/bug_report.json"), true);
  $res = array("bug_reported" => $bug_reported);
  return $res;
}

function save_bug_reported($bug_reported) {
  return file_put_contents("public_data/bug_report.json", json_encode($bug_reported, JSON_PRETTY_PRINT)) !== false;
}

// Ajoute un bogue
function bug_report($bug_txt) {
  if ($bug_txt !== "") {
    $bug_reported = bug_reported()["bug_reported"];
    $bug = array(
      "login" => get_login(),
      "date" => date("Y-m-d"),
      "id" => count($bug_reported) + 1,
      "bug" => $bug_txt,
      "responses" => array()
    );
    array_unshift($bug_reported, $bug);
    return array("bug_report_status" => save_bug_reported($bug_reported));
  }
}

// Ajout d'une réponse à un bogue spécifique
function answer_bug($id, $response_txt) {
  if ($response_txt !== "") {
    $bug_reported = bug_reported()["bug_reported"];
    $response = array(
      "login" => get_login(),
      "date" => date("Y-m-d"),
      "response" => $response_txt
    );
    array_push($bug_reported[count($bug_reported) - $id]["responses"], $response);
    return array("answer_bug_status" => save_bug_reported($bug_reported));
  }
}

?>