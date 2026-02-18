<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php'; // adjust if needed
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

try {
  $sql = "
  SELECT
    p.id_piece,
    p.ref_piece,
    p.nom_piece,
    p.description_piece,
    p.type_piece,
    p.date_ajout,
    p.id_fournisseur,
    CONCAT(f.prenom_fournisseur, ' ', f.nom_fournisseur) AS fournisseur_nom,
    (
      SELECT COALESCE(SUM(sp.quantite_piece), 0)
      FROM stock_piece sp
      WHERE sp.id_piece = p.id_piece
    ) AS quantite,
    (
      SELECT COALESCE(SUM(st.quantite), 0)
      FROM statut_piece st
      WHERE st.id_piece = p.id_piece AND st.statut = 'en_marche'
    ) AS statut_marche,
    (
      SELECT COALESCE(SUM(st.quantite), 0)
      FROM statut_piece st
      WHERE st.id_piece = p.id_piece AND st.statut = 'en_panne'
    ) AS statut_panne,
    (
      SELECT COALESCE(SUM(st.quantite), 0)
      FROM statut_piece st
      WHERE st.id_piece = p.id_piece AND st.statut = 'en_maintenance'
    ) AS statut_maintenance,
    (
      SELECT ip.image
      FROM image_piece ip
      WHERE ip.id_piece = p.id_piece
      ORDER BY ip.date_ajout ASC, ip.id_image ASC
      LIMIT 1
    ) AS first_image
  FROM piece p
  LEFT JOIN fournisseur f ON f.id_fournisseur = p.id_fournisseur
  ORDER BY p.date_ajout DESC
  ";

  $res = $connect->query($sql);
  $rows = [];
  while ($r = $res->fetch_assoc()) $rows[] = $r;

  echo json_encode(['ok' => true, 'rows' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
  exit;
}
