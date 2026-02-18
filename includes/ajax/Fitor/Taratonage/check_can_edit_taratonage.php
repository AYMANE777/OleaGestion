<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once '../../../../config/config.ini.php';
ini_set('display_errors', '0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Méthode non autorisée']); exit; }

$id = filter_input(INPUT_POST, 'id_taratonage', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'id_taratonage manquant']); exit; }

try {
  // 1) lié à un bon_fitor ?
  $q = $connect->prepare("SELECT COUNT(*) FROM bon_fitor WHERE id_taratonage=?");
  $q->bind_param('i', $id);
  $q->execute();
  $q->bind_result($cnt);
  $q->fetch();
  $q->close();

  if ($cnt > 0) {
    echo json_encode(['ok'=>true, 'editable'=>false, 'reason'=>'Ce taratonage est déjà utilisé dans un bon de fitor.']);
    exit;
  }

  // 2) récupérer la ligne pour pré-remplir
  $sql = "
    SELECT id_taratonage, poid_brut, poid_tarat AS poid_taratone, poid_net, note
    FROM taratonage WHERE id_taratonage = ?
  ";
  $stmt = $connect->prepare($sql);
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();

  if (!$row) { echo json_encode(['ok'=>false, 'error'=>'Taratonage introuvable']); exit; }

  echo json_encode(['ok'=>true, 'editable'=>true, 'data'=>$row], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Erreur serveur']);
}
