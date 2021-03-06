/* Enregistrer un nouvel utilisateur */
DELIMITER //
CREATE OR REPLACE PROCEDURE register_new_user (
  new_login TYPE OF Users.login, 
  new_passwd VARCHAR(2048)
)
BEGIN
  DECLARE random_salt TYPE OF Users.salt;
  DECLARE hash_passwd TYPE OF Users.password;
  IF LENGTH(new_passwd) < 8 THEN
    SIGNAL SQLSTATE '45007' 
    SET MESSAGE_TEXT = 'Password must have length >= 8' ;
  ELSE
    SELECT LEFT(MD5(RAND()), 16) INTO random_salt;
    SELECT SHA2(CONCAT(new_passwd, random_salt), 256) INTO hash_passwd;
    INSERT INTO Users (login, password, salt)
    VALUES (new_login, hash_passwd, random_salt);
  END IF;
END; //
DELIMITER ;

/* Récupérer le rôle de l'utilisateur */
DELIMITER //
CREATE OR REPLACE FUNCTION get_role (
  login_user TYPE OF Users.login
) RETURNS ENUM('player', 'admin')
BEGIN
  DECLARE res TYPE OF Users.role;
  SELECT role INTO res FROM Users WHERE login = login_user;
  RETURN res;
END; //
DELIMITER ;

/** 
 * Authentification avec couple (login, password) 
 * @return TRUE si la connexion est valide 
 **/
DELIMITER //
CREATE OR REPLACE FUNCTION authentication_is_valid (
  login_user TYPE OF Users.login,
  passwd_user VARCHAR(2048)
) RETURNS BOOLEAN
BEGIN
  DECLARE res BOOLEAN;
  SELECT EXISTS(
    SELECT * FROM Users 
    WHERE login = login_user 
      AND password = SHA2(CONCAT(passwd_user, salt), 256)
  ) INTO res;
  IF res THEN
    UPDATE Users SET connection = CURRENT_TIMESTAMP WHERE login = login_user;
    UPDATE Users SET visits = visits + 1 WHERE login = login_user;
  END iF;
  RETURN res;
END; //
DELIMITER ;

/** 
 * Vérifie que le login existe déjà
 * @return TRUE si le login existe déjà 
 **/
DELIMITER //
CREATE OR REPLACE FUNCTION login_exists (
  login_user TYPE OF Users.login
) RETURNS BOOLEAN
BEGIN
  DECLARE res BOOLEAN;
  SELECT EXISTS(
  SELECT TRUE FROM Users 
  WHERE login = login_user)
  INTO res;
  RETURN res;
END; //
DELIMITER ;

/* Changer le rôle d'un utilisateur */
DELIMITER //
CREATE OR REPLACE PROCEDURE set_role (
  login_user TYPE OF Users.login,
  new_role TYPE OF Users.role
)
BEGIN
  IF NOT login_exists(login_user) 
  THEN
    SIGNAL SQLSTATE '45008' 
    SET MESSAGE_TEXT = 'Invalid login';
  ELSE
    UPDATE Users SET role = new_role 
    WHERE login = login_user;
  END IF;
END; //
DELIMITER ;

/* Changer le mot de passe */
DELIMITER //
CREATE OR REPLACE PROCEDURE set_password (
  login_user TYPE OF Users.login,
  actual_passwd VARCHAR(2048),
  new_passwd VARCHAR(2048)
)
BEGIN
  IF LENGTH(new_passwd) < 8 THEN
    SIGNAL SQLSTATE '45009' 
    SET MESSAGE_TEXT = 'Password must have length >= 8';
  ELSEIF NOT authentication_is_valid(login_user, actual_passwd) THEN
    SIGNAL SQLSTATE '45010' 
    SET MESSAGE_TEXT = 'Authentication has failed';
  ELSE
    UPDATE Users SET password = SHA2(CONCAT(new_passwd, salt), 256) 
    WHERE login = login_user;
  END IF;
END; //
DELIMITER ;

/** 
 * Procédure de modification d'urgence du mot de passe pour l'usage
 * de l'administrateur du serveur
 * ATTENTION : Cette procédure ne doit pas avoir d'interface web et est
 * réservée à la ligne de commande
 **/
