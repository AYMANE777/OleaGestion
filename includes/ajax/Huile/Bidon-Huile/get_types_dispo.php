<?php
//===================.../includes/ajax/Huile/Bidon-Huile/get_types_dispo.php=============================
require_once '../../../../config/config.ini.php'; 
header('Content-Type: application/json; charset=utf-8');

try {
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

  $sql = "
    SELECT
      b.id_bidon,
      b.nom_bidon,
      b.type_bidon,
      b.prix_bidon,

      -- initial stock enregistré
      b.quantite_bidon AS stock_initial,

      -- quantités vendues via huile
      COALESCE(dh.vendu_huile, 0) AS vendu_via_bon_huile,

      -- quantités vendues via bidon
      COALESCE(db.vendu_bidon, 0) AS vendu_via_bon_bidon,

      -- total vendu / sorti
      (COALESCE(dh.vendu_huile, 0) + COALESCE(db.vendu_bidon, 0)) AS total_vendu_ou_sorti,

      -- stock exact restant
      (b.quantite_bidon - COALESCE(dh.vendu_huile, 0) - COALESCE(db.vendu_bidon, 0)) AS stock_restant

    FROM bidons b
    LEFT JOIN (
      SELECT id_bidon, SUM(quantite_bidon) AS vendu_huile
      FROM details_bon_huile
      GROUP BY id_bidon
    ) dh ON dh.id_bidon = b.id_bidon
    LEFT JOIN (
      SELECT id_bidon, SUM(quantite) AS vendu_bidon
      FROM details_bon_bidon
      GROUP BY id_bidon
    ) db ON db.id_bidon = b.id_bidon
    ORDER BY b.nom_bidon
  ";

  $res = mysqli_query($connect, $sql);
  $out = [];

  while ($r = mysqli_fetch_assoc($res)) {
    $out[] = [
      'id_bidon'          => (int)$r['id_bidon'],
      'nom_bidon'         => (string)$r['nom_bidon'],
      'type_bidon'        => (string)$r['type_bidon'],
      'prix_bidon'        => (float)$r['prix_bidon'],
      'stock_initial'     => (float)$r['stock_initial'],
      'vendu_via_bidon'   => (float)$r['vendu_via_bon_bidon'],
      'vendu_via_huile'   => (float)$r['vendu_via_bon_huile'],
      'total_vendu'       => (float)$r['total_vendu_ou_sorti'],
      'stock_restant'     => max(0.0, (float)$r['stock_restant']),
    ];
  }

  echo json_encode(['ok' => true, 'data' => $out], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Erreur serveur: '.$e->getMessage()]);
  exit;
}
