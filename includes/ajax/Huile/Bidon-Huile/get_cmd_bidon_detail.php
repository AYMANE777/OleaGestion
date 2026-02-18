<?php
// ───────────────────────────────
// get_cmd_bidon_detail.php
// ───────────────────────────────
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once '../../../../config/config.ini.php';

try {
  $bon_id = (int)($_GET['bon_id'] ?? 0);
  if ($bon_id <= 0) throw new Exception('Identifiant du bon invalide.');

  $stmt = $connect->prepare("
    SELECT 
      bdn.nom_bidon,
      bdn.type_bidon,
      dbb.quantite AS quantite_bidon,
      dbb.prix_unitaire AS prix_bidon,
      (dbb.quantite * dbb.prix_unitaire) AS total_ligne
    FROM details_bon_bidon dbb
    JOIN bidons bdn ON bdn.id_bidon = dbb.id_bidon
    WHERE dbb.id_bon = ?
    ORDER BY dbb.id_detail ASC
  ");
  $stmt->bind_param('i', $bon_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $items = $res->fetch_all(MYSQLI_ASSOC);

  echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