DELIMITER //
CREATE OR REPLACE PROCEDURE emergency_set_password (
  login_user TYPE OF Users.login,
  new_passwd VARCHAR(2048)
)
BEGIN
  IF LENGTH(new_passwd) < 8 THEN
    SIGNAL SQLSTATE '45011' 
    SET MESSAGE_TEXT = 'Password must have length >= 8';
  ELSEIF NOT login_exists(login_user) THEN
    SIGNAL SQLSTATE '45012' 
    SET MESSAGE_TEXT = 'Login doesn\'t exist';
  ELSE
    UPDATE Users SET password = SHA2(CONCAT(new_passwd, salt), 256) 
    WHERE login = login_user;
  END IF;
END; //
DELIMITER ;

/* Supprimer un utilisateur */
DELIMITER //
CREATE OR REPLACE PROCEDURE unregister_user (
  login_user TYPE OF Users.login,
  passwd VARCHAR(2048)
)
BEGIN
  IF NOT authentication_is_valid(login_user, passwd) THEN
    SIGNAL SQLSTATE '45013' 
    SET MESSAGE_TEXT = 'Authentication has failed';
  ELSE
    START TRANSACTION;
    CREATE TEMPORARY TABLE QuizCreated (
      SELECT id FROM Quiz WHERE login_creator = login_user
    );
    DELETE FROM PlayerQuizAnswered 
    WHERE login = login_user OR id IN (SELECT id FROM QuizCreated);
    DELETE FROM PlayerQuizResponses 
    WHERE login = login_user OR id IN (SELECT id FROM QuizCreated);
    DELETE FROM QuizResponses WHERE id IN (SELECT id FROM QuizCreated);
    DELETE FROM Quiz WHERE id IN (SELECT id FROM QuizCreated);
    DELETE FROM Users WHERE login = login_user;
    COMMIT;
  END IF;
END; //
DELIMITER ;

/* Vue pour la table des scores */
CREATE OR REPLACE VIEW HighScoreView AS
SELECT login, points, success, fail FROM Users
ORDER BY points DESC, success DESC, fail ASC;

/* Remise à zéro du tableau de score global */
DELIMITER //
CREATE OR REPLACE PROCEDURE reset_high_score (
  login_user TYPE OF Users.login,
  passwd VARCHAR(2048)
)
BEGIN
  IF NOT authentication_is_valid(login_user, passwd) THEN
    SIGNAL SQLSTATE '45014' 
    SET MESSAGE_TEXT = 'Authentication has failed';
  ELSE
    START TRANSACTION;
    UPDATE Users SET points = 0;
    UPDATE Users SET success = 0;
    UPDATE Users SET fail = 0;
    COMMIT;
  END IF;
END; //
DELIMITER ;

/**
 * Créer un nouveau quiz
 * @implementation
 * Une séquence n'est pas gapless et s'incrémente à chaque lecture
 * Une séquence ne prend pas en compte les transactions
 * Une séquence est performante et gère la concurrence
 * Une fonction n'autorise pas les transactions
 * @throws Exceptions sur les contraintes de Quiz
 * @return Identifiant du quiz créé
 **/
DELIMITER //
CREATE OR REPLACE FUNCTION create_quiz (
  login TYPE OF Quiz.login_creator,
  open TYPE OF Quiz.open,
  close TYPE OF Quiz.close,
  difficulty TYPE OF Quiz.difficulty,
  points TYPE OF Quiz.points,
  type TYPE OF Quiz.type,
  title TYPE OF Quiz.title,
  question TYPE OF Quiz.question
) RETURNS BIGINT UNSIGNED
BEGIN
  DECLARE quiz_id BIGINT UNSIGNED;
  SELECT NEXTVAL(QuizSequence) INTO quiz_id;
  -- Peut renvoyer des exceptions
  INSERT INTO Quiz (id, login_creator, open, close, difficulty, points, type, title, question)
  VALUES (quiz_id, login, open, close, difficulty, points, type, title, question);
  RETURN quiz_id;
END; //
DELIMITER ;

/* Mettre à jour tous les champs d'un quiz existant */
DELIMITER //
CREATE OR REPLACE PROCEDURE edit_quiz (
  quiz_id TYPE OF Quiz.id,
  login_user TYPE OF Quiz.login_creator,
  new_open TYPE OF Quiz.open,
  new_close TYPE OF Quiz.close,
  new_difficulty TYPE OF Quiz.difficulty,
  new_points TYPE OF Quiz.points,
  new_type TYPE OF Quiz.type,
  new_title TYPE OF Quiz.title,
  new_question TYPE OF Quiz.question
)
BEGIN
  IF (SELECT login_creator FROM Quiz WHERE id = quiz_id) != login_user THEN
    SIGNAL SQLSTATE '45015' 
    SET MESSAGE_TEXT = 'Invalid login_creator for this quiz';
  END IF;
  UPDATE Quiz SET 
  open = new_open,
  close = new_close,
  difficulty = new_difficulty,
  points = new_points,
  type = new_type,
  title = new_title,
  question = new_question
  WHERE id = quiz_id;
