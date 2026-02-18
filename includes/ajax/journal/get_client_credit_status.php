<?php
/**
 * Meknassa Olive - get_client_credit_status.php
 * 📊 Retourne la situation globale et détaillée des clients créditeurs :
 *    - Poids total (Kg)
 *    - Prix moyen (DH)
 *    - Montant crédité
 *    - Montant versé
 *    - Solde restant
 *    - Référence bon client (ref_bon_client)
 *    + Totaux globaux
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

try {
  // === PARAMÈTRES ===
  $id_client = (int)($_POST['id_client'] ?? 0);
  $date_debut = trim($_POST['date_debut'] ?? '');
  $date_fin   = trim($_POST['date_fin'] ?? '');

  // === CAS GLOBAL : TOUS LES CLIENTS ===
  if ($id_client <= 0) {
    $where = "1";
    $params = [];
    $types  = "";

    if (!empty($date_debut) && !empty($date_fin)) {
      $where .= " AND DATE(t.date_taratonage) BETWEEN ? AND ?";
      $params = [$date_debut, $date_fin];
      $types  = "ss";
    } elseif (!empty($date_debut)) {
      $where .= " AND DATE(t.date_taratonage) >= ?";
      $params = [$date_debut];
      $types  = "s";
    } elseif (!empty($date_fin)) {
      $where .= " AND DATE(t.date_taratonage) <= ?";
      $params = [$date_fin];
      $types  = "s";
    }

    // 🔹 Calcul global
   $sql = "
SELECT 
  c.id_client_olive,
  CONCAT(c.prenom, ' ', c.nom) AS client_nom,
  GROUP_CONCAT(DISTINCT NULLIF(t.ref_bon_client, '') SEPARATOR ', ') AS ref_bons,
  COALESCE(SUM(t.poid_net), 0) AS total_poids,
  CASE WHEN SUM(t.poid_net) > 0 THEN
    ROUND(SUM(t.montant_total) / SUM(t.poid_net), 2)
  ELSE 0 END AS prix_moyen,
  COALESCE(SUM(t.montant_total), 0) AS total_credit,
  COALESCE((
    SELECT SUM(v.montant)
    FROM versement_olive v
    WHERE v.id_client_olive = c.id_client_olive
  ), 0) AS total_versement
FROM taratonage_client_olive_credit t
JOIN clientolive c ON c.id_client_olive = t.id_client_olive
WHERE $where
  AND (t.ref_bon_client IS NOT NULL AND t.ref_bon_client <> '')
GROUP BY c.id_client_olive
ORDER BY client_nom ASC
";

    $stmt = $connect->prepare($sql);
    if (!empty($types)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $clients = [];
    $total_poids = $tot_credit = $tot_versement = $tot_solde = 0;

    while ($r = $res->fetch_assoc()) {
      $solde = (float)$r['total_credit'] - (float)$r['total_versement'];
      $r['solde'] = $solde;

      $total_poids   += (float)$r['total_poids'];
      $tot_credit    += (float)$r['total_credit'];
      $tot_versement += (float)$r['total_versement'];
      $tot_solde     += $solde;

      $clients[] = $r;
    }

    echo json_encode([
      'ok' => true,
      'type' => 'global',
      'clients' => $clients,
      'totaux' => [
        'poids'      => number_format($total_poids, 2, '.', ' '),
        'credit'     => number_format($tot_credit, 2, '.', ' '),
        'versement'  => number_format($tot_versement, 2, '.', ' '),
        'solde'      => number_format($tot_solde, 2, '.', ' ')
      ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // === CAS CLIENT UNIQUE ===
  $q = $connect->prepare("SELECT * FROM clientolive WHERE id_client_olive=?");
  $q->bind_param("i", $id_client);
  $q->execute();
  $client = $q->get_result()->fetch_assoc();
  if (!$client) throw new Exception("Client introuvable");

  $where = "WHERE id_client_olive=?";
  $params = [$id_client];
  $types = "i";

  if (!empty($date_debut) && !empty($date_fin)) {
    $where .= " AND DATE(date_taratonage) BETWEEN ? AND ?";
    $params[] = $date_debut;
    $params[] = $date_fin;
    $types .= "ss";
  }

  // 🔹 Détails des taratonages
  $t = $connect->prepare("
    SELECT ref_bon_client, poid_net, prix_unitaire, montant_total, note, scanne_bon,
           DATE_FORMAT(date_taratonage, '%d/%m/%Y %H:%i') AS date_taratonage
    FROM taratonage_client_olive_credit
    $where
    ORDER BY date_taratonage DESC
  ");
  $t->bind_param($types, ...$params);
  $t->execute();
  $taratonages = $t->get_result()->fetch_all(MYSQLI_ASSOC);

  // 🔹 Détails des versements
  $v = $connect->prepare("
    SELECT montant, mode_paiement, note,
           DATE_FORMAT(date_versement, '%d/%m/%Y %H:%i') AS date_versement
    FROM versement_olive 
    WHERE id_client_olive=?
    ORDER BY date_versement DESC
  ");
  $v->bind_param("i", $id_client);
  $v->execute();
  $versements = $v->get_result()->fetch_all(MYSQLI_ASSOC);

  // 🔹 Totaux client unique
  $tot_credit = array_sum(array_column($taratonages, 'montant_total'));
  $tot_versement = array_sum(array_column($versements, 'montant'));
  $solde = $tot_credit - $tot_versement;

  echo json_encode([
    'ok' => true,
    'type' => 'single',
    'client' => $client,
    'taratonages' => $taratonages,
    'versements' => $versements,
    'totaux' => [
      'credit' => number_format($tot_credit, 2, '.', ' '),
      'versement' => number_format($tot_versement, 2, '.', ' '),
      'solde' => number_format($solde, 2, '.', ' ')
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
