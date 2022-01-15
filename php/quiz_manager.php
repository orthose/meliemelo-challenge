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
    
      execute_request($request);
      
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
        execute_request($request);
      }
      
      // Vérification de la validité du quiz
      $sql = "CALL check_quiz(:quiz_id)";
      $request = $pdo->prepare($sql);
      $request->bindParam(":quiz_id", $res["quiz_id"]);
      // La fonction check_quiz renvoie une erreur si quiz invalide
      execute_request($request);
      
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
        execute_request($request);
      }
      
      // Calcul du nombre de points
      $sql = "SELECT check_answer(:login, :quiz_id)";
      $params = array(":login" => get_login(), ":quiz_id" => $quiz_id);
      
      $request = $pdo->prepare($sql);
      foreach($params as $param => $_) {
        $request->bindParam($param, $params[$param]);
      }
      
      execute_request($request);
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

/** 
 * Factorisation de requêtes de listage des quiz
 * @param sql1: string de la requête SQL des métadonnées
 * @param sql2: string de la requête SQL des réponses aux quiz
 * @param year: Année de filtrage facultative
 * @param fill_res1: Remplissage facultatif du tableau des résultats pour les métadonnées
 * @param fill_res1: Remplissage facultatif du tableau des résultats pour les réponses aux quiz
 * 
 * Note : Les requêtes SQL doivent toujours finir par WHERE ou WHERE ... AND pour permettre
 * le filtrage sur l'année qui est obligatoire. La fonction le laisse optionnel car quiz_current
 * ne peut pas suivre ce pattern de factorisation.
 **/
function list_quiz($sql1, $sql2, $params, $year = NULL, $fill_res1 = NULL, $fill_res2 = NULL) {
  $res = array("quiz" => array(), "responses" => array());
  request_database_manual($res,
    function($pdo, &$res) use($sql1, $sql2, $params, $year, $fill_res1, $fill_res2) {
      
      // Sélection des infos principales
      $sql1 = $year !== NULL ? str_replace("[YEAR]", " (YEAR(open) = :year OR YEAR(close) = :year) ", $sql1) : $sql1;
      $sql = "CREATE TEMPORARY TABLE QuizHeader (".$sql1.")";
      $fill_res = $fill_res1 !== NULL ? $fill_res1 : 
      function($row, &$res) {
        $question = str_replace(array("\r\n", "\r", "\n"), "<br>\n", $row[8]);
        array_push($res["quiz"], array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $question));
      };
      $request = $pdo->prepare($sql);
      foreach($params as $param => $_) {
        $request->bindParam($param, $params[$param]);
      } 
      if ($year !== NULL) { $request->bindParam(":year", $year); }
      execute_request($request);
      $sql = "SELECT * FROM QuizHeader";
      $request = $pdo->prepare($sql);
      execute_request($request);
      foreach($request as $row) {
        $fill_res($row, $res);
      }
      
      // Sélection des réponses
      if ($sql2 !== NULL) {
        $sql = str_replace("[ID]", " id IN (SELECT id FROM QuizHeader) ", $sql2);
        $fill_res = $fill_res2 !== NULL ? $fill_res2 :
        function($row, &$res) {
          if (!isset($res["responses"][$row[0]])) {
            $res["responses"][$row[0]] = array();
          }
          array_push($res["responses"][$row[0]], array($row[1], $row[2]));
        };
        $request = $pdo->prepare($sql);
        foreach($params as $param => $_) {
          $request->bindParam($param, $params[$param]);
        }
        execute_request($request);
        foreach($request as $row) {
          $fill_res($row, $res);
        }
      }
    }
  );
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
    WHERE qrcv.id IN (SELECT id FROM QuizHeader) AND qcv.id = qrcv.id
    -- La réponse des quiz text ne doit pas être envoyée
    AND qcv.type != 'text'",
    array(":login" => get_login()), NULL, NULL,
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
    "SELECT * FROM QuizResponsesCurrentView WHERE [ID]",
    array(":login" => get_login())
  );
}

/**
 * Renvoie les quiz jouables (dans l'état archive)
 * avec les infos principales et les réponses valides et invalides
 **/
function quiz_archive($year) {
  return list_quiz(
    "SELECT * FROM QuizArchiveView WHERE [YEAR]", 
    "SELECT * FROM QuizResponsesArchiveView WHERE [ID]", 
    array(), $year
  );
}

/**
 * Renvoie les quiz en stock (dans l'état stock)
 * avec les infos principales et les réponses valides et invalides
 **/
function quiz_stock($year) {
  return list_quiz(
    "SELECT * FROM QuizStockView WHERE login_creator = :login AND [YEAR]", 
    "SELECT * FROM QuizResponsesStockView WHERE [ID]", 
    array(":login" => get_login()), $year
  );
}

/**
 * Renvoie les quiz qui peuvent être remis en stock
 * avec les infos principales et les réponses valides et invalides
 **/
function quiz_stockable($year) {
  return list_quiz(
    "SELECT * FROM (SELECT * FROM QuizCurrentView WHERE login_creator = :login AND [YEAR]
    UNION SELECT * FROM QuizArchiveView WHERE login_creator = :login AND [YEAR]) AS t",
    NULL, array(":login" => get_login()), $year
  );
}

/**
 * Renvoie les quiz modifiables (tous les quiz créés par l'utilisateur)
 * avec les infos principales et les réponses valides et invalides
 **/
function quiz_editable($year) {
  return list_quiz(
    "SELECT * FROM (SELECT * FROM
    (SELECT * FROM QuizStockView WHERE login_creator = :login AND [YEAR]
    UNION SELECT * FROM QuizCurrentView WHERE login_creator = :login AND [YEAR]) AS t
    UNION SELECT * FROM QuizArchiveView WHERE login_creator = :login AND [YEAR]) AS u", 
    "SELECT * FROM QuizResponsesStockView WHERE [ID]
    UNION SELECT * FROM QuizResponsesCurrentView WHERE [ID]
    UNION SELECT * FROM QuizResponsesArchiveView WHERE [ID]", 
    array(":login" => get_login()), $year
  );
}

/**
 * Renvoie les quiz auxquels a répondu le joueur
 * avec les infos principales et les réponses "valides" qui sont celles
 * sélectionnées par le joueur et "invalides" celles non-sélectionnées
 * ATTENTION: Ce ne sont donc pas les réponses valides et invalides au sens propre
 **/
function quiz_answered($year) {
  return list_quiz(
    "SELECT * FROM QuizAnsweredView WHERE login = :login AND [YEAR]", 
    "SELECT * FROM QuizResponsesAnsweredView WHERE login = :login AND [ID]", 
    array(":login" => get_login()), $year,
    function($row, &$res) {
      $question = str_replace(array("\r\n", "\r", "\n"), "<br>\n", $row[8]);
      array_push($res["quiz"], array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $question, $row[10]));
    }
  );
}

?>