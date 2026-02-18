<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(0);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once '../../../../config/config.ini.php';

try {
  // --- Inputs
  $id_bidon     = (int)($_POST['id_bidon'] ?? 0);
  $qte_changee  = (float)($_POST['quantite_changee'] ?? 0);
  $commentaire  = trim((string)($_POST['commentaire'] ?? ''));
  $utilisateur  = $_SESSION['username'] ?? 'admin';
  $type_action  = $_POST['type_action'] ?? 'ajout';

  // --- Validation
  if ($id_bidon <= 0) {
    echo json_encode(['success' => false, 'message' => 'Identifiant du bidon invalide']); exit;
  }
  if ($qte_changee <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantité invalide']); exit;
  }
  if (!in_array($type_action, ['ajout', 'retrait', 'correction'], true)) {
    echo json_encode(['success' => false, 'message' => 'Type d’action invalide']); exit;
  }

  // --- Ajustement du signe pour les retraits
  $sign = ($type_action === 'retrait') ? -1 : 1;
  $qte_signed = $sign * $qte_changee;

  // --- Transaction
  $connect->begin_transaction();

  // 1️⃣ Mettre à jour le stock restant
  $stmt = $connect->prepare("UPDATE bidons SET quantite_bidon = quantite_bidon + ? WHERE id_bidon = ?");
  $stmt->bind_param('di', $qte_signed, $id_bidon);
  $stmt->execute();
  $stmt->close();

  // 2️⃣ Historique
  $stmt = $connect->prepare("
    INSERT INTO historique_bidon (id_bidon, type_action, quantite_changee, utilisateur, commentaire)
    VALUES (?, ?, ?, ?, ?)
  ");
  $stmt->bind_param('isdss', $id_bidon, $type_action, $qte_changee, $utilisateur, $commentaire);
  $stmt->execute();
  $stmt->close();

  $connect->commit();

  echo json_encode([
    'success' => true,
    'message' => 'Stock mis à jour avec succès.',
    'type_action' => $type_action,
    'quantite_changee' => $qte_changee
  ]);
  exit;

} catch (Throwable $e) {
  if (isset($connect)) $connect->rollback();
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Erreur serveur : '.$e->getMessage()]);
  exit;
}
