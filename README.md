# Introduction
Application web multi-utilisateurs de quiz journalier où s'affrontent 
les joueurs pour monter sur le podium.

# Mise en production

## Paramétrage de la base de données
L'application n'a été testée qu'avec le SGBD MariaDB.
En principe, elle devrait aussi être compatible avec MySQL.

1. Création de la base de données
```
$ cd sql
$ sudo mysql
mysql> CREATE DATABASE meliemelo_challenge;
```
2. Création des utilisateurs
* L'utilisateur principal est meliemelo à n'utiliser qu'en ligne de commande et pour cron.php.
```
mysql> CREATE USER "meliemelo"@"localhost" IDENTIFIED BY "mot_de_passe";
mysql> GRANT ALL ON meliemelo_challenge.* TO "meliemelo"@"localhost";
```
* L'utilisateur admin_meliemelo correspond aux droits des utilisateurs dont
le rôle est fixé à admin.
```
mysql> CREATE USER "admin_meliemelo"@"localhost" IDENTIFIED BY "mot_de_passe";
```
* L'utilisateur player_meliemelo correspond aux droits des utilisateurs dont
le rôle est fixé à player.
```
mysql> CREATE USER "player_meliemelo"@"localhost" IDENTIFIED BY "mot_de_passe";
```
* L'utilisateur undefined_meliemelo correspond aux droits des utilisateurs
non-connectés.
```
mysql> CREATE USER "undefined_meliemelo"@"localhost" IDENTIFIED BY "mot_de_passe";
```
3. Se déconnecter puis se connecter
```
mysql> EXIT;
$ mysql -u meliemelo -D meliemelo_challenge -p
```
4. Créer les tables de la base
```
mysql> source schema.sql;
```
5. Créer les fonctions et procédures
```
mysql> source actions.sql;
```
6. Définition des droits
```
mysql> EXIT;
$ sudo mysql
mysql> source rules.sql;
```

## Configuration de l'accès à la base
Rendez-vous dans le fichier ./php/config.php et modifiez uniquement
les valeurs commençant par passwd. Ce sont les mots de passe associés
aux utilisateurs de MariaDB créés précédemment.

## Créer un premier utilisateur
Pour pouvoir commencer à créer des quiz, il va falloir créer un premier 
utilisateur, avec le rôle d'administrateur.
Pour cela rendez-vous sur le site web et créez un nouveau compte.
Une fois créé, rendez-vous sur votre base de données.
```
$ mysql -u meliemelo -D meliemelo_challenge -p
mysql> CALL set_role("nouvel_utilisateur", "admin");
```
Ce premier utilisateur peut par la suite donner le rôle d'administrateur,
ou le reprendre à un utilisateur depuis l'interface web directement.

## Enregistrer une tâche de fond avec crontab
Pour mettre en stock et archiver les quiz, l'application utilise une tâche
de fond, exécutée de manière régulière grâce à crontab.
Si vous désirez recevoir les fichiers de log de ces tâches, exécutez les
commandes suivantes (le mode débogage doit être activé dans php/config.php).
```
$ touch log.txt
$ chmod o+rw log.txt
```
Pour enregistrer la tâche de fond exécutez ouvrez d'abord l'éditeur de crontab.
```
crontab -e
```
Puis entrez la ligne suivante en fin de fichier, qui va exécuter toutes les
12 heures la routine. Pour exécuter journalièrement on peut aussi remplacer par
@daily la suite de caractères précédant la commande.
```
0 */12 * * * php -f /var/www/html/meliemelo-challenge/cron.php
```
Si on veut quelque chose de plus réactif, on peut opter pour une actualisation
toutes les 5 minutes.
```
*/5 * * * * php -f /var/www/html/meliemelo-challenge/cron.php
```

## Sauvegarde de la base
Commande pour faire une sauvegarde de la base de données.
```
$ mysqldump -u meliemelo -p meliemelo_challenge > ~/meliemelo_challenge_backup_$(date +%F).sql
```
Commande pour restaurer la base de données à partir d'une sauvegarde.
```
$ mysqldump -u meliemelo -p meliemelo_challenge < ~/meliemelo_challenge_backup.sql
```

## Mettre à jour le schéma de la base
Il peut être nécessaire de mettre à jour la base de données
comme les procédures stockées.
Pour cela il faut commencer par éteindre le serveur web.
```
$ sudo systemctl stop nginx
```
Il faut ensuite entrer dans la base de données en tant que super-utilisateur
et mettre à jour la liste des procédures.
```
$ cd sql
$ sudo mysql -D meliemelo_challenge
mysql> source actions.sql;
mysql> source rules.sql;
mysql> EXIT;
```
Il ne reste plus qu'à redémarrer le serveur web.
```
$ sudo systemctl start nginx
```