END; //
DELIMITER ;

/**
 * Suppression des réponses d'un quiz
 * Permet de mettre à jour les réponses d'un quiz
 * ATTENTION: Supprime les réponses des joueurs pour ce quiz
 **/
DELIMITER //
CREATE OR REPLACE PROCEDURE remove_quiz_responses (
  quiz_id TYPE OF Quiz.id
)
BEGIN
  START TRANSACTION;
  DELETE FROM PlayerQuizAnswered WHERE id = quiz_id;
  DELETE FROM PlayerQuizResponses WHERE id = quiz_id;
  DELETE FROM QuizResponses WHERE id = quiz_id;
  COMMIT;
END; //
DELIMITER ;

/**
 * Vérifie la validité d'un quiz en fonction des réponses et du type de quiz
 * Renvoie une erreur si le quiz est invalide
 **/
DELIMITER //
CREATE OR REPLACE PROCEDURE check_quiz (
  quiz_id TYPE OF Quiz.id
)
BEGIN
  DECLARE quiz_type TYPE OF Quiz.type;
  SELECT type INTO quiz_type FROM Quiz WHERE id = quiz_id;
  -- Au moins une réponse valide pour quiz checkbox
  IF (quiz_type LIKE 'checkbox%')
  AND NOT (SELECT COUNT(*) >= 1 FROM QuizResponses WHERE id = quiz_id AND valid)
  THEN
    SIGNAL SQLSTATE '45016' 
    SET MESSAGE_TEXT = 'Checkbox quiz must have at least one valid response';
  -- Une seule réponse valide parmi les réponses pour quiz radio
  ELSEIF quiz_type = 'radio' 
  AND NOT (SELECT COUNT(*) = 1 FROM QuizResponses WHERE id = quiz_id AND valid) 
  THEN
    SIGNAL SQLSTATE '45017' 
    SET MESSAGE_TEXT = 'Radio quiz must have only one valid response';
  -- Une seule réponse valide uniquement pour quiz text
  ELSEIF quiz_type LIKE 'text%'
  AND NOT (
    (SELECT COUNT(*) FROM QuizResponses WHERE id = quiz_id AND valid) = 1
    AND (SELECT COUNT(*) FROM QuizResponses WHERE id = quiz_id AND NOT valid) = 0
  )
  THEN
    SIGNAL SQLSTATE '45018' 
    SET MESSAGE_TEXT = 'Text quiz must have only one response and valid';
  END IF;
END; //
DELIMITER ;

/* Supprimer un quiz existant quel que soit son état */
DELIMITER //
CREATE OR REPLACE PROCEDURE remove_quiz (
  login TYPE OF Quiz.login_creator,
  quiz_id TYPE OF Quiz.id
)
BEGIN
  IF NOT EXISTS(SELECT * FROM Quiz WHERE login_creator = login AND id = quiz_id) 
  THEN
    SIGNAL SQLSTATE '45019' 
    SET MESSAGE_TEXT = 'Invalid login creator or id for this quiz';
  ELSE
    START TRANSACTION;
    DELETE FROM PlayerQuizAnswered WHERE id = quiz_id;
    DELETE FROM PlayerQuizResponses WHERE id = quiz_id;
    DELETE FROM QuizResponses WHERE id = quiz_id;
    DELETE FROM Quiz WHERE login_creator = login AND id = quiz_id;
    COMMIT;
  END IF;
END; //
DELIMITER ;

/* Vue pour obtenir les quiz en stock */
CREATE OR REPLACE VIEW QuizStockView AS SELECT id, login_creator, open, close, difficulty, points, type, title, question FROM Quiz WHERE state = 'stock' ORDER BY open ASC, close ASC;

/* Vue pour obtenir les quiz courants */
CREATE OR REPLACE VIEW QuizCurrentView AS SELECT id, login_creator, open, close, difficulty, points, type, title, question FROM Quiz WHERE state = 'current' ORDER BY close ASC, open ASC;

