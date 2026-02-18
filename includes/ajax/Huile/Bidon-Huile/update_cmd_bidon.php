<?php
// ───────────────────────────────
// update_cmd_bidon.php (with stock sync)
// ───────────────────────────────
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once '../../../../config/config.ini.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

try {
  $connect->begin_transaction();

  // ── INPUT
  $id_bon   = (int)($_POST['id_bon'] ?? 0);
  $statut   = trim($_POST['statut_commande'] ?? '');
  $mode     = trim($_POST['mode_paiement'] ?? '');
  $note     = trim($_POST['note'] ?? '');
  $details  = json_decode($_POST['details'] ?? '[]', true);

  if ($id_bon <= 0) throw new Exception('Bon ID invalide.');
  if (!in_array($statut, ['PAYE','NON PAYE'], true)) throw new Exception('Statut commande invalide.');
  if (!is_array($details) || !count($details)) throw new Exception('Aucun détail transmis.');

  // ────────────────────────────────────────────────
  // 1️⃣ Récupérer ancien détail pour ajuster stock
  // ────────────────────────────────────────────────
  $old = [];
  $r = $connect->query("
      SELECT b.type_bidon, d.quantite 
      FROM Details_bon_bidon d 
      JOIN bidons b ON b.id_bidon = d.id_bidon 
      WHERE d.id_bon = {$id_bon}
  ");
  while ($row = $r->fetch_assoc()) {
    $old[$row['type_bidon']] = ($old[$row['type_bidon']] ?? 0) + (float)$row['quantite'];
  }

  // ────────────────────────────────────────────────
  // 2️⃣ Mettre à jour en-tête du bon
  // ────────────────────────────────────────────────
  $stmt = $connect->prepare("
    UPDATE Bon_Sortie_Bidon 
    SET statut_commande = ?, mode_paiement = ?, note = ?
    WHERE id_bon_sortie_bidon = ?
  ");
  $stmt->bind_param('sssi', $statut, $mode, $note, $id_bon);
  $stmt->execute();
  $stmt->close();

  // ────────────────────────────────────────────────
  // 3️⃣ Supprimer anciens détails
  // ────────────────────────────────────────────────
  $stmt = $connect->prepare("DELETE FROM Details_bon_bidon WHERE id_bon = ?");
  $stmt->bind_param('i', $id_bon);
  $stmt->execute();
  $stmt->close();

  // ────────────────────────────────────────────────
  // 4️⃣ Insérer nouveaux détails
  // ────────────────────────────────────────────────
  $stmt = $connect->prepare("
    INSERT INTO Details_bon_bidon (id_bon, id_bidon, quantite, prix_unitaire, created_at)
    VALUES (?, (SELECT id_bidon FROM bidons WHERE type_bidon = ? LIMIT 1), ?, ?, NOW())
  ");
  $new = [];
  foreach ($details as $d) {
    $type  = trim($d['type_bidon'] ?? '');
    $qte   = (float)($d['quantite_bidon'] ?? 0);
    $prix  = (float)($d['prix_bidon'] ?? 0);
    if ($type === '' || $qte <= 0) continue;
    $stmt->bind_param('isdd', $id_bon, $type, $qte, $prix);
    $stmt->execute();
    $new[$type] = ($new[$type] ?? 0) + $qte;
  }
  $stmt->close();

  // ────────────────────────────────────────────────
  // 5️⃣ Ajustement du stock
  // ────────────────────────────────────────────────
  // Si un bidon a augmenté → on retire du stock
  // Si un bidon a diminué → on ajoute au stock
  $stmtStock = $connect->prepare("UPDATE bidons SET qte_dispo = qte_dispo + ? WHERE type_bidon = ?");
  foreach ($new as $type => $qteNew) {
    $oldQte = $old[$type] ?? 0;
    $delta = $oldQte - $qteNew; // positif = on restitue au stock
    if ($delta != 0) {
      $stmtStock->bind_param('ds', $delta, $type);
      $stmtStock->execute();
    }
  }

  // Si des anciens types ne sont plus dans la commande
  foreach ($old as $type => $qteOld) {
    if (!isset($new[$type])) {
      $stmtStock->bind_param('ds', $qteOld, $type);
      $stmtStock->execute();
    }
  }
  $stmtStock->close();

  // ────────────────────────────────────────────────
  // 6️⃣ Commit + retour
  // ────────────────────────────────────────────────
  $connect->commit();
  echo json_encode(['ok' => true, 'msg' => 'Commande + stock mis à jour']);
} catch (Throwable $e) {
  $connect->rollback();
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
