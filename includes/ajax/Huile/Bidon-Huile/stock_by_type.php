<?php
// ../includes/ajax/Bidon-Huile/stock_by_type.php
require_once '../../../../config/config.ini.php'; // adapte le chemin si besoin
header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT UPPER(TRIM(type_bidon)) AS type_bidon, COALESCE(SUM(quantite_bidon),0) AS dispo
        FROM bidons
        GROUP BY UPPER(TRIM(type_bidon))";
$res = $connect->query($sql);

$out = [];
if ($res) {
  while ($r = $res->fetch_assoc()) {
    $out[$r['type_bidon']] = (int)$r['dispo']; // dispo = nombre de bidons (comptes), pas litres
  }
}

echo json_encode(['ok' => true, 'stock' => $out], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
