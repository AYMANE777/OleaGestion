<?php
/**
 * Meknassa Olive - get_taratonage_credit_status.php
 * 🔹 Retourne le détail des taratonages par BON (ref_bon_client)
 *    - Un enregistrement par bon
 *    - Avec : client, ref_bon, poids, prix, montant, note
 *    - Filtrage par date uniquement
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.ini.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect->set_charset('utf8mb4');

try {
  // === PARAMÈTRES ===
  $date_debut = trim($_POST['date_debut'] ?? '');
  $date_fin   = trim($_POST['date_fin'] ?? '');

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

  // === Requête principale : détail par BON ===
  $sql = "
    SELECT 
      c.id_client_olive,
      CONCAT(c.prenom, ' ', c.nom) AS client_nom,
      t.ref_bon_client,
      COALESCE(SUM(t.poid_net), 0) AS total_poids,
      CASE WHEN SUM(t.poid_net) > 0 THEN
        ROUND(SUM(t.montant_total) / SUM(t.poid_net), 2)
      ELSE 0 END AS prix_moyen,
      COALESCE(SUM(t.montant_total), 0) AS total_credit,
      GROUP_CONCAT(DISTINCT NULLIF(t.note, '') SEPARATOR ' | ') AS notes
    FROM taratonage_client_olive_credit t
    JOIN clientolive c ON c.id_client_olive = t.id_client_olive
    WHERE $where
      AND (t.ref_bon_client IS NOT NULL AND t.ref_bon_client <> '')
    GROUP BY c.id_client_olive, t.ref_bon_client
    ORDER BY  t.ref_bon_client ASC
  ";

  $stmt = $connect->prepare($sql);
  if (!empty($types)) $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();

  $bons = [];
  $total_poids = $total_credit = 0;

  while ($r = $res->fetch_assoc()) {
    $total_poids += (float)$r['total_poids'];
    $total_credit += (float)$r['total_credit'];
    $bons[] = $r;
  }

  echo json_encode([
    'ok' => true,
    'type' => 'par_bon',
    'bons' => $bons,
    'totaux' => [
      'poids'  => number_format($total_poids, 2, '.', ' '),
      'credit' => number_format($total_credit, 2, '.', ' ')
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
