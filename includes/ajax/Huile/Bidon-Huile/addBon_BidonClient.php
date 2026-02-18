<?php
// addBon_BidonClient.php (corrigé pour ton schéma)
// Reçoit: client + lignes (type_bidon / quantité) depuis le formulaire "Créer un bon Bidon"
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_error_handler(function ($sev, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $sev, $file, $line); });

require_once '../../../../config/config.ini.php';

try {
  // ---------- Helpers ----------
  function type_to_liters(string $t): float
  {
    // "5L" / "10 L" / "5 l" => 5.0
    if (preg_match('/(\d+(?:\.\d+)?)\s*L/i', $t, $m))
      return (float) $m[1];
    return 0.0;
  }

  // ---------- Lire POST ----------
  $client_id = trim($_POST['List_client_name'] ?? '');
  $note = trim($_POST['note_bon_bidon'] ?? '');      // on l’utilise comme "destination" (faute de champ note)
  $statut_commande = trim($_POST['statut_commande'] ?? ''); // PAYE | NON PAYE | ANNULE (si tu l’ajoutes)
  $mode_paiement = $_POST['mode_paiement'] ?? null; // ESPECES|CARTE|VIREMENT|CHEQUE|AUTRE

  $detail_types = $_POST['detail_type'] ?? [];
  $detail_counts = $_POST['detail_count'] ?? [];

  if (!is_array($detail_types))
    $detail_types = [];
  if (!is_array($detail_counts))
    $detail_counts = [];

  // ---------- Validations ----------
  if ($client_id === '' || !ctype_digit($client_id)) {
    throw new Exception('Client invalide.');
  }
  $client_id_i = (int) $client_id;

  if (count($detail_types) === 0 || count($detail_types) !== count($detail_counts)) {
    throw new Exception('Aucune ligne de bidon valide.');
  }

  // Agréger par type (et ne garder que quantité > 0)
  $lines = []; // type_bidon => quantité demandée
  foreach ($detail_types as $i => $t) {
    $t = strtoupper(trim((string) $t));
    $cnt = (int) ($detail_counts[$i] ?? 0);
    if ($t !== '' && $cnt > 0) {
      $lines[$t] = ($lines[$t] ?? 0) + $cnt;
    }
  }
  if (!$lines) {
    throw new Exception('Lignes invalides (type / quantité).');
  }

  // Vérifier que le client existe
  $stmt = $connect->prepare('SELECT id FROM client WHERE id = ?');
  $stmt->bind_param('i', $client_id_i);
  $stmt->execute();
  $stmt->store_result();
  if ($stmt->num_rows === 0) {
    throw new Exception('Client introuvable.');
  }
  $stmt->close();

  // ---------- Transaction ----------
  $connect->begin_transaction();

  // 1) Vérifier stock par type (et verrouiller)
  foreach ($lines as $type => $need) {
    $stmt = $connect->prepare("
      SELECT COALESCE(SUM(quantite_bidon),0) AS dispo
      FROM bidons
      WHERE UPPER(TRIM(type_bidon)) = UPPER(TRIM(?))
      FOR UPDATE
    ");
    $stmt->bind_param('s', $type);
    $stmt->execute();
    $res = $stmt->get_result();
    $dispo = (int) ($res->fetch_assoc()['dispo'] ?? 0);
    $stmt->close();

    if ($dispo < $need) {
      throw new Exception("Stock insuffisant pour le type '$type' : demandé $need, disponible $dispo.");
    }
  }

  // 2) Créer l’entête du bon (destination = note si fournie)
  $stmt = $connect->prepare("
    INSERT INTO bon_sortie_bidon (id_client, destination, statut, mode_paiement)
    VALUES (?, ?, ?, ?)
  ");
  $dest = ($note !== '') ? $note : null; // ton champ 'destination' est nullable
  // si tu préfères chaîne vide: $dest = $note;
  $stmt->bind_param('isss', $client_id_i, $dest, $statut_commande, $mode_paiement);
  $stmt->execute();
  $bon_id = (int) $connect->insert_id;
  $stmt->close();

  // 3) Allouer par type (FIFO) + insérer lignes de détail + décrémenter stock
  $total_bidons = 0;
  $total_litres = 0.0;
  $total_montant = 0.0;

  // Préparer statements réutilisables
  $stmtSelectRows = $connect->prepare("
    SELECT id_bidon, prix_bidon, quantite_bidon
    FROM bidons
    WHERE UPPER(TRIM(type_bidon)) = UPPER(TRIM(?)) AND quantite_bidon > 0
    ORDER BY id_bidon ASC
    FOR UPDATE
  ");

  $stmtInsertDetail = $connect->prepare("
    INSERT INTO details_bon_bidon (id_bon, id_bidon, quantite, prix_unitaire)
    VALUES (?, ?, ?, ?)
  ");


  foreach ($lines as $type => $need) {
    $left = (int) $need;
    $capL = type_to_liters($type);

    // lignes stock disponibles pour ce type
    $stmtSelectRows->bind_param('s', $type);
    $stmtSelectRows->execute();
    $rsRows = $stmtSelectRows->get_result();
    $rows = [];
    while ($r = $rsRows->fetch_assoc())
      $rows[] = $r;

    foreach ($rows as $r) {
      if ($left <= 0)
        break;

      $id_bidon = (int) $r['id_bidon'];
      $row_qty = (int) $r['quantite_bidon'];
      $row_px = (float) $r['prix_bidon'];

      $take = min($left, $row_qty);
      if ($take <= 0)
        continue;

      // Insérer la ligne détail (snapshot du prix)
      $stmtInsertDetail->bind_param('iiid', $bon_id, $id_bidon, $take, $row_px);
      $stmtInsertDetail->execute();

      // Décrémenter le stock
      

      // Totaux
      $total_bidons += $take;
      $total_litres += $capL * $take;
      $total_montant += $row_px * $take;

      $left -= $take;
    }

    if ($left > 0) {
      throw new Exception("Allocation incomplète pour le type '$type' (reste $left).");
    }
  }

  // Fin OK
  $connect->commit();

  echo json_encode([
    'status' => 'success',
    'message' => 'Bon de sortie créé avec succès.',
    'bon_id' => $bon_id,
    'client_id' => $client_id_i,
     'statut'        => $statut_commande,
    'mode_paiement' => $mode_paiement,
    'total_bidons' => (int) $total_bidons,
    'total_litres' => round($total_litres, 2),
    'total_montant' => round($total_montant, 2),
    'note_used_as_destination' => ($note !== '')
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;

} catch (Throwable $e) {
  if (isset($connect) && $connect instanceof mysqli) {
    try {
      $connect->rollback();
    } catch (Throwable $ignored) {
    }
  }
  http_response_code(400);
  echo json_encode([
    'status' => 'error',
    'message' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
