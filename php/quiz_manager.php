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
  
  $res = array("login" => get_login(), "create_quiz_status" => true);
  // Initialisation de la session avec la base
  $pdo = connect_database(get_role(), $res);
  
  // Echec de connexion ?
  if ($pdo !== NULL) {
    
    try {
      // Activation de la transaction
      $pdo->beginTransaction();
      
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
    
      $request = $pdo->prepare($sql);
      foreach($params as $param => $_) {
        $request->bindParam($param, $params[$param]);
      } 
    
      check_request($request->execute());
      
      // Récupération de l'identifiant de quiz
      $res["quiz_id"] = $request->fetch()[0];
      $request->closeCursor();
      
      // Ajout des réponses si la création n'a pas échoué
      $sql = "CALL add_response(:quiz_id, :response, :valid)";
      $request = $pdo->prepare($sql);
      $request->bindParam(":quiz_id", $res["quiz_id"]);
      
      foreach($responses as $response) {
        $request->bindParam(":response", $response["response"]);
        $request->bindParam(":valid", $response["valid"]);
        check_request($request->execute());
      }
      
      // Vérification de la validité du quiz
      $sql = "CALL check_quiz(:quiz_id)";
      $request = $pdo->prepare($sql);
      $request->bindParam(":quiz_id", $res["quiz_id"]);
      // La fonction check_quiz renvoie une erreur si quiz invalide
      check_request($request->execute());
      
      // Acceptation de la transaction
      $pdo->commit();
    }
    
    // Echec de la création de quiz
    catch (Exception $e){
      error_fun_default($request, $res);
      $res["create_quiz_status"] = false;
      // Abandon de la transaction
      $pdo->rollback();
    }
  }     
   
  // Fermeture de la session
  $pdo = NULL;
  
  return $res;
}

/**
 * Suppression de quiz par l'administrateur en étant le créateur si en stock
 * @return: JSON avec champ remove_quiz_status = true si le quiz a bien été 
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
 * Remise en stock d'un quiz en jeu ou archivé par son créateur
 * @return: JSON avec champ quiz_stock_status = true si quiz bien mis en stock,
 * le login de l'utilisateur et l'id du quiz
 **/
