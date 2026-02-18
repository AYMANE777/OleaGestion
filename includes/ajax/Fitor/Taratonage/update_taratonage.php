<?php

header('Content-Type: application/json; charset=utf-8');
require_once '../../../../config/config.ini.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Méthode non autorisée']); exit; }

$id   = filter_input(INPUT_POST, 'id_taratonage', FILTER_VALIDATE_INT);
$brut = filter_input(INPUT_POST, 'poid_brut', FILTER_VALIDATE_FLOAT);
$tar  = filter_input(INPUT_POST, 'poid_taratone', FILTER_VALIDATE_FLOAT);
$note = isset($_POST['note']) ? trim($_POST['note']) : null;

if (!$id || $brut === false || $tar === false) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Champs invalides']); exit; }

$net = $brut - $tar; if ($net < 0) $net = 0;

try {
  // sécurité: ne pas permettre update si lié à bon_fitor
  $check = $connect->prepare("SELECT COUNT(*) FROM bon_fitor WHERE id_taratonage=?");
  $check->bind_param('i', $id);
  $check->execute();
  $check->bind_result($cnt);
  $check->fetch();
  $check->close();
  if ($cnt > 0) { echo json_encode(['ok'=>false,'error'=>'Ce taratonage est déjà utilisé dans un bon_fitor.']); exit; }

  $sql = "UPDATE taratonage SET poid_brut=?, poid_tarat=?, poid_net=?, note=? WHERE id_taratonage=?";
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
