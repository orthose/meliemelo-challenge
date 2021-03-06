<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>MelieMelo Challenge</title>
  <!-- Responsive Web Site for smartphone -->
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <!-- Icônes -->
  <link rel="icon" type="image/png" href="favicon.png" sizes="512x512">
  <link rel="apple-touch-icon" type="image/png" href="favicon.png" sizes="512x512">
  <!-- Feuilles de style -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Ubuntu">
  <link href='css/a11y-light.min.css' rel='stylesheet'>
  <?php
    require("php/config.php");
    $styles = array("default_style", "halloween", "christmas");
    // Style par défaut
    if (!isset($config["theme"]) || !in_array($config["theme"], $styles)) {
      echo "<link href='css/default_style.css' rel='stylesheet'>";
    }
    // Style personnalisé
    else {
      echo "<link href='css/".$config["theme"].".css' rel='stylesheet'>";
    }
  ?>
  <!-- Scripts -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="js/popstate.js"></script>
  <script src="js/convert_markdown.js"></script>
  <script src="js/daily_msg.js"></script>
  <script src="js/login_manager_pages.js"></script>
  <script src="js/login_manager_ajax.js"></script>
  <script src="js/lib_quiz_manager.js"></script>
  <script src="js/quiz_manager_pages.js"></script>
  <script src="js/quiz_manager_ajax.js"></script>
  <script src="js/bug_report.js"></script>
  <script src="js/highlight.min.js"></script>
  <script>
    // Variables globales de session
    let user_login = "";
    let user_role = "undefined";
    // Données de configuration
    const config = {
    <?php
      require("php/config.php");
      echo "debug: '".$config["debug"]."',";
      echo "serverURL: '".$config["serverURL"]."',";
    ?>
  };
  <?php
    // Message personnalisé 
    $daily_msg = file_get_contents("public_data/daily_msg.txt");
    // Message aléatoire
    if ($daily_msg === "") {
      $random_msg = json_decode(file_get_contents("public_data/random_msg.json"), true);
      $daily_msg = $random_msg[rand(0, count($random_msg) - 1)];
    }
    $daily_msg = str_replace("`", "\`", $daily_msg);
    $daily_msg = str_replace(array("\r\n", "\r", "\n"), "<br>", $daily_msg);
  ?>
  const daily_msg = `<?php echo $daily_msg; ?>`;
  </script>
</head>
<body onload="convert_md_daily_msg()">
  <header>
    <h1>MelieMelo Challenge WebApp</h1>
    <a href="index.php">Revenir à l'Accueil</a>
  </header>
  <section id="manage_account">
  </section>
  <main>
    <p>Bienvenue sur <strong>MelieMelo Challenge</strong>, l'application en ligne de quiz en folie&nbsp;!</p>
    <button onclick="register_page()">Créer un Compte</button>
    <button onclick="connection_page()">Se Connecter</button>
  </main>
  <footer>
    Application créée par Maxime Vincent | <a href="https://github.com/orthose/meliemelo-challenge" target="_blank">Code Source</a><br>
    Commande de Amélie Gaillard | Janvier 2022
  </footer>
</body>
</html>