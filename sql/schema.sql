/**
 * Données utilisateur principales
 * (login, password) le mot de passe est chiffré avec MD5
 * Le role influence les actions réalisables par l'utilisateur
 * Les autres informations concernent les statistiques
 **/
CREATE TABLE Users (
  login VARCHAR(16) PRIMARY KEY,
  password CHAR(64) NOT NULL,
  salt CHAR(16) NOT NULL CHECK(LENGTH(salt) = 16),
  role ENUM("player", "admin") NOT NULL DEFAULT "player",
  points INT NOT NULL DEFAULT 0,
  success INT NOT NULL DEFAULT 0, -- Nombre de quiz réussis
  fail INT NOT NULL DEFAULT 0 -- Nombre de quiz échoués
);

CREATE SEQUENCE QuizSequence START WITH 1 INCREMENT BY 1 CACHE 0;

/**
 * Informations principales d'un quiz
 * Pour la plupart des métadonnées
 * L'état du quiz signifie s'il est jouable ou pas
 * stock: le quiz est en attente d'être joué tant que la date d'ouverture
 * n'est pas atteinte
 * current: le quiz est jouable jusqu'à son expiration
 * archive: le quiz n'est plus jouable définitivement
 **/
CREATE TABLE Quiz (
  id INT PRIMARY KEY,
  login_creator VARCHAR(16), -- Utilisateur ayant créé le quiz
  FOREIGN KEY (login_creator) REFERENCES Users(login),
  open DATE NOT NULL, -- Date à laquelle pour être ouvert le quiz
  close DATE NOT NULL, -- Date à laquelle le quiz expire
  CHECK(open < close),
  difficulty TINYINT NOT NULL CHECK(1 <= difficulty AND difficulty <= 10),
  points TINYINT NOT NULL CHECK(0 <= points AND points <= 10),
  type ENUM("checkbox", "radio", "text") NOT NULL, -- Type de réponse du quiz
  state ENUM("creation", "stock", "current", "archive") NOT NULL DEFAULT "creation",
  title VARCHAR(256) NOT NULL, -- Peut être NULL
  question TEXT NOT NULL -- Champ principal du quiz
);

DELIMITER //
CREATE TRIGGER QuizUpdateTrigger
  BEFORE UPDATE ON Quiz
  FOR EACH ROW
BEGIN
  /* Il faut respecter l'ordre des changements d'état du quiz */
  IF (OLD.state = "creation" AND NEW.state != "stock")
    OR (OLD.state = "stock" AND NEW.state != "current")
    OR (OLD.state = "current" AND NEW.state != "archive") 
  THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Order of state must be creation -> stock -> current -> archive";
  
  /* Il doit y avoir au moins une réponse valide */
  ELSEIF NEW.state = "stock" 
  AND NOT EXISTS(SELECT * FROM QuizResponses WHERE id = NEW.id AND valid = TRUE) 
  THEN
    -- Annulation de la création de quiz
    SIGNAL SQLSTATE "45001" 
    SET MESSAGE_TEXT = "Before setting quiz to stock you have to add at least one valid response";
  END IF;
END; //
DELIMITER ;

DELIMITER //
CREATE TRIGGER QuizDeleteTrigger
  BEFORE DELETE ON Quiz
  FOR EACH ROW
BEGIN
  DECLARE quiz_state TYPE OF Quiz.state;
  SELECT state INTO quiz_state FROM Quiz
  WHERE id = OLD.id; 
  IF NOT (quiz_state = "creation" OR quiz_state = "stock") THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "You can delete only quiz in creation or in stock";
  ELSE
    DELETE FROM QuizResponses WHERE id = OLD.id;
    DELETE FROM PlayerQuizResponses WHERE id = OLD.id;
  END IF;
END; //
DELIMITER ;

/**
 * Choix de réponses possibles pour les quiz
 * Si la réponse est valide alors valid = TRUE
 * Si type = checkbox il peut y avoir plusieurs réponses valid = TRUE
 * Si type = radio il peut y avoir plusieurs choix de réponse mais une seule
 * avec valid = TRUE
 * Si type = text il n'y a qu'une seule réponse
 **/
CREATE TABLE QuizResponses (
  id INT, FOREIGN KEY (id) REFERENCES Quiz(id),
  response VARCHAR(256) NOT NULL,
  valid BOOLEAN NOT NULL,
  PRIMARY KEY(id, response)
);

DELIMITER //
CREATE TRIGGER QuizResponsesTrigger
  BEFORE INSERT ON QuizResponses
  FOR EACH ROW