function stock_quiz($quiz_id, $open_date, $close_date) {
  $sql = "CALL stock_quiz(:login, :quiz_id, :open_date, :close_date)";
  $params = array(":login" => get_login(), ":quiz_id" => $quiz_id, 
  ":open_date" => $open_date, ":close_date" => $close_date);
  $res = array("login" => get_login(), "quiz_id" => $quiz_id, "stock_quiz_status" => true);
  $error_fun = function($request, &$res) {
    error_fun_default($request, $res);
    $res["stock_quiz_status"] = false;
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
  
  $res = array("login" => get_login(), "quiz_id" => $quiz_id, "answer_quiz_status" => true, "response" => $responses);
  // Initialisation de la session avec la base
  $pdo = connect_database(get_role(), $res);
  
  // Echec de connexion ?
  if ($pdo !== NULL) {
  
    try {
      // Activation de la transaction
      $pdo->beginTransaction();
      
      // Ajout des réponses
      $sql = "CALL answer_quiz(:login, :quiz_id, :response)";
      $params = array(":login" => get_login(), ":quiz_id" => $quiz_id);
      
      $request = $pdo->prepare($sql);
      foreach($params as $param => $_) {
        $request->bindParam($param, $params[$param]);
      } 
      
      foreach ($responses as $response) {
        $request->bindParam(":response", $response);
        check_request($request->execute());
      }
      
      // Calcul du nombre de points
      $sql = "SELECT check_answer(:login, :quiz_id)";
      $params = array(":login" => get_login(), ":quiz_id" => $quiz_id);
      
      $request = $pdo->prepare($sql);
      foreach($params as $param => $_) {
        $request->bindParam($param, $params[$param]);
      }
      
      check_request($request->execute());
      $res["points"] = $request->fetch()[0];
      
      // Acceptation de la transaction
      $pdo->commit();
    }
    
    // Echec de d'envoi de la réponse
    catch (Exception $e) {
      error_fun_default($request, $res);
      $res["answer_quiz_status"] = false;
      // Abandon de la transaction
      $pdo->rollback();
    }
  }
  
  // Fermeture de la session
  $pdo = NULL;
  
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

// Factorisation de requêtes
function list_quiz($sql1, $sql2, $params, $fill_res1 = NULL, $fill_res2 = NULL) {
  // Sélection des infos principales
  $res = array("quiz" => array(), "responses" => array());
  $fill_res = $fill_res1 !== NULL ? $fill_res1 : 
  function($row, &$res) {
    $question = str_replace(array("\r\n", "\r", "\n"), "<br>\n", $row[8]);
    array_push($res["quiz"], array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $question));
  };
  request_database(get_role(), $sql1, $params, $res, NULL, $fill_res);
  // Sélection des réponses
  $fill_res = $fill_res2 !== NULL ? $fill_res2 :
  function($row, &$res) {
    if (!isset($res["responses"][$row[0]])) {
      $res["responses"][$row[0]] = array();
    }
    array_push($res["responses"][$row[0]], array($row[1], $row[2]));
  };
  if ($sql2 !== NULL) {
    request_database(get_role(), $sql2, $params, $res, NULL, $fill_res);
  }
  return $res;
}

/**
 * Renvoie les quiz jouables (dans l'état current)
 * avec les infos principales et les réponses
 **/
function quiz_current() {
  return list_quiz(
    "SELECT * FROM QuizCurrentView
    WHERE login_creator != :login
    AND id NOT IN (
      SELECT id FROM PlayerQuizAnswered WHERE login = :login)",
    "SELECT qrcv.id, qrcv.response FROM QuizCurrentView AS qcv, QuizResponsesCurrentView AS qrcv
    WHERE qcv.login_creator != :login
    -- La réponse des quiz text ne doit pas être envoyée
    AND qcv.type != 'text'
    AND qcv.id = qrcv.id
    AND qcv.id NOT IN (
      SELECT id FROM PlayerQuizAnswered WHERE login = :login)",
    array(":login" => get_login()), NULL,
    $fill_res = function($row, &$res) {
      if (!isset($res["responses"][$row[0]])) {
        $res["responses"][$row[0]] = array();
      }
      array_push($res["responses"][$row[0]], $row[1]);
    }
  );
}

// Quiz jouables par les autres (pas par son créateur)
function quiz_current_not_playable() {
  return list_quiz(
    "SELECT * FROM QuizCurrentView WHERE login_creator = :login",
    "SELECT * FROM QuizResponsesCurrentView WHERE login_creator = :login",
    array(":login" => get_login())
  );
}

/**
 * Renvoie les quiz jouables (dans l'état archive)
 * avec les infos principales et les réponses valides et invalides
 **/
function quiz_archive() {
  return list_quiz("SELECT * FROM QuizArchiveView", "SELECT * FROM QuizResponsesArchiveView", array());
}

/**
 * Renvoie les quiz en stock (dans l'état stock)
 * avec les infos principales et les réponses valides et invalides
 **/
function quiz_stock() {
  return list_quiz(
    "SELECT * FROM QuizStockView WHERE login_creator = :login", 
    "SELECT * FROM QuizResponsesStockView WHERE login_creator = :login", 
    array(":login" => get_login())
  );
}

/**
 * Renvoie les quiz qui peuvent être remis en stock
 * avec les infos principales et les réponses valides et invalides
 **/
function quiz_stockable() {
  return list_quiz(
    "SELECT * FROM QuizCurrentView WHERE login_creator = :login
    UNION SELECT * FROM QuizArchiveView WHERE login_creator = :login",
    NULL, array(":login" => get_login())
  );
}

/**
 * Renvoie les quiz modifiables (tous les quiz créés par l'utilisateur)
 * avec les infos principales et les réponses valides et invalides
 **/
function quiz_editable() {
  return list_quiz(
    "SELECT * FROM QuizStockView WHERE login_creator = :login
    UNION SELECT * FROM QuizCurrentView WHERE login_creator = :login
    UNION SELECT * FROM QuizArchiveView WHERE login_creator = :login", 
    "SELECT * FROM QuizResponsesStockView WHERE login_creator = :login
    UNION SELECT * FROM QuizResponsesCurrentView WHERE login_creator = :login
    UNION SELECT * FROM QuizResponsesArchiveView WHERE login_creator = :login", 
    array(":login" => get_login())
  );
}

/**
 * Renvoie les quiz auxquels a répondu le joueur
 * avec les infos principales et les réponses "valides" qui sont celles
 * sélectionnées par le joueur et "invalides" celles non-sélectionnées
 * ATTENTION: Ce ne sont donc pas les réponses valides et invalides au sens propre
 **/
function quiz_answered() {
  return list_quiz(
    "SELECT * FROM QuizAnsweredView WHERE login = :login", 
    "SELECT * FROM QuizResponsesAnsweredView WHERE login = :login", 
    array(":login" => get_login()),
    function($row, &$res) {
      $question = str_replace(array("\r\n", "\r", "\n"), "<br>\n", $row[8]);
      array_push($res["quiz"], array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $question, $row[10]));
    }
  );
}

?>