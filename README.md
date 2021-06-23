# Introduction
Application web multi-utilisateurs de quiz journalier où s'affrontent les joueurs pour monter sur le podium.

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

## Responsabilités entre client et serveur
Le client ne doit servir qu'à de la visualisation. Le serveur doit vérifier tout
ce que le client lui envoie rigoureusement.

## Rôles des utilisateurs
Le client ne doit pas pouvoir compromettre les données stockées sur le serveur.
Chaque appel AJAX doit être vérifié via une session. On vérifie ainsi le rôle
de l'utilisateur et l'action qu'il tente d'exécuter. De plus, en fonction de son
rôle l'utilisateur ne devra pas avoir reçu toutes les fonctions javascript possibles.

Liste des rôles et actions possibles
* Administrateur = {1, 2, 3, 4, 8}  U actions(Joueur)
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