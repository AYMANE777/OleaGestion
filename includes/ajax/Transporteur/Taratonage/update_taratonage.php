<?php

header('Content-Type: application/json; charset=utf-8');
require_once '../../../../config/config.ini.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Méthode non autorisée']); exit; }

$id   = filter_input(INPUT_POST, 'id_taratonage_trans', FILTER_VALIDATE_INT);
$brut = filter_input(INPUT_POST, 'poid_brut_trans', FILTER_VALIDATE_FLOAT);
$tar  = filter_input(INPUT_POST, 'poid_taratone_trans', FILTER_VALIDATE_FLOAT);
$note = isset($_POST['note_trans']) ? trim($_POST['note_trans']) : null;

if (!$id || $brut === false || $tar === false) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Champs invalides']); exit; }

$net = $brut - $tar; if ($net < 0) $net = 0;

try {


  $sql = "UPDATE taratonage_olive SET poid_brut=?, poid_tarat=?, poid_net=?, note=? WHERE id_taratonage=?";
  $stmt = $connect->prepare($sql);
  $stmt->bind_param('dddsi', $brut, $tar, $net, $note, $id);
  $ok = $stmt->execute();
  $stmt->close();

  if (!$ok) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Échec de mise à jour']); exit; }

  echo json_encode(['ok'=>true, 'data'=>[
    'id_taratonage'=>$id,
    'poid_brut'=>$brut, 'poid_taratone'=>$tar, 'poid_net'=>$net, 'note'=>$note
  ]]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Erreur serveur']);
}