/* Vue pour obtenir les quiz archivés */
CREATE OR REPLACE VIEW QuizArchiveView AS SELECT id, login_creator, open, close, difficulty, points, type, title, question FROM Quiz WHERE state = 'archive' ORDER BY close DESC, open DESC;

/* Vue pour obtenir les quiz auxquels ont répondu les joueurs */
CREATE OR REPLACE VIEW QuizAnsweredView AS SELECT Quiz.id, login_creator, open, close, difficulty, points, type, title, question, PlayerQuizAnswered.login, success FROM Quiz, PlayerQuizAnswered WHERE Quiz.id = PlayerQuizAnswered.id ORDER BY open DESC, close DESC;

/* Echappement des réponses texte pour favoriser les joueurs */
DELIMITER //
CREATE OR REPLACE FUNCTION escape_response_text (
  response TYPE OF QuizResponses.response
) RETURNS VARCHAR(256)
BEGIN
  DECLARE res TYPE OF QuizResponses.response;
  -- Echappement des apostrophes par apostrophe française
  SELECT REGEXP_REPLACE(response, '\'|‘|’|ʼ', '’') INTO res;
  -- Les suites d'espaces sont réduits à un seul espace
  SELECT REGEXP_REPLACE(res, '[ ]+', ' ') INTO res;
  RETURN res;
END; //
DELIMITER ;

/* Ajouter un choix de réponse possible à un quiz */
DELIMITER //
CREATE OR REPLACE PROCEDURE add_response (
  quiz_id TYPE OF QuizResponses.id,
  new_response TYPE OF QuizResponses.response,
  is_valid TYPE OF QuizResponses.valid
)
BEGIN
  DECLARE quiz_type TYPE OF Quiz.type;
  SELECT type INTO quiz_type FROM Quiz WHERE id = quiz_id;
  IF quiz_type = 'text_weak' THEN
    SELECT escape_response_text(new_response) INTO new_response;
  END IF; 
  INSERT INTO QuizResponses VALUES (quiz_id, new_response, is_valid);
END; //
DELIMITER ;

/* Vue pour les réponses des quiz en stock */
CREATE OR REPLACE VIEW QuizResponsesStockView AS 
SELECT QuizResponses.*, login_creator FROM QuizResponses, Quiz 
WHERE Quiz.state = 'stock' AND Quiz.id = QuizResponses.id;

/* Vue pour les réponses des quiz jouables */
CREATE OR REPLACE VIEW QuizResponsesCurrentView AS 
SELECT QuizResponses.*, login_creator FROM QuizResponses, Quiz 
WHERE Quiz.state = 'current' AND Quiz.id = QuizResponses.id;

/* Vue pour les réponses des quiz archivés */
CREATE OR REPLACE VIEW QuizResponsesArchiveView AS 
SELECT QuizResponses.*, login_creator FROM QuizResponses, Quiz 
WHERE Quiz.state = 'archive' AND Quiz.id = QuizResponses.id;

/* Vue pour les réponses des joueurs */
CREATE OR REPLACE VIEW QuizResponsesAnsweredView AS
(SELECT id, response, 1 AS valid, login FROM PlayerQuizResponses) 
UNION 
(SELECT a.* FROM (
  (SELECT QuizResponses.id, response, 0, login FROM QuizResponses, PlayerQuizAnswered, Quiz 
  WHERE QuizResponses.id = PlayerQuizAnswered.id AND PlayerQuizAnswered.id = Quiz.id AND Quiz.type NOT LIKE 'text%') 
  EXCEPT (SELECT id, response, 0, login FROM PlayerQuizResponses)) AS a)

/* Répondre à un quiz */
CREATE OR REPLACE PROCEDURE answer_quiz (
  login_player TYPE OF PlayerQuizResponses.login,
  quiz_id TYPE OF PlayerQuizResponses.id,
  response TYPE OF PlayerQuizResponses.response
)
INSERT INTO PlayerQuizResponses (login, id, response) VALUES (login_player, quiz_id, response);

/**
 * Procédure à appeler après avoir répondu à un quiz
 * Notamment après avoir envoyé toutes les réponses d'un checkbox
 * Le score, et les statistiques du joueur sont alors mis à jour
 * @return Nombre de points ajoutés au score du joueur
 **/
