<?php
header('Content-Type: application/json; charset=utf-8');

try {
  require_once __DIR__ . '/../../config/config.ini.php';
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $connect->set_charset('utf8mb4');

  // === PARAMÈTRES REÇUS ===
  $id_piece = (int)($_POST['id_piece'] ?? 0);
  $statut   = trim($_POST['statut'] ?? '');
  $mouv     = trim($_POST['mouvement_type'] ?? '');
  $quantite = (int)($_POST['quantite'] ?? 0);
  $desc     = trim($_POST['description'] ?? '');
    $id_machine = !empty($_POST['id_machine_statut']) ? (int)$_POST['id_machine_statut'] : null;

  // === VALIDATIONS ===
  if ($id_piece <= 0) throw new Exception("Pièce invalide.");
  if (!in_array($statut, ['en_marche', 'en_panne', 'en_maintenance'], true)) throw new Exception("Statut invalide.");
  if (!in_array($mouv, ['entree', 'sortie'], true)) throw new Exception("Type de mouvement invalide.");
  if ($quantite <= 0) throw new Exception("Quantité doit être >= 1.");
  if (in_array($statut, ['en_panne', 'en_maintenance'], true) && $desc === '') throw new Exception("La description est requise.");

    if ($statut === 'en_marche' && $id_machine === null) {
  
  }

  // === STOCK ET STATUTS ACTUELS ===
  $sql = "SELECT COALESCE(SUM(quantite_piece),0) AS q_total FROM stock_piece WHERE id_piece = ?";
  $st = $connect->prepare($sql);
  $st->bind_param("i", $id_piece);
  $st->execute();
  $q_total = (int)($st->get_result()->fetch_assoc()['q_total'] ?? 0);
  $st->close();

  $q_marche = $q_panne = $q_maint = 0;
  $sql = "SELECT statut, COALESCE(SUM(quantite),0) AS q FROM statut_piece WHERE id_piece = ? GROUP BY statut";
  $st = $connect->prepare($sql);
  $st->bind_param("i", $id_piece);
  $st->execute();
  $res = $st->get_result();
  while ($r = $res->fetch_assoc()) {
    switch ($r['statut']) {
      case 'en_marche':      $q_marche = (int)$r['q']; break;
      case 'en_panne':       $q_panne  = (int)$r['q']; break;
      case 'en_maintenance': $q_maint  = (int)$r['q']; break;
    }
  }
  $st->close();

  $q_dispo = max(0, $q_total);

  // === CONTRÔLES DE QUANTITÉ ===
  if ($mouv === 'sortie') {
    if ($q_dispo < $quantite) throw new Exception("Quantité en stock insuffisante : $q_dispo disponible !");
  } else {
    $q_statut = ($statut === 'en_marche') ? $q_marche : (($statut === 'en_panne') ? $q_panne : $q_maint);
    if ($q_statut < $quantite) throw new Exception("Quantité insuffisante dans ce statut : $q_statut !");
  }

  // === TRANSACTION ===
  $connect->begin_transaction();

  // MISE À JOUR DU STOCK
  $deltaStock = ($mouv === 'sortie') ? -$quantite : +$quantite;
  $sql = "INSERT INTO stock_piece (date_ajout, quantite_piece, id_piece)
          VALUES (NOW(), ?, ?)";
  $st = $connect->prepare($sql);
  $st->bind_param("ii", $deltaStock, $id_piece);
  $st->execute();
  $st->close();

  // MISE À JOUR DU STATUT
  $deltaStatut = ($mouv === 'sortie') ? +$quantite : -$quantite;

    $sql = "INSERT INTO statut_piece (date_ajout, statut, description, id_piece, quantite, id_machine)
            VALUES (NOW(), ?, ?, ?, ?, ?)";
  $st = $connect->prepare($sql);
  $st->bind_param("ssiii", $statut, $desc, $id_piece, $deltaStatut, $id_machine);
  $st->execute();
  $st->close();

  $connect->commit();

  // === NOUVEL ÉTAT ===
  $sql = "SELECT COALESCE(SUM(quantite_piece),0) AS q_total FROM stock_piece WHERE id_piece = ?";
  $st = $connect->prepare($sql);
  $st->bind_param("i", $id_piece);
  $st->execute();
  $q_total = (int)($st->get_result()->fetch_assoc()['q_total'] ?? 0);
  $st->close();

  $q_marche = $q_panne = $q_maint = 0;
  $sql = "SELECT statut, COALESCE(SUM(quantite),0) AS q FROM statut_piece WHERE id_piece = ? GROUP BY statut";
  $st = $connect->prepare($sql);
  $st->bind_param("i", $id_piece);
  $st->execute();
  $res = $st->get_result();
  while ($r = $res->fetch_assoc()) {
    switch ($r['statut']) {
      case 'en_marche':      $q_marche = (int)$r['q']; break;
      case 'en_panne':       $q_panne  = (int)$r['q']; break;
      case 'en_maintenance': $q_maint  = (int)$r['q']; break;
    }
  }
  $st->close();

  echo json_encode([
    'status'  => 'success',
    'message' => "L’opération a été enregistrée avec succès.",
    'payload' => [
      'q_total'  => $q_total,
      'q_marche' => $q_marche,
      'q_panne'  => $q_panne,
      'q_maint'  => $q_maint,
    ],
  ]);
  exit;

} catch (Exception $e) {
  if (isset($connect) && $connect instanceof mysqli) {
    try { $connect->rollback(); } catch (\Throwable $ignore) {}
  }
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  exit;
}