BEGIN
  DECLARE quiz_type TYPE OF Quiz.type;
  DECLARE quiz_state TYPE OF Quiz.state;
  
  SELECT type INTO quiz_type FROM Quiz WHERE id = NEW.id;
  SELECT state INTO quiz_state FROM Quiz WHERE id = NEW.id;
  
  /* Contraintes sur l'état de quiz */
  IF quiz_state = "stock" OR quiz_state = "current" OR quiz_state = "archive" THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Cannot add reponses to quiz with state = stock or current or archive";
  END IF;
  
  /* Contraintes sur les types de quiz */
  IF quiz_type = "radio" AND NEW.valid = TRUE
  AND EXISTS(SELECT * FROM QuizResponses WHERE id = NEW.id AND valid = TRUE) 
  THEN
    -- Annulation de la création de quiz
    SIGNAL SQLSTATE "45001" 
    SET MESSAGE_TEXT = "Radio quiz must have only one valid response";
  ELSEIF quiz_type = "text" THEN
    IF EXISTS(SELECT * FROM QuizResponses WHERE id = NEW.id) 
    THEN
      -- Annulation de la création de quiz
      SIGNAL SQLSTATE "45001" 
      SET MESSAGE_TEXT = "Text quiz must have only one response";
    END IF;
    IF NEW.valid = FALSE THEN
      -- Annulation de la création de quiz
      SIGNAL SQLSTATE "45001" 
      SET MESSAGE_TEXT = "Text quiz must have always valid = TRUE";
    END IF;
  END IF;
END; //
DELIMITER ;

/**
 * Les quiz auxquels ont répondu ou pas les joueurs
 * et le résultat après correction
 **/
CREATE TABLE PlayerQuizAnswered (
  login VARCHAR(16),
  FOREIGN KEY (login) REFERENCES Users(login),
  id INT, FOREIGN KEY (id) REFERENCES Quiz(id),
  /* TRUE si quiz réussi FALSE si quiz échoué
  Si quiz pas répondu alors la ligne n'existe pas */
  success BOOLEAN NOT NULL,
  PRIMARY KEY(login, id)
);

DELIMITER //
CREATE TRIGGER PlayerQuizAnsweredTrigger
  BEFORE INSERT ON PlayerQuizAnswered
  FOR EACH ROW
BEGIN
  /* Un utilisateur ne peut pas répondre à un quiz qu'il a créé */
  IF NEW.login = (SELECT login_creator FROM Quiz WHERE id = NEW.id) THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "User cannot answers quiz because he created it";
  END IF;
END; //
DELIMITER ;

/**
 * Les réponses choisies par les joueurs
 * pour les quiz auxquels ils ont répondu
 **/
CREATE TABLE PlayerQuizResponses (
  login VARCHAR(16),
  FOREIGN KEY (login) REFERENCES Users(login),
  id INT, FOREIGN KEY (id) REFERENCES Quiz(id),
  response VARCHAR(256) NOT NULL,
  PRIMARY KEY(login, id, response)
); 

DELIMITER //
CREATE TRIGGER PlayerQuizResponsesTrigger
  BEFORE INSERT ON PlayerQuizResponses
  FOR EACH ROW
BEGIN
  DECLARE quiz_type TYPE OF Quiz.type;
  DECLARE success_quiz BOOLEAN;
  
  /* Si le joueur a déjà répondu il ne peut plus répondre à ce quiz */
  SELECT success INTO success_quiz FROM PlayerQuizAnswered
  WHERE login = NEW.login AND id = NEW.id; 
  IF success_quiz IS NOT NULL THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Player has already answered this quiz";
  END IF;
  
  /* On ne peut pas répondre à un quiz qui n'est pas jouable */
  IF (SELECT state FROM Quiz WHERE id = NEW.id) != "current" 
  THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Quiz isn't in current state";
  END IF;
  
  SELECT type INTO quiz_type FROM Quiz WHERE id = NEW.id;
  /* Une réponse doit correspondre à l'id du quiz */
  IF quiz_type != "text" AND NEW.response NOT IN (
    SELECT response FROM QuizResponses 
    WHERE QuizResponses.id = NEW.id) 
  THEN
    -- Annulation de la réponse du joueur
    SIGNAL SQLSTATE "45001" 
    SET MESSAGE_TEXT = "Response doesn't match quiz id";
  END IF;
  
  /* Un utilisateur ne peut pas répondre à un quiz qu'il a créé */
  IF NEW.login = (SELECT login_creator FROM Quiz WHERE id = NEW.id) THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "User cannot answers quiz because he created it";
  END IF;
  
  /* Un joueur ne peut donner qu'une seule réponse pour un quiz de type radio ou text */
  IF (quiz_type = "radio" OR quiz_type = "text") 
  AND EXISTS(SELECT * FROM PlayerQuizResponses 
  WHERE login = NEW.login AND id = NEW.id) 
  THEN
    -- Annulation de la réponse du joueur
    SIGNAL SQLSTATE "45001" 
    SET MESSAGE_TEXT = "Player must give only one answer for radio or text quiz";
  END IF;
END; //
DELIMITER ;