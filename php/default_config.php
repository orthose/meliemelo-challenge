<?php

// Garder ce fichier secret et s'assurer qu'il est inaccessible sur le Web
$config = array(
  "debug" => true, // Mettre à false en production
  "absolutePath" => "/var/www/html/meliemelo-challenge/",
  "serverURL" => "", // MODIFIER
  "dbname" => "meliemelo_challenge",
  "host" => "localhost",
  "login_undefined" => "undefined_meliemelo",
  "passwd_undefined" => "test", // MODIFIER
  "login_player" => "player_meliemelo",
  "passwd_player" => "test", // MODIFIER
  "login_admin" => "admin_meliemelo",
  "passwd_admin" => "test", // MODIFIER
  "theme" => "default_style",
); 

?>