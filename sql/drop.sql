/* Triggers */
DROP TRIGGER QuizUpdateTrigger;
DROP TRIGGER QuizDeleteTrigger;
DROP TRIGGER QuizResponsesTrigger;
DROP TRIGGER PlayerQuizResponsesTrigger;
DROP TRIGGER PlayerQuizAnsweredTrigger;

/* Tables */
DROP TABLE PlayerQuizResponses;
DROP TABLE PlayerQuizAnswered;
DROP TABLE QuizResponses;
DROP SEQUENCE QuizSequence;
DROP TABLE Quiz;
DROP TABLE Users;

/* Vues */
DROP VIEW HighScoreView;
DROP VIEW QuizStockView;
DROP VIEW QuizCurrentView;
DROP VIEW QuizArchiveView;
DROP VIEW QuizResponsesStockView;
DROP VIEW QuizResponsesCurrentView;
DROP VIEW QuizResponsesArchiveView;

/* Fonctions et Procédures */
DROP PROCEDURE register_new_user;
DROP FUNCTION get_role;
DROP FUNCTION authentication_is_valid;
DROP FUNCTION login_exists;
DROP PROCEDURE set_role;
DROP PROCEDURE set_password;
DROP PROCEDURE unregister_user;
DROP FUNCTION create_quiz;
DROP PROCEDURE remove_quiz;
DROP PROCEDURE add_response;
DROP PROCEDURE answer_quiz;
DROP FUNCTION check_answer;
DROP PROCEDURE stock_quiz;
DROP PROCEDURE open_quiz;
DROP PROCEDURE close_quiz;
DROP FUNCTION cron_routine;

/* Pour supprimer les utilisateurs */
-- DROP USER "meliemelo"@"localhost";
-- DROP USER "undefined_meliemelo"@"localhost";
-- DROP USER "player_meliemelo"@"localhost";
-- DROP USER "admin_meliemelo"@"localhost";

/* Pour supprimer la base de données */
-- DROP DATABASE meliemelo_challenge;