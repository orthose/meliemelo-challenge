/* Tests sur la table Users */
CALL register_new_user("Maxime", "password"); -- Query OK
CALL register_new_user("Maxime", "aaaaaaaa"); -- ERROR 1062 (23000): Duplicate entry 'Maxime' for key 'PRIMARY' 
CALL register_new_user("Amélie", "password"); -- Query OK 
CALL register_new_user("Maéva", "drowssap"); -- Query OK 
CALL register_new_user("Lucie", "passwordpassword"); -- Query OK 
CALL register_new_user("Yann", "passwor"); -- ERROR 1644 (45000): Password must have length >= 8
CALL register_new_user("Yann", "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"); --  Query OK

SELECT authentication_is_valid("Maxime", "password"); -- 1 
SELECT authentication_is_valid("Maxim", "password"); -- 0 
SELECT authentication_is_valid("Maxime", "passwor"); -- 0 
SELECT authentication_is_valid("Maxim", "passwor"); -- 0 

SELECT login_exists("Maxime"); -- 1 
SELECT login_exists("Maxim"); -- 0 
SELECT login_exists("Gallou"); -- 0 

SELECT get_role("Maxime"); -- player
CALL set_role("Maxime", "admin"); -- Query OK
SELECT get_role("Maxime"); -- admin
CALL set_role("Maxime", "player"); -- Query OK
CALL set_role("Maxime", "player"); -- Query OK
SELECT get_role("Maxime"); -- player
CALL set_role("Maxime", "undefined"); -- ERROR 1265 (01000): Data truncated for column 'role' at row 1 
SELECT get_role("Maxime"); -- player

CALL set_password("Maxime", "password", "new_password"); -- Query OK et change le mot de passe de Maxime
CALL set_password("Maxime", "password", "new_password"); -- Query OK mais mot de passe pas changé
SELECT authentication_is_valid("Maxime", "new_password"); -- 1
SELECT authentication_is_valid("Maxime", "password"); -- 0

CALL unregister_user("Yann", "aaa"); -- Query OK mais ne fait rien
CALL unregister_user("Yann", "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"); -- Query OK et supprime Yann

/* Tests sur les tables de Quiz */
SELECT create_quiz("Maxime", "2021-07-01", "2021-07-31", 1, 1, "checkbox", "Un quiz très facile", "Qui est Emmanuel Macron ?"); -- Query OK 
SELECT create_quiz("Maxim", "2021-07-01", "2021-07-31", 1, 1, "checkbox", "Un quiz très facile", "Qui est Emmanuel Macron ?"); -- ERROR 1452 (23000): Cannot add or update a child row: a foreign key constraint fails (`meliemelo_challenge`.`Quiz`, CONSTRAINT `Quiz_ibfk_1` FOREIGN KEY (`login_creator`) REFERENCES `Users` (`login`)) 
SELECT create_quiz("Maxime", "2021-08-01", "2021-07-31", 1, 1, "checkbox", "Un quiz très facile", "Qui est Emmanuel Macron ?"); -- ERROR 4025 (23000): CONSTRAINT `CONSTRAINT_1` failed for `meliemelo_challenge`.`Quiz` 
SELECT create_quiz("Maxime", "2021-07-01", "2021-07-31", 0, 1, "checkbox", "Un quiz très facile", "Qui est Emmanuel Macron ?"); -- ERROR 4025 (23000): CONSTRAINT `Quiz.difficulty` failed for `meliemelo_challenge`.`Quiz` 
SELECT create_quiz("Maxime", "2021-07-01", "2021-07-31", 1, 0, "checkbox", "Un quiz très facile", "Qui est Emmanuel Macron ?"); -- Query OK 
SELECT create_quiz("Maxime", "2021-07-01", "2021-07-31", 10, 10, "checkbox", "Un quiz horrible", "Qui est Dieu ?"); -- Query OK 
SELECT create_quiz("Maxime", "2021-07-01", "2021-07-31", 11, 10, "checkbox", "Un quiz horrible", "Qui est Dieu ?"); -- ERROR 4025 (23000): CONSTRAINT `Quiz.difficulty` failed for `meliemelo_challenge`.`Quiz` 
SELECT create_quiz("Maxime", "2021-07-01", "2021-07-31", 10, 11, "checkbox", "Un quiz horrible", "Qui est Dieu ?"); -- ERROR 4025 (23000): CONSTRAINT `Quiz.points` failed for `meliemelo_challenge`.`Quiz` 
SELECT create_quiz("Maxime", "2021-07-01", "2021-07-31", 1, 2, "radio", "Un quiz gentil", "Sélectionnez le président actuel."); -- Query OK 
SELECT create_quiz("Maxime", "2021-07-01", "2021-07-31", 2, 5, "text", "Un quiz vicieux", "Quel est le nom de famille de Sarkozy ?"); -- Query OK 
SELECT create_quiz("Maxime", "2021-07-01", "2021-07-31", 2, 5, "check", "Un quiz vicieux", "Quel est le nom de famille de Sarkozy ?"); -- ERROR 1265 (01000): Data truncated for column 'type' at row 7 
SELECT create_quiz("Maxime", "2021-06-01", "2021-07-1", 2, 5, "radio", "Un quiz surprenant", "Qu'est-ce qu'une surprise ?"); -- Query OK 

