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
}

else if ($request === "disconnection") {
  $valid = true;
  $doc = "disconnection()";
  $fun = function() { return disconnection(); };
}

else if ($request === "check_session") {
  $valid = true;
  $doc = "check_session()";
  $fun = function() { return check_session(); };
}

else if ($request === "register") {
  $valid = (isset($_REQUEST["login"]) && isset($_REQUEST["passwd"]));
  $doc = "register(login, passwd)";
  $fun = function() {
    $login = htmlspecialchars(trim($_REQUEST["login"]));
    $passwd = $_REQUEST["passwd"];
    return register($login, $passwd); 
  };
}

else if ($request === "unregister") {
  $valid = isset($_REQUEST["passwd"]);
  $doc = "unregister(passwd)";
  $fun = function() {
    $passwd = $_REQUEST["passwd"];
    return unregister($passwd); 
  };
}

else if ($request === "set_password") {
  $valid = (isset($_REQUEST["actual"]) && isset($_REQUEST["new"]));
  $doc = "set_password(actual, new)";
  $fun = function() {
    $actual_passwd = $_REQUEST["actual"];
    $new_passwd = $_REQUEST["new"];
    return set_password($actual_passwd, $new_passwd);
  };
}

else if ($request === "set_role") {
  $valid = (isset($_REQUEST["login"]) && isset($_REQUEST["new_role"]));
  $doc = "set_role(login, new_role)";
  $fun = function() {
    $login = htmlspecialchars(trim($_REQUEST["login"]));
    $new_role = $_REQUEST["new_role"];
    return set_role($login, $new_role);
  };
}

else if ($request === "high_score") {
  $valid = true;
  $doc = "high_score()";
  $fun = function() { return high_score(); };
}

else if ($request === "reset_high_score") {
  $valid = isset($_SESSION["login"]) && isset($_REQUEST["passwd"]);
  $doc = "reset_high_score(passwd)";
  $fun = function() { return reset_high_score($_REQUEST["passwd"]); };
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
      $responses[$i]["response"] = htmlspecialchars(trim($responses[$i]["response"]), ENT_COMPAT);
    }
    return create_quiz($open, $close, $difficulty, $points, $type, $title, $question, $responses);
  };
}

else if ($request === "edit_quiz") {
  $valid = (
    isset($_REQUEST["quiz_id"])
    && isset($_REQUEST["open"])
    && isset($_REQUEST["close"])
    && isset($_REQUEST["difficulty"])
    && isset($_REQUEST["points"])
    && isset($_REQUEST["type"])
    && isset($_REQUEST["title"])
    && isset($_REQUEST["question"])
    && isset($_REQUEST["overwrite"])
  );
  $doc = "edit_quiz(quiz_id, open, close, difficulty, points, type, title, question, responses, overwrite)";
  $fun = function() {
    $quiz_id = $_REQUEST["quiz_id"];
    $open = $_REQUEST["open"];
    $close = $_REQUEST["close"];
    $difficulty = $_REQUEST["difficulty"];
    $points = $_REQUEST["points"];
    $type = $_REQUEST["type"];
    $title = htmlspecialchars(trim($_REQUEST["title"]));
    $question = htmlspecialchars(trim($_REQUEST["question"]));
    $responses = array();
    if (isset($_REQUEST["responses"])) {
      $responses = $_REQUEST["responses"];
      for($i = 0; $i < count($responses); $i++) {
        $responses[$i]["response"] = htmlspecialchars(trim($responses[$i]["response"]), ENT_COMPAT);
      }
    }
    $overwrite = (bool)$_REQUEST["overwrite"];
    return edit_quiz($quiz_id, $open, $close, $difficulty, $points, $type, $title, $question, $responses, $overwrite);
  };
}

else if ($request === "remove_quiz") {
  $valid = isset($_REQUEST["quiz_id"]);
  $doc = "remove_quiz(quiz_id)";
  $fun = function() {
    $quiz_id = $_REQUEST["quiz_id"];
    return remove_quiz($quiz_id);
  };
}

else if ($request === "stock_quiz") {
  $valid = isset($_REQUEST["quiz_id"]) && isset($_REQUEST["open_date"]) && isset($_REQUEST["close_date"]);
  $doc = "qtock_quiz(quiz_id, open_date, close_date)";
  $fun = function() {
    $quiz_id = $_REQUEST["quiz_id"];
    $open_date = $_REQUEST["open_date"];
    $close_date = $_REQUEST["close_date"];
    return stock_quiz($quiz_id, $open_date, $close_date);
  };
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
      $responses[$i] = htmlspecialchars(trim($responses[$i]), ENT_COMPAT);
    }
    return answer_quiz($quiz_id, $responses);
  };
}

else if ($request === "quiz_current") {
  $valid = true;
  $doc = "quiz_current()";
  $fun = function() { return quiz_current(); };
}

else if ($request === "quiz_current_not_playable") {
  // La base de données ne gère pas cette permission
  $valid = isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
  $doc = "quiz_current_not_playable()";
  $fun = function() { return quiz_current_not_playable(); };
}

else if ($request === "quiz_archive") {
  $valid = isset($_REQUEST["num_page"]);
  $doc = "quiz_archive(num_page)";
  $fun = function() { return quiz_archive($_REQUEST["num_page"]); };
}

else if ($request === "quiz_stock") {
  $valid = isset($_REQUEST["num_page"]);
  $doc = "quiz_stock(num_page)";
  $fun = function() { return quiz_stock($_REQUEST["num_page"]); };
}

else if ($request === "quiz_stockable") {
  // La base de données ne gère pas cette permission
  $valid = isset($_SESSION["role"]) && $_SESSION["role"] === "admin" && isset($_REQUEST["num_page"]);
  $doc = "quiz_stockable(num_page)";
  $fun = function() { return quiz_stockable($_REQUEST["num_page"]); };
}

else if ($request === "quiz_editable") {
  $valid = isset($_REQUEST["num_page"]);
  $doc = "quiz_editable(num_page)";
  $fun = function() { return quiz_editable($_REQUEST["num_page"]); };
}

else if ($request === "quiz_answered") {
  $valid = isset($_REQUEST["num_page"]);
  $doc = "quiz_answered(num_page)";
  $fun = function() { return quiz_answered($_REQUEST["num_page"]); };
}

else if ($request === "quiz_answered_others") {
  $valid = isset($_REQUEST["login"]) && $_REQUEST["num_page"];
  $doc = "quiz_answered_others(login, num_page)";
  $fun = function() { return quiz_answered_others($_REQUEST["login"], $_REQUEST["num_page"]); };
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
}

else if ($request === "bug_reported") {
  // La base de données ne gère pas cette permission
  $valid = isset($_SESSION["role"]) && ($_SESSION["role"] === "admin" || $_SESSION["role"] === "player");
  $doc = "bug_reported()";
  $fun = function() { return bug_reported(); };
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
}

// Action non-répertoriée
else {
  $valid = false;
  $doc = "Action doesn't exist";
  $fun = NULL;
}

// Appel de la requête
request_template($valid, $doc, $fun);

?>