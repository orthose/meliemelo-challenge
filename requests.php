<?php

require("php/login_manager.php");
require("php/quiz_manager.php");
require("php/bug_report.php");

/**
 * Modèle pour vérifier que les bons paramètres sont donnés
 * @param valid: Si true fun est exécutée sinon un message d'erreur est renvoyé
 * @param doc: Documentation renvoyée sur l'utilisation de la requête
 * @param fun: Fonction à exécuter sans paramètre
 **/
function request_template($valid, $doc, $fun) {
  $status_session = (isset($_SESSION["login"]) && isset($_SESSION["role"]));
  if ($valid) {
    $res = $fun();
    $res["status_session"] = $status_session;
    echo json_encode($res);
  }
  else {
    echo json_encode(array("parameters_error" => $doc, "status_session" =>  $status_session));
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

else if ($request === "high_score_quiz_title") {
  $valid = (isset($_REQUEST["title"]) && isset($_REQUEST["begin_date"]) && isset($_REQUEST["end_date"]));
  $doc = "high_score_quiz_title(title, begin_date, end_date)";
  $fun = function() {
    // Pas protégé pour permettre regex
    $title = $_REQUEST["title"];
    // Pas de filtrage temporel
    if ($_REQUEST["begin_date"] === "" || $_REQUEST["end_date"] === "") {
      $begin_date = NULL;
      $end_date = NULL;
    }
    else {
      $begin_date = $_REQUEST["begin_date"];
      $end_date = $_REQUEST["end_date"];
    }
    return high_score_quiz_title($title, $begin_date, $end_date);
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

else if ($request === "quiz_current_not_playable") {
  // La base de données ne gère pas cette permission
  $valid = isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
  $doc = "quiz_current_not_playable()";
  $fun = function() { return quiz_current_not_playable(); };
  request_template($valid, $doc, $fun);
}

else if ($request === "quiz_archive") {
  $valid = true;
  $doc = "quiz_archive()";
  $fun = function() { return quiz_archive(); };
  request_template($valid, $doc, $fun);
}

else if ($request === "quiz_stock") {
  $valid = true;
  $doc = "quiz_stock()";
  $fun = function() { return quiz_stock(); };
  request_template($valid, $doc, $fun);
}

else if ($request === "quiz_answered") {
  $valid = true;
  $doc = "quiz_answered()";
  $fun = function() { return quiz_answered(); };
  request_template($valid, $doc, $fun);
}

else if ($request === "set_daily_msg") {
  // La base de données ne gère pas cette permission
  $valid = isset($_REQUEST["msg"]) && isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
  $doc = "set_daily_msg(msg)";
  $fun = function() {
    $msg = htmlspecialchars(trim($_REQUEST["msg"]));
    $res = array();
    $res["set_daily_msg_status"] = (file_put_contents("public_data/daily_msg.txt", $msg) !== false);
    return $res;
  };
  request_template($valid, $doc, $fun);
}

else if ($request === "bug_reported") {
  // La base de données ne gère pas cette permission
  $valid = isset($_SESSION["role"]) && ($_SESSION["role"] === "admin" || $_SESSION["role"] === "player");
  $doc = "bug_reported()";
  $fun = function() { return bug_reported(); };
  request_template($valid, $doc, $fun);
}

else if ($request === "bug_report") {
  // La base de données ne gère pas cette permission
  $valid = isset($_REQUEST["bug"]) && $_REQUEST["bug"] !== "" && isset($_SESSION["role"]) && isset($_SESSION["login"]) && ($_SESSION["role"] === "admin" || $_SESSION["role"] === "player");
  $doc = "bug_report(bug)";
  $fun = function() {
    $bug = htmlspecialchars(trim($_REQUEST["bug"]));
    $bug = str_replace(array("\r\n", "\r", "\n"), "<br>\n", $bug);
    return bug_report($bug); 
  };
  request_template($valid, $doc, $fun);
}

else if ($request === "answer_bug") {
  // La base de données ne gère pas cette permission
  $valid = isset($_REQUEST["id"]) && isset($_REQUEST["response"]) && $_REQUEST["response"] !== "" && isset($_SESSION["role"]) && isset($_SESSION["login"]) && ($_SESSION["role"] === "admin" || $_SESSION["role"] === "player");
  $doc = "answer_bug(response)";
  $fun = function() { 
    $response = htmlspecialchars(trim($_REQUEST["response"]));
    $response = str_replace(array("\r\n", "\r", "\n"), "<br>\n", $response);
    return answer_bug($_REQUEST["id"], $response); 
  };
  request_template($valid, $doc, $fun);
}

// Action non-répertoriée
else {
  $valid = false;
  $doc = "Action doesn't exist";
  request_template($valid, $doc, NULL);
}

?>