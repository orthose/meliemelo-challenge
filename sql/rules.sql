-- Utilisateur en ligne de commande
GRANT ALL ON meliemelo_challenge.* TO "meliemelo"@"localhost";

-- Utilisateurs non-définis pour le processus de connexion
-- REVOKE ALL ON meliemelo_challenge.* FROM "undefined_meliemelo"@"localhost";
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.register_new_user TO "undefined_meliemelo"@"localhost";
GRANT EXECUTE ON FUNCTION meliemelo_challenge.get_role TO "undefined_meliemelo"@"localhost";
GRANT EXECUTE ON FUNCTION meliemelo_challenge.authentication_is_valid TO "undefined_meliemelo"@"localhost";
GRANT EXECUTE ON FUNCTION meliemelo_challenge.login_exists TO "undefined_meliemelo"@"localhost";
GRANT EXECUTE ON FUNCTION meliemelo_challenge.cron_routine TO "undefined_meliemelo"@"localhost";

-- Utilisateurs avec role = player
-- REVOKE ALL ON meliemelo_challenge.* FROM "player_meliemelo"@"localhost";
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.set_password TO "player_meliemelo"@"localhost";
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.unregister_user TO "player_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.HighScoreView TO "player_meliemelo"@"localhost";
GRANT SELECT (id, title, difficulty, points, open, close) ON meliemelo_challenge.Quiz TO "player_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizCurrentView TO "player_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizArchiveView TO "player_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizAnsweredView TO "player_meliemelo"@"localhost";
GRANT SELECT (id, response) ON meliemelo_challenge.QuizResponsesCurrentView TO "player_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.PlayerQuizAnswered TO "player_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizResponsesArchiveView TO "player_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizResponsesAnsweredView TO "player_meliemelo"@"localhost";
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.answer_quiz TO "player_meliemelo"@"localhost";
GRANT EXECUTE ON FUNCTION meliemelo_challenge.check_answer TO "player_meliemelo"@"localhost";
GRANT SELECT, CREATE TEMPORARY TABLES ON meliemelo_challenge.* TO "player_meliemelo"@"localhost";

-- Utilisateurs avec role = admin
-- REVOKE ALL ON meliemelo_challenge.* FROM "admin_meliemelo"@"localhost";
-- Mêmes privilèges que les utilisateurs avec role = player
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.set_password TO "admin_meliemelo"@"localhost";
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.unregister_user TO "admin_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.HighScoreView TO "admin_meliemelo"@"localhost";
GRANT SELECT (id, title, difficulty, points, open, close) ON meliemelo_challenge.Quiz TO "admin_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizCurrentView TO "admin_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizArchiveView TO "admin_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizAnsweredView TO "admin_meliemelo"@"localhost";
-- On a besoin de (id, response, valid) pour quiz_current_not_playable
GRANT SELECT ON meliemelo_challenge.QuizResponsesCurrentView TO "admin_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.PlayerQuizAnswered TO "admin_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizResponsesArchiveView TO "admin_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizResponsesAnsweredView TO "admin_meliemelo"@"localhost";
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.answer_quiz TO "admin_meliemelo"@"localhost";
GRANT EXECUTE ON FUNCTION meliemelo_challenge.check_answer TO "admin_meliemelo"@"localhost";
-- Privilèges supplémentaires
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.set_role TO "admin_meliemelo"@"localhost";
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.reset_high_score TO "admin_meliemelo"@"localhost";
GRANT EXECUTE ON FUNCTION meliemelo_challenge.create_quiz TO "admin_meliemelo"@"localhost";
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.add_response TO "admin_meliemelo"@"localhost";
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.check_quiz TO "admin_meliemelo"@"localhost";
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.remove_quiz TO "admin_meliemelo"@"localhost";
GRANT EXECUTE ON PROCEDURE meliemelo_challenge.stock_quiz TO "admin_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizStockView TO "admin_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizResponsesStockView TO "admin_meliemelo"@"localhost";
GRANT SELECT ON meliemelo_challenge.QuizResponsesArchiveView TO "admin_meliemelo"@"localhost";
GRANT SELECT, CREATE TEMPORARY TABLES ON meliemelo_challenge.* TO "admin_meliemelo"@"localhost";


FLUSH PRIVILEGES;