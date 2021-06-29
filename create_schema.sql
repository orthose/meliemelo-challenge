/**
 * Données utilisateur principales
 * (login, password) le mot de passe est chiffré avec MD5
 * Le role influence les actions réalisables par l'utilisateur
 * Les autres informations concernent les statistiques
 **/
CREATE TABLE Users (
  login VARCHAR(16) PRIMARY KEY,
  password VARCHAR(64) NOT NULL CHECK(LENGTH(password) >= 8),
  salt CHAR(16) NOT NULL,
  role ENUM("player", "admin") NOT NULL DEFAULT "player",
  points INT NOT NULL DEFAULT 0,
  success INT NOT NULL DEFAULT 0, -- Nombre de quiz réussis
  fail INT NOT NULL DEFAULT 0 -- Nombre de quiz échoués
);

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
  id INT AUTO_INCREMENT PRIMARY KEY,
  login_creator VARCHAR(16), -- Utilisateur ayant créé le quiz
  FOREIGN KEY (login_creator) REFERENCES Users(login),
  open DATE NOT NULL, -- Date à laquelle pour être ouvert le quiz
  close DATE NOT NULL, -- Date à laquelle le quiz expire
  CHECK(open < close),
  difficulty TINYINT NOT NULL CHECK(1 <= difficulty AND difficulty <= 10),
  points TINYINT NOT NULL CHECK(0 <= points AND points <= 10),
  type ENUM("checkbox", "radio", "text") NOT NULL, -- Type de réponse du quiz
  state ENUM("stock", "current", "archive") NOT NULL DEFAULT "stock",
  title VARCHAR(256), -- Peut être NULL
  question TEXT NOT NULL -- Champ principal du quiz
);

DELIMITER //
CREATE TRIGGER QuizTrigger
  BEFORE UPDATE ON Quiz
  FOR EACH ROW
BEGIN
  DECLARE count_valid INT;
  /* Il doit y avoir au moins une réponse valide */
  SELECT COUNT(*) INTO count_valid FROM QuizResponses 
  WHERE id = NEW.id AND valid = TRUE;
  IF (NEW.state = "current" OR NEW.state = "archive")
  AND count_valid < 1 THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Before setting quiz to current or archive you have to add at least one valid response";
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
  DECLARE count_valid INT;
  DECLARE count_responses INT;
  
  SELECT type INTO quiz_type FROM Quiz WHERE id = NEW.id;
  SELECT state INTO quiz_state FROM Quiz WHERE id = NEW.id;
  
  -- Contraintes sur l'état de quiz
  IF quiz_state = "current" OR quiz_state = "archive" THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Cannot add reponses to quiz with state = current or archive";
  END IF;
  
  -- Contraintes sur les types de quiz
  IF quiz_type = "radio" AND NEW.valid = TRUE THEN
    SELECT COUNT(*) INTO count_valid FROM QuizResponses 
    WHERE id = NEW.id AND valid = TRUE;
    IF count_valid >= 1 THEN
      SIGNAL SQLSTATE "45000" 
      SET MESSAGE_TEXT = "Radio quiz must have only one valid response";
    END IF;
  ELSEIF quiz_type = "text" THEN
    SELECT COUNT(*) INTO count_responses FROM QuizResponses WHERE id = NEW.id;
    IF count_responses >= 1 THEN
      SIGNAL SQLSTATE "45000" 
      SET MESSAGE_TEXT = "Text quiz must have only one response";
    END IF;
  ELSEIF quiz_type = "text" AND NEW.valid = FALSE THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Text quiz must have always valid = TRUE";
  END IF;
END; //
DELIMITER ;

/**
 * Les quiz auxquels ont répondu les joueurs
 * et leur réponse(s) associée(s)
 **/
CREATE TABLE QuizAnswered (
  login VARCHAR(16),
  FOREIGN KEY (login) REFERENCES Users(login),
  id INT, FOREIGN KEY (id) REFERENCES Quiz(id),
  response VARCHAR(256) NOT NULL REFERENCES QuizResponses(response),
  PRIMARY KEY(login, id, response)
); 

DELIMITER //
CREATE TRIGGER QuizAnsweredTrigger
  BEFORE INSERT ON QuizAnswered
  FOR EACH ROW
BEGIN
  DECLARE quiz_type TYPE OF Quiz.type;
  DECLARE count_answered INT;
  
  /* Une réponse doit correspondre à l'id du quiz */
  IF NEW.response NOT IN (
    SELECT response FROM QuizResponses 
    WHERE QuizResponses.id = QuizAnswered.id) THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Response doesn't match quiz id";
  END IF;
  /* Un utilisateur ne peut pas répondre à un quiz qu'il a créé */
  IF NEW.login = (SELECT login_creator FROM Quiz WHERE id = NEW.id) THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "User cannot answers quiz because he created it";
  END IF;
  /* Un joueur ne peut donner qu'une seule réponse pour un quiz de type radio */
  /* Pour un quiz de type text QuizResponsesTrigger empêche la création de plusieurs réponses */
  SELECT type INTO quiz_type FROM Quiz WHERE id = NEW.id;
  SELECT COUNT(*) INTO count_answered FROM QuizAnswered WHERE id = NEW.id;
  IF quiz_type = "radio" AND count_answered >= 1 THEN
    SIGNAL SQLSTATE "45000" 
    SET MESSAGE_TEXT = "Player must give only one answer for radio quiz";
  END IF;
END; //
DELIMITER ;