## Changer le mot de passe d'un utilisateur
Si un utilisateur oublie son mot de passe, l'administrateur du serveur
peut changer le mot de passe de l'utilisateur grâce à une procédure d'urgence.
```
$ mysql -u meliemelo -D meliemelo_challenge -p
mysql> CALL emergency_set_password("login", "new_password");
mysql> EXIT;
```
L'utilisateur peut toujours par la suite modifier de nouveau son mot de passe
par l'interface web. 

# Cahier des charges

## Responsabilités entre client et serveur
Le client ne doit servir qu'à de la visualisation. Le serveur doit vérifier tout
ce que le client lui envoie rigoureusement. Notamment chaque action ne peut être
effectuée qu'avec un rôle précis, et dans un contexte particulier.

## Rôles des utilisateurs
Le client ne doit pas pouvoir compromettre les données stockées sur le serveur.
Chaque appel AJAX doit être vérifié via une session. On vérifie ainsi le rôle
de l'utilisateur et l'action qu'il tente d'exécuter. De plus, en fonction de son
rôle l'utilisateur ne devra pas avoir reçu toutes les fonctions javascript possibles.

Liste des rôles et actions possibles
* Administrateur = {1, 2, 3, 4, 8} U actions(Joueur)
* Joueur = {5, 7, 9, 10, 11, 12}

Détail des actions possibles
1. Création de quiz = Éditer et d'ajouter un quiz au stock de quiz.
2. Suppression de quiz = Supprimer définitivement un quiz archivé .
3. Archivage de quiz = Archiver un quiz lorsque sa date d'expiration est atteinte.
Plus personne ne peut y répondre. La réponse du quiz reste visible aux joueurs
y ayant répondu.
4. Consulter le stock de quiz = Consulter les quiz en attente d'être joué, pas
encore visibles par les joueurs.
5. Consulter l'historique de quiz = Consulter les quiz auxquels on a joué, les réponses
données, et la correction, quand les quiz ont atteint leur date d'expiration.
Ainsi que le nombre de participation et le taux de réussite.
6. Consulter les statistiques d'utilisation = Consulter le nombre de joueurs,
le nombre d'utilisateurs connectés, le nombre de connexions total au site,
le nombre total de quiz en stock, archivés, supprimés, en jeu, le nombre total
de participation. 
7. Consulter le classement des joueurs = Classement en nombre de points avec les
pseudos des joueurs et mise en valeur du podium.
8. Définir administrateur = Choisir un joueur et lui octroyer le statut d'administrateur.
9. Répondre au quiz du jour = Consulter le quiz du jour et y répondre puis envoyer
la réponse. Il n'est plus possible de modifier la réponse après l'envoi.
Un créateur ne peut pas répondre à son propre quiz.
10. Répondre à un quiz antérieur = Les quiz ont une durée de vie avant expiration
durant laquelle il est possible d'y répondre. Passé ce délai il n'est plus possible
d'y répondre.
11. Modifier mot de passe = Tout utilisateur peut modifier son mot de passe.
Par contre pas de système de récupération de mot de passe car pas la création
de compte ne nécessite pas d'entrer un mail.
12. Connexion / Déconnexion / Création de compte

## Manuel des fonctionnalités
Toutes les fonctionnalités sont assez intuitives d'utilisation, et la plupart 
ne seront pas détaillées. En revanche, certains points peuvent être accompagnés
d'explications supplémentaires.

# Création de quiz
La création de quiz est réservée aux administrateurs. Plusieurs champs doivent
être remplis avant d'envoyer le quiz au serveur pour vérification.

Le champ question, et les champs réponses doivent contenir du texte. Toute balise
HTML sera automatiquement échappée par le serveur. En revanche, il est possible
d'ajouter des balises markdown pour contourner cette sécurité.
Ainsi, les liens, la mise en gras et la balise code sont implémentées pour ces champs.
Une fois le quiz affiché, les éléments markdown sont convertis en éléments HTML.

Notez que la coloration syntaxique de la balise code est gérée par un programme tierce
disponible sur ce [site](https://highlightjs.org/).

Lorsqu'on ajoute une réponse, la réponse ajoutée voit son cadre mis en bleu foncé.
Il est ensuite possible de modifier la réponse, et sa valeur de vérité (Vrai/Faux).
Cependant, si le texte de la réponse est supprimé après coup et laissé vide,
la réponse ne sera pas considérée. Cela peut être un bon moyen de supprimer une
réponse erronée.
