<?php
// create_bon_sortie_bulk.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors','0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_error_handler(function($s,$m,$f,$l){ throw new ErrorException($m,0,$s,$f,$l); });

require_once '../../../../config/config.ini.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Méthode non autorisée']); exit;
  }

  $clientId = (int)($_POST['client_id'] ?? 0);
  if ($clientId <= 0) {
    echo json_encode(['ok'=>false,'error'=>'client_id manquant ou invalide']); exit;
  }

  $dest = trim((string)($_POST['destination'] ?? ''));

  // expects: id_details[]=..&id_details[]=..
  $ids = $_POST['id_details'] ?? [];
  if (!is_array($ids) || empty($ids)) {
    echo json_encode(['ok'=>false,'error'=>'Aucune ligne sélectionnée']); exit;
  }

  // normaliser / dédupliquer
  $ids = array_values(array_unique(array_map('intval', $ids)));
  $ids = array_filter($ids, fn($n) => $n > 0);
  if (!$ids) {
    echo json_encode(['ok'=>false,'error'=>'Aucune ligne valide']); exit;
  }

  mysqli_begin_transaction($connect);

  // Vérifier que ces détails appartiennent à des bidons existants
  // et qu'ils ne sont pas déjà rattachés (id_bon IS NULL).
  $in = implode(',', $ids);
  $sqlCheck = "
    SELECT dbb.id_detail, dbb.id_bon
    FROM Details_bon_bidon dbb
    WHERE dbb.id_detail IN ($in)
    FOR UPDATE
  ";
  $rs = mysqli_query($connect, $sqlCheck);

  $attachable = [];
  while ($r = mysqli_fetch_assoc($rs)) {
    if (is_null($r['id_bon'])) {
      $attachable[] = (int)$r['id_detail'];
    }
  }

  if (!$attachable) {
    mysqli_rollback($connect);
    echo json_encode(['ok'=>false,'error'=>'Toutes les lignes sont déjà rattachées à un bon']); exit;
  }

  // Créer l’entête du bon
  $stmtBon = $connect->prepare("
    INSERT INTO Bon_Sortie_Bidon (id_client, destination, statut)
    VALUES (?, ?, 'ACTIVE')
  ");
  $stmtBon->bind_param('is', $clientId, $dest);
  $stmtBon->execute();
  $bonId = (int)$connect->insert_id;

  // Rattacher les lignes au bon
  $in2 = implode(',', $attachable);
  $sqlUpd = "
    UPDATE Details_bon_bidon
    SET id_bon = $bonId
    WHERE id_detail IN ($in2) AND id_bon IS NULL
  ";
  mysqli_query($connect, $sqlUpd);

  mysqli_commit($connect);

  echo json_encode([
    'ok'      => true,
    'bon_id'  => $bonId,
    'attached'=> count($attachable)
  ]);
  exit;

} catch (Throwable $e) {
  if (mysqli_errno($connect)) mysqli_rollback($connect);
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error: '.$e->getMessage()]);
}
