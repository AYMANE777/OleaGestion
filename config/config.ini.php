<?php
define("SITE_NAME", "Gestion Meknassa Olive");
define("DB_HOST", "localhost");
define("DB_NAME", "gestion_meknassa_olive");
define("DB_USERNAME", "root");
define("DB_PASSWORD", "");
function global_24h_token(string $salt = ''): string {
  $tz = new DateTimeZone('Africa/Casablanca');
  $today = (new DateTimeImmutable('now', $tz))->format('Y-m-d'); // day-window key
  $secret = defined('APP_SECRET') ? APP_SECRET : ($salt ?: php_uname()); // add APP_SECRET in config if you have one
  return substr(hash('sha256', $today . '|' . $secret), 0, 6); // 12 hex chars = short & readable
}
// Connexion MySQL unique et sécurisée
if (!isset($connect) || !$connect instanceof mysqli) {
    $connect = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($connect->connect_errno) {
        error_log("Erreur MySQL : " . $connect->connect_error);
        die("Erreur de connexion à la base de données. Réessaye plus tard.");
    }

    // Timeout réduit pour libérer les connexions inactives
    $connect->query("SET SESSION wait_timeout = 30;");
    $connect->query("SET SESSION interactive_timeout = 30;");
}

// Fermeture automatique à la fin du script
register_shutdown_function(function() use ($connect) {
    if ($connect instanceof mysqli) {
        $connect->close();
    }
});
?>