DELIMITER //
CREATE OR REPLACE FUNCTION check_answer (
  login_player TYPE OF PlayerQuizResponses.login,
  quiz_id TYPE OF PlayerQuizResponses.id
) RETURNS INT
BEGIN
  DECLARE quiz_type TYPE OF Quiz.type;
  DECLARE quiz_points TYPE OF Quiz.points;
  DECLARE success_quiz TYPE OF PlayerQuizAnswered.success;
  DECLARE res INT;
  SET res = 0;
  
  SELECT type, points INTO quiz_type, quiz_points
  FROM Quiz WHERE id = quiz_id;
  SELECT success INTO success_quiz FROM PlayerQuizAnswered
  WHERE login = login_player AND id = quiz_id;
  
  /* @implement: branchement conditionnel car évaluation paresseuse 
  d'une expression booléenne boguée sous MariaDB */
  
  /* Vérification que le joueur n'a pas déjà répondu à ce quiz */ 
  IF success_quiz IS NULL THEN
  
    /* Vérification que les réponses sont correctes selon type de quiz */ 
    IF quiz_type = 'checkbox_and' THEN
      /* Égalité entre 2 tables E_1 = E_2 <=> (E_1 Union E_2) - (E_2 Inter E_1) = Vide */
      SELECT NOT EXISTS (
        SELECT a.response FROM
          ((SELECT response FROM QuizResponses WHERE id = quiz_id AND valid)
          UNION (SELECT response FROM PlayerQuizResponses WHERE login = login_player AND id = quiz_id)) AS a
        EXCEPT
        SELECT b.response FROM
          ((SELECT response FROM PlayerQuizResponses WHERE login = login_player AND id = quiz_id)
          INTERSECT (SELECT response FROM QuizResponses WHERE id = quiz_id AND valid)) AS b
      ) INTO success_quiz;
      
    ELSEIF quiz_type = 'checkbox_or' THEN
      /* Au moins une réponse et toutes les réponses doivent être justes */
      SELECT (SELECT COUNT(*) > 0 AND BIT_AND(valid)
      FROM (
        SELECT pqr.response, qr.valid
        FROM PlayerQuizResponses AS pqr, QuizResponses AS qr
        WHERE pqr.login = login_player AND pqr.id = quiz_id AND pqr.id = qr.id AND pqr.response = qr.response
      ) AS pqrv) INTO success_quiz;
      
    ELSEIF quiz_type = 'radio' THEN
      SELECT (SELECT response FROM QuizResponses WHERE id = quiz_id AND valid) 
      = (SELECT response FROM PlayerQuizResponses WHERE login = login_player AND id = quiz_id) INTO success_quiz;
      
    /* La comparaison pour un quiz text est insensible à la casse */
    ELSEIF quiz_type = 'text_strong' THEN
      /* Vérification forte en ignorant les erreurs aux extrémités */
      SELECT (SELECT response FROM PlayerQuizResponses WHERE login = login_player AND id = quiz_id)
      LIKE (SELECT CONCAT('%', response, '%') FROM QuizResponses WHERE id = quiz_id AND valid) INTO success_quiz;
      
    ELSEIF quiz_type = 'text_weak' THEN
      /* Vérification faible en tolérant les espaces et les apostrophes erronés */
      SELECT (SELECT escape_response_text(response) FROM PlayerQuizResponses WHERE login = login_player AND id = quiz_id)
      LIKE (SELECT CONCAT('%', response, '%') FROM QuizResponses WHERE id = quiz_id AND valid) INTO success_quiz;
      
    ELSEIF quiz_type = 'text_regex' THEN
      /* Vérification selon expression régulière */
      SELECT (SELECT response FROM PlayerQuizResponses WHERE login = login_player AND id = quiz_id)
      REGEXP (SELECT CONCAT('.*', response, '.*') FROM QuizResponses WHERE id = quiz_id AND valid) INTO success_quiz;
    END IF;
    
    /* Le quiz est réussi */
    IF success_quiz THEN
      -- Marquage du quiz comme réussi
      INSERT INTO PlayerQuizAnswered (login, id, success) VALUES
      (login_player, quiz_id, TRUE);
      -- Mise à jour du nombre de points du joueur
      /* En cas d'ajout ou modification des règles de calcul des points
      modifier high_score_quiz_title dans php/login_manager.php */
      IF quiz_type = 'checkbox_or' THEN
        SET res = ROUND(
          (quiz_points * (SELECT COUNT(response) FROM PlayerQuizResponses WHERE login = login_player AND id = quiz_id))
          / (SELECT COUNT(response) FROM QuizResponses WHERE id = quiz_id AND valid)
        );
      ELSE
        SET res = quiz_points;
      END IF;
      UPDATE Users SET points = points + res 
      WHERE login = login_player;
      -- Mise à jour du compteur de quiz réussis
      UPDATE Users SET success = success + 1 
      WHERE login = login_player;
      
    /* Le quiz est en échec */
    ELSE
      -- Marquage du quiz comme échoué 
      INSERT INTO PlayerQuizAnswered (login, id, success) VALUES
      (login_player, quiz_id, FALSE);
      -- Mise à jour du compteur de quiz échoués
      UPDATE Users SET fail = fail + 1 
      WHERE login = login_player;
    END IF;
  END IF;
  
  RETURN res;
