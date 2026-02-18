<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once '../../../config/config.ini.php'; // adjust if needed

try {
  $sql = "
    SELECT 
      t.id_transporteur,
      CONCAT(t.prenom_trans, ' ', t.nom_trans) AS nom_complet,
      t.telephone_trans,
      t.Adresse_Source,
      t.image_trans,
      COALESCE(SUM(o.poid_net), 0) AS total_poids,
      COUNT(o.id_taratonage) AS nb_chargements
    FROM transporteur t
    LEFT JOIN vehiculetrans v ON v.id_transporteur = t.id_transporteur
    LEFT JOIN taratonage_olive o ON o.id_vehicule_trans = v.id_vehicule_trans
    GROUP BY t.id_transporteur
    ORDER BY total_poids DESC, nom_complet ASC
    LIMIT 50
  ";

  $res = $connect->query($sql);
  $rows = [];
  while ($r = $res->fetch_assoc()) {
    $rows[] = [
      'id_transporteur' => (int)$r['id_transporteur'],
      'nom_complet' => (string)$r['nom_complet'],
      'telephone' => (string)$r['telephone_trans'],
      'adresse' => (string)$r['Adresse_Source'],
      'image' => (string)($r['image_trans'] ?? ''),
      'total_poids' => (float)$r['total_poids'],
      'nb_chargements' => (int)$r['nb_chargements']
    ];
  }

  echo json_encode(['ok' => true, 'rows' => $rows]);
} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