CALL remove_quiz("Maxime", 3); -- ERROR 1644 (45000): You can delete only quiz in stock
CALL stock_quiz(3); -- ERROR 1644 (45000): Before setting quiz to stock you have to add at least one valid response
CALL add_response(3, "Je ne sais pas", FALSE); -- Query OK
CALL stock_quiz(3); -- ERROR 1644 (45000): Before setting quiz to stock you have to add at least one valid response
CALL add_response(3, "Un humain", TRUE); -- Query OK
CALL open_quiz(3); -- ERROR 1644 (45000): Order of state must be creation -> stock -> current -> archive
CALL close_quiz(3); -- ERROR 1644 (45000): Order of state must be creation -> stock -> current -> archive
CALL stock_quiz(3); -- Query OK
CALL close_quiz(3); -- ERROR 1644 (45000): Order of state must be creation -> stock -> current -> archive
CALL answer_quiz("Amélie", 3, "Un humain"); -- ERROR 1644 (45000): Quiz isn't in current state
CALL remove_quiz("Maxime", 3); -- Query OK et les réponses sont bien supprimées

CALL add_response(1, "Un sombre personnage", FALSE); -- Query OK 
CALL add_response(1, "L'idole des jeunes", FALSE); -- Query OK 
CALL add_response(1, "Le variant delta du COVID-19", FALSE); -- Query OK 
CALL stock_quiz(1); --  ERROR 1644 (45000): Before setting quiz to stock you have to add at least one valid response
CALL open_quiz(1); -- ERROR 1644 (45000): Order of state must be creation -> stock -> current -> archive 
CALL add_response(1, "Le président de la République", TRUE); -- Query OK 
CALL stock_quiz(1); -- Query OK
CALL open_quiz(1); -- Query OK

CALL answer_quiz("Maxime", 1, "Un sombre personnage"); -- ERROR 1644 (45000): User cannot answers quiz because he created it 
CALL answer_quiz("Gallou", 1, "Un sombre personnage"); -- ERROR 1452 (23000): Cannot add or update a child row: a foreign key constraint fails (`meliemelo_challenge`.`PlayerQuizResponses`, CONSTRAINT `PlayerQuizResponses_ibfk_1` FOREIGN KEY (`login`) REFERENCES `Users` (`login`)) 
CALL answer_quiz("Amélie", 1, "Un sombre"); -- ERROR 1644 (45000): Response doesn't match quiz id 
CALL answer_quiz("Amélie", 1, "Un sombre personnage"); -- Query OK
CALL answer_quiz("Amélie", 1, "Un sombre personnage"); -- ERROR 1062 (23000): Duplicate entry 'Amélie-1-Un sombre personnage' for key 'PRIMARY'
CALL answer_quiz("Maéva", 1, "Le président de la République"); -- Query OK

CALL check_answer("Yann", 1); -- ERROR 1452 (23000): Cannot add or update a child row: a foreign key constraint fails (`meliemelo_challenge`.`PlayerQuizAnswered`, CONSTRAINT `PlayerQuizAnswered_ibfk_1` FOREIGN KEY (`login`) REFERENCES `Users` (`login`))
CALL check_answer("Maxime", 1); -- Query OK, 2 rows affected (0.034 sec) incrémente fail de Users pour Maxime
CALL check_answer("Amélie", 3); -- ERROR 1452 (23000): Cannot add or update a child row: a foreign key constraint fails (`meliemelo_challenge`.`PlayerQuizAnswered`, CONSTRAINT `PlayerQuizAnswered_ibfk_2` FOREIGN KEY (`id`) REFERENCES `Quiz` (`id`))
CALL check_answer("Amélie", 1); -- Query OK, 3 rows affected (0.095 sec) incrémente fail de Users pour Amélie
CALL check_answer("Amélie", 1); -- Query OK, 2 rows affected (0.000 sec) mais ne modifie rien heureusement
CALL answer_quiz("Amélie", 1, "Le président de la République"); -- ERROR 1644 (45000): Player has already answered this quiz
CALL check_answer("Maéva", 1); -- Query OK, 4 rows affected (0.070 sec) Maéva obtient 1 point et 1 success

CALL add_response(4, "Personne ne le sait", TRUE); -- Query OK
CALL add_response(4, "Le créateur de l'univers", TRUE); -- Query OK
CALL add_response(4, "L'auteur de la Bible", FALSE); -- Query OK

