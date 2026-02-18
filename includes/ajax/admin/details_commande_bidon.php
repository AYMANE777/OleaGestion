<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

try {
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $connect->set_charset('utf8mb4');

  $sql = "
    SELECT 
      bon.id_bon_sortie_bidon     AS id_bon,
      bon.created_at              AS date_bon,
      CONCAT(c.prenom,' ',c.nom)  AS client,
      bon.destination,
      SUM(dbb.quantite)           AS quantite_totale,
      GROUP_CONCAT(DISTINCT bdn.type_bidon SEPARATOR ', ') AS types,
      SUM(dbb.quantite * dbb.prix_unitaire) AS montant_total,
      bon.mode_paiement,
      bon.statut
    FROM details_bon_bidon dbb
    JOIN bon_sortie_bidon bon ON bon.id_bon_sortie_bidon = dbb.id_bon
    JOIN client c ON c.id = bon.id_client
    JOIN bidons bdn ON bdn.id_bidon = dbb.id_bidon
    GROUP BY bon.id_bon_sortie_bidon
    ORDER BY bon.created_at DESC
  ";

  $res = $connect->query($sql);
  $rows = [];

  while ($r = $res->fetch_assoc()) {
    $rows[] = [
      'id_bon' => (int)$r['id_bon'],
      'date_bon' => $r['date_bon'],
      'client' => $r['client'],
      'destination' => $r['destination'] ?: '-',
      'quantite_totale' => (float)$r['quantite_totale'],
      'types' => $r['types'],
      'montant_total' => (float)$r['montant_total'],
      'mode_paiement' => $r['mode_paiement'] ?: '—',
      'statut' => $r['statut'] ?: '—'
    ];
  }

  echo json_encode([
    'status' => 'success',
    'rows' => $rows
  ]);

} catch (Exception $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
