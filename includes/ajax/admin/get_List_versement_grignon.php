<?php 
require_once '../../../config/config.ini.php'; 
header('Content-Type: application/json; charset=utf-8');

$sql = "
  SELECT
    v.id_versement,
    v.montant,
    v.mode_paiement,
    v.note,
    DATE_FORMAT(v.date_versement, '%d/%m/%Y %H:%i') AS date_fmt,
    p.nom_patron AS nom_patron,
    p.prenom_patron AS prenom_patron,
    c.nom AS nom_chauffeur,
    c.prenom AS prenom_chauffeur,
    v.image_versement,
    c.image_Fchauffeur AS image_Fchauffeur,
    p.image_Patron AS image_Patron
  FROM versement v
  LEFT JOIN chauffeur c ON c.id_Fchauffeur = v.id_Fchauffeur
  LEFT JOIN patron p ON p.id_patron = c.id_patron
  ORDER BY v.date_versement DESC, v.id_versement DESC
";

$res = $connect->query($sql);
$data = [];

if ($res) {
  while ($row = $res->fetch_assoc()) $data[] = $row;
  echo json_encode(['ok' => true, 'rows' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
  echo json_encode(['ok' => false, 'error' => $connect->error]);
}