CALL answer_quiz("Amélie", 4, "Un sombre personnage"); -- ERROR 1644 (45000): Quiz isn't in current state
CALL stock_quiz(4); -- Query OK
CALL open_quiz(4); -- Query OK
CALL answer_quiz("Amélie", 4, "Un sombre personnage"); -- ERROR 1644 (45000): Response doesn't match quiz id
CALL answer_quiz("Amélie", 4, "Personne ne le sait"); -- Query OK
CALL answer_quiz("Amélie", 4, "Le créateur de l'univers"); -- Query OK
CALL answer_quiz("Amélie", 4, "L'auteur de la Bible"); -- Query OK
CALL check_answer("Amélie", 4); -- Query OK, 3 rows affected (0.054 sec) Amélie se prend un fail
CALL answer_quiz("Maéva", 4, "Personne ne le sait"); -- Query OK
CALL answer_quiz("Maéva", 4, "Le créateur de l'univers"); -- Query OK
CALL check_answer("Maéva", 4); -- Query OK, 4 rows affected (0.177 sec) Maéva a 101 points bravo !
CALL answer_quiz("Lucie", 4, "Le créateur de l'univers"); -- Query OK, 3 rows affected (0.028 sec)
CALL check_answer("Lucie", 4); -- Query OK, 3 rows affected (0.065 sec)

CALL add_response(6, "Nicolas Sarkozy", FALSE); -- ERROR 1644 (45000): Text quiz must have always valid = TRUE
CALL add_response(6, "Emmanuel Macron", TRUE); -- Query OK 
CALL add_response(6, "Édouard Philippe", TRUE); -- ERROR 1644 (45000): Text quiz must have only one response 
CALL stock_quiz(6); -- Query OK

CALL crontab_routine(); -- Query OK Attention dépend de la date actuelle
CALL answer_quiz("Lucie", 6, " EmmAnuel MacRoN    "); -- Query OK
CALL answer_quiz("Lucie", 6, "Emmanuel Macron"); -- ERROR 1644 (45000): Player must give only one answer for radio or text quiz
CALL answer_quiz("Lucie", 6, "Nicolas Sarkozy"); -- ERROR 1644 (45000): Player must give only one answer for radio or text quiz
CALL answer_quiz("Lucie", 6, "Édouard Philippe"); -- ERROR 1644 (45000): Player must give only one answer for radio or text quiz
CALL answer_quiz("Maéva", 6, "Nicolas Sarkozy"); -- Query OK
CALL check_answer("Lucie", 6); -- Query OK 10 points pour Lucie
CALL check_answer("Maéva", 6); -- Query OK une défaite pour Maéva

CALL add_response(7, "Nicolas", FALSE); -- Query OK
CALL add_response(7, "Sarkozy", TRUE); -- Query OK 
CALL add_response(7, "Frédéric", TRUE); -- ERROR 1644 (45000): Radio quiz must have only one valid response 
CALL stock_quiz(7); -- Query OK
CALL open_quiz(7); -- Query OK 
CALL answer_quiz("Amélie", 7, "Frédéric"); -- ERROR 1644 (45000): Response doesn't match quiz id
CALL answer_quiz("Amélie", 7, "Sarkozy"); -- Query OK
CALL answer_quiz("Amélie", 7, "Bruel"); -- ERROR 1644 (45000): Response doesn't match quiz id
CALL answer_quiz("Amélie", 7, "Sarkozy"); -- ERROR 1644 (45000): Player must give only one answer for radio or text quiz
CALL answer_quiz("Amélie", 7, "Nicolas"); -- ERROR 1644 (45000): Player must give only one answer for radio or text quiz
CALL answer_quiz("Maéva", 7, "Nicolas"); -- Query OK
CALL check_answer("Amélie", 7); -- Query OK 10 points pour Amélie
CALL check_answer("Maéva", 7); -- Query OK une défaite pour Maéva

CALL crontab_routine(); -- Query OK Attention dépend de la date actuelle
CALL remove_quiz("Maxime", 1); -- ERROR 1644 (45000): You can delete only quiz in stock
CALL remove_quiz("Maxime", 5); -- ERROR 1644 (45000): You can delete only quiz in stock
CALL remove_quiz("Maxime", 7); -- ERROR 1644 (45000): You can delete only quiz in stock

SELECT * FROM HighScoreView;
/************************************
+---------+--------+---------+------+
| login   | points | success | fail |
+---------+--------+---------+------+
| Maéva   |    101 |       2 |    2 |
| Amélie  |     10 |       1 |    2 |
| Lucie   |     10 |       1 |    1 |
| Maxime  |      0 |       0 |    0 |
+---------+--------+---------+------+
************************************/