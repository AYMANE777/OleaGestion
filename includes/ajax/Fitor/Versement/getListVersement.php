<?php
require_once '../../../../config/config.ini.php'; // adapte le chemin si besoin
header('Content-Type: application/json; charset=utf-8');

// NOTE: Adaptez noms de tables/colonnes à votre schéma
$sql = "
  SELECT
    v.id_versement,
   
    
    v.montant,
    v.mode_paiement,
    v.note,
    v.date_versement,
    v.created_at,
  
    p.nom_patron  AS nom_patron,
    p.prenom_patron AS prenom_patron,
    c.nom  AS nom_chauffeur,
    c.prenom AS prenom_chauffeur,
    v.image_versement,
    c.image_Fchauffeur AS image_Fchauffeur,
    p.image_Patron AS image_Patron
  FROM versement v
 
  LEFT JOIN chauffeur c ON c.id_Fchauffeur = v.id_Fchauffeur
   LEFT JOIN patron p    ON p.id_patron = c.id_patron
  ORDER BY v.date_versement DESC, v.id_versement DESC
";

$res = $connect->query($sql);
$data = [];
if ($res) {
  while ($row = $res->fetch_assoc()) { $data[] = $row; }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