END; //
DELIMITER ;

/* Rendre le quiz jouable */
CREATE OR REPLACE PROCEDURE open_quiz (
  quiz_id TYPE OF Quiz.id
)
UPDATE Quiz SET state = 'current' WHERE id = quiz_id;

/* Archiver le quiz */
CREATE OR REPLACE PROCEDURE close_quiz (
  quiz_id TYPE OF Quiz.id
)
UPDATE Quiz SET state = 'archive' WHERE id = quiz_id;

/* Mettre en stock un quiz en jeu ou archivé */
DELIMITER //
CREATE OR REPLACE PROCEDURE stock_quiz (
  login TYPE OF Quiz.login_creator,
  quiz_id TYPE OF Quiz.id,
  open_date TYPE OF Quiz.open,
  close_date TYPE OF Quiz.close
)
BEGIN
  IF NOT EXISTS(SELECT * FROM Quiz WHERE login_creator = login AND id = quiz_id)
  THEN
    SIGNAL SQLSTATE '45020' 
    SET MESSAGE_TEXT = 'Invalid login creator or id for this quiz';
  END IF;
  IF (SELECT state FROM Quiz WHERE id = quiz_id) = 'stock' 
  THEN
    SIGNAL SQLSTATE '45021' 
    SET MESSAGE_TEXT = 'Quiz is already in stock';
  END IF;
  START TRANSACTION;
  -- Suppression des réponses données par les joueurs
  DELETE FROM PlayerQuizResponses WHERE id = quiz_id;
  DELETE FROM PlayerQuizAnswered WHERE id = quiz_id;
  UPDATE Quiz SET open = open_date, close = close_date, state = 'stock' WHERE id = quiz_id;
  COMMIT;
END; //
DELIMITER ;

/* Traitement automatique des quiz en fonction de leurs dates */
DELIMITER //
CREATE OR REPLACE FUNCTION cron_routine () RETURNS JSON
BEGIN
  DECLARE res ROW (stock INT, close INT);
  DECLARE res_json JSON;
  DECLARE end_cursor BOOLEAN DEFAULT FALSE;
  DECLARE quiz_row ROW TYPE OF Quiz; 
  DECLARE quiz_stock_cursor CURSOR FOR
  SELECT * FROM Quiz WHERE state = 'stock';
  DECLARE quiz_current_cursor CURSOR FOR
  SELECT * FROM Quiz WHERE state = 'current'; 
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET end_cursor = TRUE;
  
  SET res.stock = 0; SET res.close = 0;
  
  /* Traitement quiz en stock */
  OPEN quiz_stock_cursor;
  FETCH quiz_stock_cursor INTO quiz_row;
  WHILE NOT end_cursor DO
    IF quiz_row.open <= CURRENT_DATE THEN
      CALL open_quiz(quiz_row.id);
      SET res.stock = res.stock + 1;
    END IF;
    FETCH quiz_stock_cursor INTO quiz_row;
  END WHILE;
  CLOSE quiz_stock_cursor;
  
  SET end_cursor = FALSE; 
  /* Traitement quiz jouables */
  OPEN quiz_current_cursor;
  FETCH quiz_current_cursor INTO quiz_row;
  WHILE NOT end_cursor DO
    IF quiz_row.close < CURRENT_DATE THEN
      CALL close_quiz(quiz_row.id);
      SET res.close = res.close + 1;
    END IF;
    FETCH quiz_current_cursor INTO quiz_row;
  END WHILE;
  CLOSE quiz_current_cursor;
  
  SELECT CONCAT('{"stock":', res.stock, ',"close":', res.close, '}') INTO res_json;
  RETURN res_json;
END; //
DELIMITER ;