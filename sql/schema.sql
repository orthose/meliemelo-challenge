/**
 * Données utilisateur principales
 * (login, password) le mot de passe est chiffré avec SHA2
 * Le role influence les actions réalisables par l'utilisateur
 * Les autres informations concernent les statistiques
 **/
CREATE TABLE Users (
  login VARCHAR(16) PRIMARY KEY COLLATE 'latin1_general_cs', -- Sensible à la casse
  password CHAR(64) NOT NULL,
  salt CHAR(16) NOT NULL CHECK(LENGTH(salt) = 16),
  role ENUM('player', 'admin') NOT NULL DEFAULT 'player',
  points INT NOT NULL DEFAULT 0,
  success INT NOT NULL DEFAULT 0, -- Nombre de quiz réussis
  fail INT NOT NULL DEFAULT 0, -- Nombre de quiz échoués
  registration DATE NOT NULL DEFAULT CURRENT_DATE, -- Date de création du compte
  connection TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Dernière connexion
  visits INT NOT NULL DEFAULT 0
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
  id BIGINT UNSIGNED PRIMARY KEY,
  login_creator VARCHAR(16) COLLATE 'latin1_general_cs', -- Utilisateur ayant créé le quiz
  FOREIGN KEY (login_creator) REFERENCES Users(login),
  open DATE NOT NULL, -- Date à laquelle pour être ouvert le quiz
  close DATE NOT NULL, -- Date à laquelle le quiz expire
  creation DATE NOT NULL DEFAULT CURRENT_DATE, -- Date de création
  CHECK(creation <= open), CHECK(open <= close),
  difficulty TINYINT NOT NULL CHECK(1 <= difficulty AND difficulty <= 10),
  points TINYINT NOT NULL CHECK(0 <= points AND points <= 10),
  -- Type de réponse du quiz
  type ENUM('checkbox_and', 'checkbox_or', 'radio', 'text_strong', 'text_weak', 'text_regex') NOT NULL,
  state ENUM('stock', 'current', 'archive') NOT NULL DEFAULT 'stock',
  title VARCHAR(256) NOT NULL, -- Peut être NULL
  question TEXT NOT NULL -- Champ principal du quiz
);

/**
 * Choix de réponses possibles pour les quiz
 * Si la réponse est valide alors valid = TRUE
 * Si type = checkbox il peut y avoir plusieurs réponses valid = TRUE
 * Si type = radio il peut y avoir plusieurs choix de réponse mais une seule
 * avec valid = TRUE
 * Si type = text il n'y a qu'une seule réponse
 **/
CREATE TABLE QuizResponses (
  id BIGINT UNSIGNED, FOREIGN KEY (id) REFERENCES Quiz(id),
  response VARCHAR(256) NOT NULL,
  valid BOOLEAN NOT NULL,
  PRIMARY KEY(id, response)
);

/**
 * Les quiz auxquels ont répondu les joueurs
 * et le résultat après correction
 **/
CREATE TABLE PlayerQuizAnswered (
  login VARCHAR(16) COLLATE 'latin1_general_cs',
  FOREIGN KEY (login) REFERENCES Users(login),
  id BIGINT UNSIGNED, FOREIGN KEY (id) REFERENCES Quiz(id),
  /* TRUE si quiz réussi FALSE si quiz échoué
  Si quiz pas répondu alors la ligne n'existe pas */
  success BOOLEAN NOT NULL,
  -- Date de réponse au quiz
  answered TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(login, id)
);

DELIMITER //
CREATE TRIGGER PlayerQuizAnsweredTrigger
  BEFORE INSERT ON PlayerQuizAnswered
  FOR EACH ROW
BEGIN
  /* Un utilisateur ne peut pas répondre à un quiz qu'il a créé */
  IF NEW.login = (SELECT login_creator FROM Quiz WHERE id = NEW.id) THEN
    SIGNAL SQLSTATE '45001' 
    SET MESSAGE_TEXT = 'User cannot answers quiz because he created it';
  END IF;
END; //
DELIMITER ;

/**
 * Les réponses choisies par les joueurs
 * pour les quiz auxquels ils ont répondu
 **/
CREATE TABLE PlayerQuizResponses (
  login VARCHAR(16) COLLATE 'latin1_general_cs',
  FOREIGN KEY (login) REFERENCES Users(login),
  id BIGINT UNSIGNED, FOREIGN KEY (id) REFERENCES Quiz(id),
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
    SIGNAL SQLSTATE '45002' 
    SET MESSAGE_TEXT = 'Player has already answered this quiz';
  END IF;
  
  /* On ne peut pas répondre à un quiz qui n'est pas jouable */
  IF (SELECT state FROM Quiz WHERE id = NEW.id) != 'current' 
  THEN
    SIGNAL SQLSTATE '45003' 
    SET MESSAGE_TEXT = 'Quiz isn\'t in current state';
  END IF;
  
  SELECT type INTO quiz_type FROM Quiz WHERE id = NEW.id;
  /* Une réponse doit correspondre à l'id du quiz */
  IF quiz_type NOT LIKE 'text%' AND NEW.response NOT IN (
    SELECT response FROM QuizResponses 
    WHERE QuizResponses.id = NEW.id) 
  THEN
    SIGNAL SQLSTATE '45004' 
    SET MESSAGE_TEXT = 'Response doesn\'t match quiz id';
  END IF;
  
  /* Un utilisateur ne peut pas répondre à un quiz qu'il a créé */
  IF NEW.login = (SELECT login_creator FROM Quiz WHERE id = NEW.id) THEN
    SIGNAL SQLSTATE '45005' 
    SET MESSAGE_TEXT = 'User cannot answers quiz because he created it';
  END IF;
  
  /* Un joueur ne peut donner qu'une seule réponse pour un quiz de type radio ou text */
  IF (quiz_type = 'radio' OR quiz_type LIKE 'text%') 
  AND EXISTS(SELECT * FROM PlayerQuizResponses 
  WHERE login = NEW.login AND id = NEW.id) 
  THEN
    SIGNAL SQLSTATE '45006' 
    SET MESSAGE_TEXT = 'Player must give only one answer for radio or text quiz';
  END IF;
END; //
DELIMITER ;