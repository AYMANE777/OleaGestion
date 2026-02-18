<?php
// includes/ajax/Fitor/Patron/patron_status_details.php
declare(strict_types=1);

require_once __DIR__ . '/../../../../config/config.ini.php';
mysqli_set_charset($connect, 'utf8mb4');

header('Content-Type: application/json; charset=utf-8');

$id_patron = isset($_GET['id_patron']) ? (int)$_GET['id_patron'] : 0;
if ($id_patron <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'id_patron invalide'], JSON_UNESCAPED_UNICODE);
  exit;
}

/** Totaux */
$sqlTot = "
SELECT
  p.id_patron, p.nom_patron, p.prenom_patron,
  COALESCE(v.total_versements,0) AS total_versements,
  COALESCE(b.total_bons,0)       AS total_bons,
  COALESCE(a.total_affecte,0)    AS total_affecte,
  (COALESCE(b.total_bons,0) - COALESCE(a.total_affecte,0)) AS reste_a_payer_bons,
  (COALESCE(v.total_versements,0) - COALESCE(a.total_affecte,0)) AS credit_non_affecte,
  (COALESCE(v.total_versements,0) - COALESCE(b.total_bons,0)) AS solde_global
FROM patron p
LEFT JOIN (
  SELECT c.id_patron, SUM(v.montant) AS total_versements
  FROM chauffeur c
  JOIN versement v ON v.id_Fchauffeur = c.id_Fchauffeur
  GROUP BY c.id_patron
) v  ON v.id_patron = p.id_patron
LEFT JOIN (
  SELECT c.id_patron, SUM(bf.prix_net) AS total_bons
  FROM chauffeur c
  JOIN vehicule ve  ON ve.id_Fchauffeur = c.id_Fchauffeur
  JOIN taratonage t ON t.id_vehicule_Fitor = ve.id_vehicule
  JOIN bon_fitor bf ON bf.id_taratonage = t.id_taratonage
  GROUP BY c.id_patron
) b  ON b.id_patron = p.id_patron
LEFT JOIN (
  SELECT c.id_patron, SUM(av.montant_affecte) AS total_affecte
  FROM affectation_versement av
  JOIN bon_fitor bf ON bf.id_bon = av.id_bon
  JOIN taratonage t ON t.id_taratonage = bf.id_taratonage
  JOIN vehicule ve  ON ve.id_vehicule = t.id_vehicule_Fitor
  JOIN chauffeur c  ON c.id_Fchauffeur = ve.id_Fchauffeur
  GROUP BY c.id_patron
) a ON a.id_patron = p.id_patron
WHERE p.id_patron = ?
LIMIT 1";
$st = $connect->prepare($sqlTot);
if (!$st) {
  http_response_code(500);
  echo json_encode(['error' => 'Erreur préparation requête totaux'], JSON_UNESCAPED_UNICODE);
  exit;
}
$st->bind_param('i', $id_patron);
$st->execute();
$totaux = $st->get_result()->fetch_assoc() ?: [];
$st->close();

// Normalise numeric fields
if ($totaux) {
  foreach (['total_versements','total_bons','total_affecte','reste_a_payer_bons','credit_non_affecte','solde_global'] as $k) {
    $totaux[$k] = isset($totaux[$k]) ? (float)$totaux[$k] : 0.0;
  }
}

/** Versements */
$sqlV = "
SELECT
  v.id_versement, v.montant, v.date_versement, v.mode_paiement, v.note, v.image_versement,
  COALESCE(SUM(av.montant_affecte), 0) AS montant_deduit,
  (v.montant - COALESCE(SUM(av.montant_affecte), 0)) AS reste_credit
FROM versement v
JOIN chauffeur c ON c.id_Fchauffeur = v.id_Fchauffeur
LEFT JOIN affectation_versement av ON av.id_versement = v.id_versement
WHERE c.id_patron = ?
GROUP BY v.id_versement
ORDER BY v.date_versement DESC, v.id_versement DESC";
$sv = $connect->prepare($sqlV);
$sv->bind_param('i', $id_patron);
$sv->execute();
$rsv = $sv->get_result();
$versements = [];
while ($row = $rsv->fetch_assoc()) {
  $row['montant'] = (float)$row['montant'];
  $row['montant_deduit'] = (float)$row['montant_deduit'];
  $row['reste_credit'] = (float)$row['reste_credit'];
  $versements[] = $row;
}
$sv->close();

/** Bons */
$sqlB = "
SELECT
  bf.id_bon, bf.quantite, bf.prix_init, bf.prix_net, bf.provider, bf.created_at,
  COALESCE(SUM(av.montant_affecte), 0) AS deja_paye,
  (bf.prix_net - COALESCE(SUM(av.montant_affecte), 0)) AS reste_a_payer
FROM bon_fitor bf
JOIN taratonage t ON t.id_taratonage = bf.id_taratonage
JOIN vehicule ve  ON ve.id_vehicule = t.id_vehicule_Fitor
JOIN chauffeur c  ON c.id_Fchauffeur = ve.id_Fchauffeur
LEFT JOIN affectation_versement av ON av.id_bon = bf.id_bon
WHERE c.id_patron = ?
GROUP BY bf.id_bon
ORDER BY bf.created_at DESC, bf.id_bon DESC";
$sb = $connect->prepare($sqlB);
$sb->bind_param('i', $id_patron);
$sb->execute();
$rsb = $sb->get_result();
$bons = [];
while ($row = $rsb->fetch_assoc()) {
  $row['quantite'] = (float)$row['quantite'];
  $row['prix_init'] = (float)$row['prix_init'];
  $row['prix_net'] = (float)$row['prix_net'];
  $row['deja_paye'] = (float)$row['deja_paye'];
  $row['reste_a_payer'] = (float)$row['reste_a_payer'];
  $bons[] = $row;
}
$sb->close();

echo json_encode([
  'totaux'     => $totaux,
  'versements' => $versements,
  'bons'       => $bons
], JSON_UNESCAPED_UNICODE);
