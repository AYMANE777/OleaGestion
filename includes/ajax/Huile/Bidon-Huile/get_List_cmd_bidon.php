<?php
// ../includes/ajax/Huile/Bidon-Huile/get_List_cmd_bidon.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_error_handler(function ($sev, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $sev, $file, $line);
});

require_once '../../../../config/config.ini.php';

try {
  $q        = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
  $page     = max(1, (int)($_GET['page'] ?? 1));
  $perPage  = (int)($_GET['perPage'] ?? 25);
  $perPage  = in_array($perPage, [10,25,50,100], true) ? $perPage : 25;
  $offset   = ($page - 1) * $perPage;

  // --------- SEARCH (client / bidon / prix / destination / statut / mode) ----------
  $where  = '';
  $types  = '';
  $params = [];

  if ($q !== '') {
    $where = "WHERE (
        c.prenom LIKE ? OR c.nom LIKE ?
        OR bdn.nom_bidon LIKE ? OR bdn.type_bidon LIKE ?
        OR CAST(bdn.prix_bidon AS CHAR) LIKE ?
        OR bon.destination LIKE ? OR bon.statut LIKE ? OR bon.mode_paiement LIKE ?
    )";
    $like   = "%{$q}%";
    $types  = 'ssssssss';
    $params = [$like,$like,$like,$like,$like,$like,$like,$like];
  }

  // --------- COUNT TOTAL BONS (not clients) ----------
  $sqlCount = "
    SELECT COUNT(*) AS cnt FROM (
      SELECT bon.id_bon_sortie_bidon
      FROM bon_sortie_bidon bon
      JOIN client c             ON c.id = bon.id_client
      JOIN details_bon_bidon dbb ON dbb.id_bon = bon.id_bon_sortie_bidon
      JOIN bidons bdn           ON bdn.id_bidon = dbb.id_bidon
      $where
      GROUP BY bon.id_bon_sortie_bidon
    ) t
  ";
  $stmt = $connect->prepare($sqlCount);
  if ($where !== '') $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $total = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
  $stmt->close();

  // --------- PAGE: ONE ROW PER BON WITH TOTALS ----------
  // NOTE:
  // - lignes          = COUNT(*) (number of detail lines)
  // - nb_bidon_types  = COUNT(DISTINCT bdn.id_bidon)
  // - total_quantite  = SUM(dbb.quantite)
  // - total_montant   = SUM(dbb.quantite * dbb.prix_unitaire)
  // - last_created_at = bon.created_at (for display)
  $sqlPage = "
    SELECT
      bon.id_bon_sortie_bidon        AS bon_id,
      c.id                           AS client_id,
      CONCAT(c.prenom,' ',c.nom)     AS client,
      bon.destination,
      bon.statut,
      bon.mode_paiement,
      bon.created_at                 AS last_created_at,
      COUNT(*)                       AS lignes,
      COUNT(DISTINCT bdn.id_bidon)   AS nb_bidon_types,
      COALESCE(SUM(dbb.quantite),0)                          AS total_quantite,
      COALESCE(SUM(dbb.quantite * dbb.prix_unitaire),0)      AS total_montant
    FROM bon_sortie_bidon bon
    JOIN client c             ON c.id = bon.id_client
    JOIN details_bon_bidon dbb ON dbb.id_bon = bon.id_bon_sortie_bidon
    JOIN bidons bdn           ON bdn.id_bidon = dbb.id_bidon
    $where
    GROUP BY bon.id_bon_sortie_bidon, c.id, c.prenom, c.nom, bon.destination, bon.statut, bon.mode_paiement, bon.created_at
    ORDER BY bon.id_bon_sortie_bidon DESC
    LIMIT ? OFFSET ?
  ";
  $stmt = $connect->prepare($sqlPage);
  if ($where !== '') {
    $typesPage  = $types . 'ii';
    $paramsPage = [...$params, $perPage, $offset];
    $stmt->bind_param($typesPage, ...$paramsPage);
  } else {
    $stmt->bind_param('ii', $perPage, $offset);
  }
  $stmt->execute();
  $res = $stmt->get_result();

  $rows = [];
  while ($r = $res->fetch_assoc()) {
    $rows[] = [
      'bon_id'          => (int)$r['bon_id'],
      'client_id'       => (int)$r['client_id'],
      'client'          => (string)$r['client'],
      'destination'     => (string)($r['destination'] ?? ''),
      'statut'          => (string)($r['statut'] ?? ''),
      'mode_paiement'   => (string)($r['mode_paiement'] ?? ''),
      'last_created_at' => $r['last_created_at'],

      'lignes'          => (int)$r['lignes'],          // number of detail lines
      'nb_bidon_types'  => (int)$r['nb_bidon_types'],  // distinct types
      'total_quantite'  => (float)$r['total_quantite'],
      'total_montant'   => (float)$r['total_montant'],
    ];
  }
  $stmt->close();

  if (ob_get_length()) { ob_clean(); }
  echo json_encode([
    'ok'         => true,
    'items'      => $rows,
    'page'       => $page,
    'perPage'    => $perPage,
    'total'      => $total,
    'totalPages' => (int)ceil($total / max(1,$perPage)),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;

} catch (Throwable $e) {
  if (ob_get_length()) { ob_clean(); }
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error: '.$e->getMessage()]);
  exit;
}
