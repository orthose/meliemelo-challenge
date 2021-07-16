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
mysql> CALL set_role("nouvel_utilisateur", "role");
```
Ce premier utilisateur peut par la suite donner le rôle d'administrateur,
ou le reprendre à un utilisateur depuis l'interface web directement.

# Cahier des charges

## Définition d'un quiz
Un quiz est un fichier stocké sur le serveur dans le format JSON et dont la
structure permet d'exprimer les différents types de quiz possibles.
```
{
  "id": 123,
  "open": "01/06/2021",
  "close": "01/07/2021",
  "difficulty": 1,
  "points": 2,
  "type": "checkbox",
  "question": "Parmi ces bâtiments lesquels se trouvent à Paris ?",
  "response": [
    {"La Tour Eiffel": true},
    {"L'arc de triomphe": true},
    {"Le château de Versailles": false}
  ]
  "players": {
    "login1": ["La Tour Eiffel", "L'arc de triomphe"],
    "login2": ["Le château de Versailles"],
  },
}
```
**Contrainte 1** = L'identifiant est le même que dans le nom du fichier. Ici on aurait
affaire au fichier 123.json. De plus, on part de 0 puis on incrémente de 1 à chaque
création de quiz.

**Contrainte 2** = La date d'ouverture doit être antérieure à la date de fermeture.
Quand la date d'expiration est atteinte plus personne ne peut répondre au quiz, et
il peut être archivé. Un archivage automatique serait encore mieux. C'est le créateur
du quiz qui décide du nombre de jours de validité du quiz. Par défaut il sera fixé à 10.
La date d'expiration est alors calculée automatiquement. La date d'ouverture est décidée
par le système. Tant que le fichier n'est pas ouvert, le champ "open" vaut null.

**Contrainte 3** = Le niveau de difficulté doit être compris entre 1 et 10. 1 étant
le niveau le plus facile et 10 le plus dur.

**Contrainte 4** = Le nombre de points doit être un entier positif entre 0 et 10.
Si le joueur répond correctement au quiz son total sera incrémenté d'autant.
On ne peut pas perdre de points.

**Contrainte 5** = Le champ "type" ne peut avoir que 4 valeurs possibles
* checkbox = plusieurs choix possibles
**Contrainte 6** = Il peut y avoir plusieurs true voire que des true comme réponses.
* radio = un seul choix possible
```
{
  "type": "radio",
  "question": "Parmi ces bâtiments lesquel ne se trouve pas à Paris ?",
  "response": [
    {"La tour Eiffel": false},
    {"L'arc de triomphe": false},
    {"Le château de Versailles": true}
  ]
}
```
**Contrainte 7** = Il ne doit y avoir qu'un seul true pour que le JSON soit valide. 
* text = champ texte à compléter
```
{
  "type": "text",
  "question": "Quel est le nom de la tour la plus célèbre de Paris ?"
  "response": "Eiffel"
}
```
**Contrainte 8** = Le champ "response" ne contient plus qu'une chaîne de caractères.
L'évaluation de la réponse du joueur devra être insensible à la casse.
* range = barre à ajuster pour saisir un nombre
```
{
  "type": "range",
  "question": "De quelle hauteur est la tour Eiffel ?"
  "response": [324, {"max": 500, "min": 10,"step": 1}]
}
```
**Contrainte 9** = Le champ "response" est un tableau de seulement 2 cases. La première
est la réponse. La deuxième exprime l'intervalle des nombres possibles parmi lesquels
le joueur peut choisir.

**Contrainte 10** = Le champ "players" contient les logins des utilisateurs
ayant répondu, auquel on associe toujours un tableau contenant la/les réponse(s) 
saisie(s) par le joueur. On peut associer un tableau vide si le joueur n'a rien 
répondu mais a validé le quiz. Dans ce cas le joueur gagne 0 point.

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

## Données utilisateur
Le fichier contenant les données utilisateur est un fichier json de la forme :
```
{
  "login1": {...},
  "login2": {...},
}
```
**Contrainte 11** = Un login ne doit pas apparaître 2 fois. Un nouvel inscrit
ne doit pas pouvoir écraser un profil en ayant choisi un login préexistant.

Chaque objet associé à un login correspond aux données de l'utilisateur
et est de la forme :
```
{
  "role": "player",
  "answered": [123, 456, 789],
  "created": [],
  "points": 6,
  "success": 3,
  "fail": 0,
}
```
**Contrainte 12** = Le champ role peut prendre les valeurs player ou admin mais
pas undefined !

**Contraint 13** = Le champ answered est un tableau contenant les identifiants 
valides des quiz, auxquels a répondu le joueur.

**Contrainte 14** = Le champ created contient la liste des identifiants de quiz
créés par l'utilisateur. Il ne peut être rempli que si l'utilisateur est admin.
Mais il doit être présent même si l'utilisateur est un simple joueur (le rôle
d'admin pourra être révoqué). Un joueur ne peut répondre que à des quiz qu'il n'a
pas créé.

**Contrainte 15** = Le champ points représente le nombre de points de l'utilisateur.
Il est initialisé à 0. Le champ success compte le nombre de quiz réussis,
et fail le nombre de quiz ratés.