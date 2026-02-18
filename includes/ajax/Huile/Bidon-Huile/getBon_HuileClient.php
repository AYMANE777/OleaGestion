<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../../config/config.ini.php';

$id_bon = (int)($_GET['id_bon'] ?? 0);
if ($id_bon <= 0) {
  echo json_encode(['ok'=>false,'error'=>'ID bon invalide']); exit;
}

$stmt = $connect->prepare("
  SELECT 
    d.id_detail,
    b.nom_bidon,
    b.type_bidon,
    d.quantite_bidon,
    b.prix_bidon
  FROM details_bon_huile d
  JOIN bidons b ON b.id_bidon = d.id_bidon
  WHERE d.id_bon = ?
");
$stmt->bind_param('i', $id_bon);
$stmt->execute();
$res = $stmt->get_result();
$details = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
  'ok' => true,
  'details' => $details
], JSON_UNESCAPED_UNICODE);
