<?php

require("php/login_manager.php");
require("php/quiz_manager.php");

/**
 * Modèle pour vérifier que les bons paramètres sont donnés
 * @param valid: Si true fun est exécutée sinon un message d'erreur est renvoyé
 * @param doc: Documentation renvoyée sur l'utilisation de la requête
 * @param fun: Fonction à exécuter sans paramètre
 **/
function request_template($valid, $doc, $fun) {
  if ($valid) {
    $res = $fun();
    echo $res;
  }
  else {
    echo json_encode(array("parameters_error" => $doc));
  }
}

session_start();

// Paramètres généraux
$request = $_REQUEST["request"];

// Gestion des différentes actions possibles

// Actions de login_manager
if ($request === "connection") {
  $valid = (isset($_REQUEST["login"]) && isset($_REQUEST["passwd"]));
  $doc = "connection(login, passwd)";
  $fun = function() {
    $login = htmlspecialchars(trim($_REQUEST["login"]));
    $passwd = $_REQUEST["passwd"];
    return connection($login, $passwd);
  };
  request_template($valid, $doc, $fun);
}

else if ($request === "disconnection") {
  $valid = true;
  $doc = "disconnection()";
  $fun = function() { return disconnection(); };
  request_template($valid, $doc, $fun);
}

else if ($request === "check_session") {
  $valid = true;
  $doc = "check_session()";
  $fun = function() { return check_session(); };
  request_template($valid, $doc, $fun);
}

else if ($request === "register") {
  $valid = (isset($_REQUEST["login"]) && isset($_REQUEST["passwd"]));
  $doc = "register(login, passwd)";
  $fun = function() {
    $login = htmlspecialchars(trim($_REQUEST["login"]));
    $passwd = $_REQUEST["passwd"];
    return register($login, $passwd); 
  };
  request_template($valid, $doc, $fun);
}

else if ($request === "unregister") {
  $valid = isset($_REQUEST["passwd"]);
  $doc = "unregister(passwd)";
  $fun = function() {
    $passwd = $_REQUEST["passwd"];
    return unregister($passwd); 
  };
  request_template($valid, $doc, $fun);
}

else if ($request === "set_password") {
  $valid = (isset($_REQUEST["actual"]) && isset($_REQUEST["new"]));
  $doc = "set_password(actual, new)";
  $fun = function() {
    $actual_passwd = $_REQUEST["actual"];
    $new_passwd = $_REQUEST["new"];
    return set_password($actual_passwd, $new_passwd);
  };
  request_template($valid, $doc, $fun);
}

else if ($request === "set_role") {
  $valid = (isset($_REQUEST["login"]) && isset($_REQUEST["new_role"]));
  $doc = "set_role(login, new_role)";
  $fun = function() {
    $login = htmlspecialchars(trim($_REQUEST["login"]));
    $new_role = $_REQUEST["new_role"];
    return set_role($login, $new_role);
  };
  request_template($valid, $doc, $fun);
}

else if ($request === "high_score") {
  $valid = true;
  $doc = "high_score()";
  $fun = function() {
    return high_score();
  };
  request_template($valid, $doc, $fun);
}

// Actions de quiz_manager
else if ($request === "create_quiz") {
  $valid = (
    isset($_REQUEST["open"])
    && isset($_REQUEST["close"])
    && isset($_REQUEST["difficulty"])
    && isset($_REQUEST["points"])
    && isset($_REQUEST["type"])
    && isset($_REQUEST["title"])
    && isset($_REQUEST["question"])
    && isset($_REQUEST["responses"])
  );
  $doc = "create_quiz(open, close, difficulty, points, type, title, question, responses)";
  $fun = function() {
    $open = $_REQUEST["open"];
    $close = $_REQUEST["close"];
    $difficulty = $_REQUEST["difficulty"];
    $points = $_REQUEST["points"];
    $type = $_REQUEST["type"];
    $title = htmlspecialchars(trim($_REQUEST["title"]));
    $question = htmlspecialchars(trim($_REQUEST["question"]));
    $responses = $_REQUEST["responses"];
    for($i = 0; $i < count($responses); $i++) {
      $responses[$i]["response"] = htmlspecialchars(trim($responses[$i]["response"]), ENT_QUOTES);
    }
    return create_quiz($open, $close, $difficulty, $points, $type, $title, $question, $responses);
  };
  request_template($valid, $doc, $fun);
}

else if ($request === "remove_quiz") {
  $valid = isset($_REQUEST["quiz_id"]);
  $doc = "remove_quiz(quiz_id)";
  $fun = function() {
    $quiz_id = $_REQUEST["quiz_id"];
    return remove_quiz($quiz_id);
  };
  request_template($valid, $doc, $fun);
}

else if ($request === "answer_quiz") {
  $valid = (isset($_REQUEST["quiz_id"]));
  $doc = "answer_quiz(quiz_id, responses = array())";
  $fun = function() {
    $quiz_id = $_REQUEST["quiz_id"];
    $responses = array();
    if (isset($_REQUEST["responses"])) {
      $responses = $_REQUEST["responses"];
    }
    for($i = 0; $i < count($responses); $i++) {
      $responses[$i] = htmlspecialchars(trim($responses[$i]), ENT_QUOTES);
    }
    return answer_quiz($quiz_id, $responses);
  };
  request_template($valid, $doc, $fun);
}

else if ($request === "quiz_current") {
  $valid = true;
  $doc = "quiz_current()";
  $fun = function() { return quiz_current(); };
  request_template($valid, $doc, $fun);
}

// Action non-répertoriée
else {
  $valid = false;
  $doc = "Action doesn't exist";
  request_template($valid, $doc, NULL);
}

?>