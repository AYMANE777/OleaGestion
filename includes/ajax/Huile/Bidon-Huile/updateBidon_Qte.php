<?php
// updateBidon_Qte.php — modifier la quantité d’un bidon dans un bon d’huile
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors','0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_error_handler(function($sev,$msg,$file,$line){ throw new ErrorException($msg,0,$sev,$file,$line); });

require_once '../../../../config/config.ini.php';

try {
  $id_detail = (int)($_POST['id_detail'] ?? 0);
  $new_qte   = (float)($_POST['quantite_bidon'] ?? 0);

  if ($id_detail <= 0) throw new Exception("ID de détail manquant.");
  if ($new_qte <= 0) throw new Exception("Quantité invalide.");

  // Récupérer l'ancien détail et verrouiller les lignes concernées
  $stmt = $connect->prepare("
    SELECT d.id_bidon, d.quantite_bidon, b.quantite_bidon AS stock_actuel
    FROM details_bon_huile d
    JOIN bidons b ON b.id_bidon = d.id_bidon
    WHERE d.id_detail = ?
    FOR UPDATE
  ");
  $stmt->bind_param('i', $id_detail);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) throw new Exception("Ligne introuvable (id_detail=$id_detail).");
  $old = $res->fetch_assoc();
  $stmt->close();

  $id_bidon   = (int)$old['id_bidon'];
  $old_qte    = (float)$old['quantite_bidon'];
  $stock      = (float)$old['stock_actuel'];

  // Calcul de la différence
  $diff = $new_qte - $old_qte;

  // Vérifier le stock si on augmente la quantité
  if ($diff > 0 && $stock < $diff) {
    throw new Exception("Stock insuffisant pour ce type de bidon (manque " . ($diff - $stock) . ").");
  }

  // Démarrer la transaction
  $connect->begin_transaction();

  // Ajuster le stock dans la table bidons
  $stmt = $connect->prepare("UPDATE bidons SET quantite_bidon = quantite_bidon - ? WHERE id_bidon = ?");
  $stmt->bind_param('di', $diff, $id_bidon);
  $stmt->execute();
  $stmt->close();

  // Mettre à jour la ligne de détail
  $stmt = $connect->prepare("UPDATE details_bon_huile SET quantite_bidon = ? WHERE id_detail = ?");
  $stmt->bind_param('di', $new_qte, $id_detail);
  $stmt->execute();
  $stmt->close();

  $connect->commit();

  echo json_encode([
    'ok' => true,
    'message' => "Quantité du bidon mise à jour avec succès.",
    'id_detail' => $id_detail,
    'nouvelle_qte' => $new_qte
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  if (isset($connect)) @$connect->rollback();
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Erreur : '.$e->getMessage()]);
}
