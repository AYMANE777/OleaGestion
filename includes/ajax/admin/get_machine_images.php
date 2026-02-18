<?php
/**
 * Meknassa Olive - get_machine_images.php
 * Retourne toutes les images du machine + images groupées par pièce associée.
 */

header('Content-Type: application/json; charset=utf-8');

try {
  require_once '../../../config/config.ini.php'; 
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $connect->set_charset('utf8mb4');

  // 🔹 Paramètre machine
  $machine = trim($_POST['machine'] ?? '');
  if ($machine === '') {
    throw new Exception("Aucune machine spécifiée.");
  }

  // 🔹 1️⃣ Images du machine
  $sqlM = "
    SELECT im.image AS url
    FROM image_machine im
    JOIN machine m ON m.id_machine = im.id_machine
    WHERE m.nom_machine = ?
    ORDER BY im.date_ajout ASC, im.id_image ASC
  ";
  $stmtM = $connect->prepare($sqlM);
  $stmtM->bind_param('s', $machine);
  $stmtM->execute();
  $resM = $stmtM->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmtM->close();

  $machineImages = array_map(fn($r) =>  $r['url'], $resM);

  // 🔹 2️⃣ Images des pièces associées (groupées)
  $sqlP = "
    SELECT 
      p.nom_piece,
      ip.image
    FROM image_piece ip
    JOIN piece p ON p.id_piece = ip.id_piece
    JOIN statut_piece sp ON sp.id_piece = p.id_piece
    JOIN machine m ON m.id_machine = sp.id_machine_statut
    WHERE m.nom_machine = ? AND sp.statut = 'en_marche'
    ORDER BY p.nom_piece ASC, ip.id_image ASC
  ";
  $stmtP = $connect->prepare($sqlP);
  $stmtP->bind_param('s', $machine);
  $stmtP->execute();
  $resP = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmtP->close();

  // 🔹 Groupement par pièce
  $pieceImages = [];
  foreach ($resP as $row) {
    $pieceName = $row['nom_piece'];
    if (!isset($pieceImages[$pieceName])) {
      $pieceImages[$pieceName] = [
        'nom_piece' => $pieceName,
        'images' => []
      ];
    }
    $pieceImages[$pieceName]['images'][] =   $row['image'];
  }

  echo json_encode([
    'ok' => true,
    'machine' => $machine,
    'machine_images' => $machineImages,
    'piece_images' => array_values($pieceImages),
    'count_machine' => count($machineImages),
    'count_piece' => count($pieceImages)
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
  exit;
}
