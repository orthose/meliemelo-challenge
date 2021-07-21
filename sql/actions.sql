/* Enregistrer un nouvel utilisateur */
DELIMITER //
CREATE PROCEDURE register_new_user (
  new_login TYPE OF Users.login, 
  new_passwd VARCHAR(2048)
)
BEGIN
  DECLARE random_salt TYPE OF Users.salt;
  DECLARE hash_passwd TYPE OF Users.password;
  IF LENGTH(new_passwd) < 8 THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Password must have length >= 8" ;
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
CREATE FUNCTION get_role (
  login_user TYPE OF Users.login
) RETURNS ENUM("player", "admin")
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
CREATE FUNCTION authentication_is_valid (
  login_user TYPE OF Users.login,
  passwd_user VARCHAR(2048)
) RETURNS BOOLEAN
BEGIN
  DECLARE res BOOLEAN;
  SELECT password = SHA2(CONCAT(passwd_user, salt), 256) FROM Users 
  WHERE login = login_user INTO res;
  -- Si res = NULL alors le login n'existe pas
  RETURN res IS NOT NULL AND res;
END; //
DELIMITER ;

/** 
 * Vérifie que le login existe déjà
 * @return TRUE si le login existe déjà 
 **/
DELIMITER //
CREATE FUNCTION login_exists (
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
CREATE PROCEDURE set_role (
  login_user TYPE OF Users.login,
  new_role TYPE OF Users.role
)
UPDATE Users SET role = new_role 
WHERE login = login_user;

/* Changer le mot de passe */
DELIMITER //
CREATE OR REPLACE PROCEDURE set_password (
  login_user TYPE OF Users.login,
  actual_passwd VARCHAR(2048),
  new_passwd VARCHAR(2048)
)
BEGIN
  IF LENGTH(new_passwd) < 8 THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Password must have length >= 8";
  ELSEIF NOT authentication_is_valid(login_user, actual_passwd) THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Authentication has failed";
  ELSE
    UPDATE Users SET password = SHA2(CONCAT(new_passwd, salt), 256) 
    WHERE login = login_user;
  END IF;
END; //
DELIMITER ;

/* Supprimer un utilisateur */
DELIMITER //
CREATE PROCEDURE unregister_user (
  login_user TYPE OF Users.login,
  passwd VARCHAR(2048)
)
BEGIN
  IF NOT authentication_is_valid(login_user, passwd) THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Authentication has failed";
  ELSE
    DELETE FROM Users WHERE login = login_user;
  END IF;
END; //
DELIMITER ;

/* Vue pour la table des scores */
CREATE VIEW HighScoreView AS
SELECT login, points, success, fail FROM Users
ORDER BY points DESC;

/* Créer un nouveau quiz */
DELIMITER //
CREATE FUNCTION create_quiz (
  login TYPE OF Quiz.login_creator,
  open TYPE OF Quiz.open,
  close TYPE OF Quiz.close,
  difficulty TYPE OF Quiz.difficulty,
  points TYPE OF Quiz.points,
  type TYPE OF Quiz.type,
  title TYPE OF Quiz.title,
  question TYPE OF Quiz.question
) RETURNS INT
BEGIN
  DECLARE quiz_id INT;
  SELECT NEXTVAl(QuizSequence) INTO quiz_id;
  INSERT INTO Quiz (id, login_creator, open, close, difficulty, points, type, title, question)
  VALUES (quiz_id, login, open, close, difficulty, points, type, title, question);
  RETURN quiz_id;
END; //
DELIMITER ;

/* Supprimer un quiz existant en stock */
DELIMITER //
CREATE PROCEDURE remove_quiz (
  login TYPE OF Quiz.login_creator,
  quiz_id TYPE OF Quiz.id
)
BEGIN
  IF NOT EXISTS(SELECT * FROM Quiz WHERE login_creator = login AND id = quiz_id) 
  THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Invalid login creator or id for this quiz";
  ELSE
    DELETE FROM Quiz WHERE login_creator = login AND id = quiz_id;
  END IF;
END; //
DELIMITER ;

/* Vue pour obtenir les quiz en stock */
CREATE VIEW QuizStockView AS SELECT * FROM Quiz WHERE state = "stock";

/* Vue pour obtenir les quiz courants */
CREATE VIEW QuizCurrentView AS SELECT * FROM Quiz WHERE state = "current";

/* Vue pour obtenir les quiz archivés */
CREATE VIEW QuizArchiveView AS SELECT * FROM Quiz WHERE state = "archive";

/* Ajouter un choix de réponse possible à un quiz */
CREATE PROCEDURE add_response (
  quiz_id TYPE OF QuizResponses.id,
  new_response TYPE OF QuizResponses.response,
  is_valid TYPE OF QuizResponses.valid
)
INSERT INTO QuizResponses VALUES (quiz_id, new_response, is_valid);

/* Vue pour les réponses des quiz en stock */
CREATE VIEW QuizResponsesStockView AS 
SELECT QuizResponses.* FROM QuizResponses, Quiz 
WHERE Quiz.state = "stock" AND Quiz.id = QuizResponses.id;

/* Vue pour les réponses des quiz jouables */
CREATE VIEW QuizResponsesCurrentView AS 
SELECT QuizResponses.* FROM QuizResponses, Quiz 
WHERE Quiz.state = "current" AND Quiz.id = QuizResponses.id;

/* Vue pour les réponses des quiz archivés */
CREATE VIEW QuizResponsesArchiveView AS 
SELECT QuizResponses.* FROM QuizResponses, Quiz 
WHERE Quiz.state = "archive" AND Quiz.id = QuizResponses.id;

/* Répondre à un quiz */
CREATE PROCEDURE answer_quiz (
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
CREATE FUNCTION check_answer (
  login_player TYPE OF PlayerQuizResponses.login,
  quiz_id TYPE OF PlayerQuizResponses.id
) RETURNS INT
BEGIN
  DECLARE quiz_type TYPE OF Quiz.type;
  DECLARE quiz_difficulty TYPE OF Quiz.difficulty;
  DECLARE quiz_points TYPE OF Quiz.points;
  DECLARE success_quiz TYPE OF PlayerQuizAnswered.success;
  DECLARE res INT;
  SET res = 0;
  
  SELECT type, difficulty, points INTO quiz_type, quiz_difficulty, quiz_points
  FROM Quiz WHERE id = quiz_id;
  SELECT success INTO success_quiz FROM PlayerQuizAnswered
  WHERE login = login_player AND id = quiz_id;
  
  /* Vérification que les réponses sont correctes 
  et que le joueur n'a pas déjà répondu à ce quiz */ 
  IF success_quiz IS NULL AND (
    (
      quiz_type = "checkbox" AND 
      -- Égalité entre 2 tables E_1 = E_2 <=> (E_1 Union E_2) - (E_2 Inter E_1) = Vide
      NOT EXISTS (
        SELECT a.response FROM
          ((SELECT response FROM QuizResponses WHERE id = quiz_id AND valid = TRUE)
          UNION (SELECT response FROM PlayerQuizResponses WHERE login = login_player AND id = quiz_id)) AS a
        EXCEPT
        SELECT b.response FROM
          ((SELECT response FROM PlayerQuizResponses WHERE login = login_player AND id = quiz_id)
          INTERSECT (SELECT response FROM QuizResponses WHERE id = quiz_id AND valid = TRUE)) AS b
      )
    ) OR
    (
      quiz_type = "radio" AND
      (SELECT response FROM QuizResponses WHERE id = quiz_id AND valid = TRUE) 
      = (SELECT response FROM PlayerQuizResponses WHERE login = login_player AND id = quiz_id)
    ) OR
    /* La comparaison pour un quiz text est insensible à la casse */
    (
      quiz_type = "text" AND 
      (SELECT response FROM PlayerQuizResponses WHERE login = login_player AND id = quiz_id)
      LIKE (SELECT CONCAT("%", response, "%") FROM QuizResponses WHERE id = quiz_id AND valid = TRUE)
    ))
  THEN
    -- Marquage du quiz comme réussi
    INSERT INTO PlayerQuizAnswered VALUES
    (login_player, quiz_id, TRUE);
    -- Mise à jour du nombre de points du joueur
    SET res = (quiz_difficulty * quiz_points);
    UPDATE Users SET points = points + res 
    WHERE login = login_player;
    -- Mise à jour du compteur de quiz réussis
    UPDATE Users SET success = success + 1 
    WHERE login = login_player;
  
  -- Le quiz est en échec
  ELSEIF success_quiz IS NULL THEN
  -- Marquage du quiz comme échoué 
    INSERT INTO PlayerQuizAnswered VALUES
    (login_player, quiz_id, FALSE);
    -- Mise à jour du compteur de quiz échoués
    UPDATE Users SET fail = fail + 1 
    WHERE login = login_player;
  END IF;
  RETURN res;
END; //
DELIMITER ;

/* Mettre le quiz en stock après avoir complété 
toutes les réponses possibles du quiz */
CREATE PROCEDURE stock_quiz (
  quiz_id TYPE OF Quiz.id
)
UPDATE Quiz SET state = "stock" WHERE id = quiz_id;

/* Rendre le quiz jouable */
CREATE PROCEDURE open_quiz (
  quiz_id TYPE OF Quiz.id
)
UPDATE Quiz SET state = "current" WHERE id = quiz_id;

/* Archiver le quiz */
CREATE PROCEDURE close_quiz (
  quiz_id TYPE OF Quiz.id
)
UPDATE Quiz SET state = "archive" WHERE id = quiz_id;

/* Traitement automatique des quiz en fonction de leurs dates */
DELIMITER //
CREATE PROCEDURE cron_routine ()
BEGIN
  DECLARE end_cursor BOOLEAN DEFAULT FALSE;
  DECLARE quiz_row ROW TYPE OF Quiz; 
  DECLARE quiz_stock_cursor CURSOR FOR
  SELECT * FROM Quiz WHERE state = "stock";
  DECLARE quiz_current_cursor CURSOR FOR
  SELECT * FROM Quiz WHERE state = "current"; 
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET end_cursor = TRUE;
  
  /* Traitement quiz en stock */
  OPEN quiz_stock_cursor;
  FETCH quiz_stock_cursor INTO quiz_row;
  WHILE NOT end_cursor DO
    IF quiz_row.open <= CURRENT_DATE THEN
      CALL open_quiz(quiz_row.id);
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
    END IF;
    FETCH quiz_current_cursor INTO quiz_row;
  END WHILE;
  CLOSE quiz_current_cursor;
END; //
DELIMITER ;