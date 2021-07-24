<?php

require("php/config.php");
require("php/quiz_manager.php");

$res = cron_routine();

// Enregistrement des logs de cron
if ($config["debug"]) {
  $log = "[".date('Y-m-d H:i:s')."] ".json_encode($res)."\n";
  file_put_contents($config["absolutePath"]."log.txt", $log, FILE_APPEND);
}

?>
