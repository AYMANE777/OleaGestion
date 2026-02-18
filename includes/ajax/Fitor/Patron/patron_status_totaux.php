<?php
// includes/ajax/Fitor/Patron/patron_status_totaux.php
require_once __DIR__ . '/../../../../config/config.ini.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');

$where = '';
$params = [];
$types  = '';
if ($q !== '') {
  $where = " WHERE (p.nom_patron LIKE CONCAT('%', ?, '%')
                OR p.prenom_patron LIKE CONCAT('%', ?, '%')
                OR p.telephone_patron LIKE CONCAT('%', ?, '%')) ";
  $params = [$q, $q, $q];
  $types  = 'sss';
}

/* Sous-agrégats */
$sql = "
SELECT
  p.id_patron,
  p.nom_patron,
  p.prenom_patron,

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
$where
ORDER BY solde_global DESC, p.id_patron DESC
";

$stmt = $connect->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while ($r = $res->fetch_assoc()) { $out[] = $r; }
$stmt->close();

echo json_encode(['rows' => $out], JSON_UNESCAPED_UNICODE);
