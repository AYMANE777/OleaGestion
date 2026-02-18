<?php
// ../includes/ajax/Huile/Bidon-Huile/get_List_bons_bidon_enregistrement.php
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

  // Recherche sur client / bidon
  $where  = '';
  $types  = '';
  $params = [];

  if ($q !== '') {
    $where = "WHERE (
      c.prenom LIKE ? OR c.nom LIKE ?
      OR bdn.nom_bidon LIKE ? OR bdn.type_bidon LIKE ?
      OR CAST(bdn.prix_bidon AS CHAR) LIKE ?
    )";
    $like   = "%{$q}%";
    $types  = 'sssss';
    $params = [$like,$like,$like,$like,$like];
  }

  // TOTAL : nb de clients qui ont au moins 1 bon avec détails
  $sqlCount = "
    SELECT COUNT(*) AS cnt FROM (
      SELECT c.id
      FROM bon_sortie_bidon bon
      JOIN client c        ON c.id = bon.id_client
      JOIN details_bon_bidon dbb ON dbb.id_bon = bon.id_bon_sortie_bidon
      JOIN bidons bdn      ON bdn.id_bidon = dbb.id_bidon
      $where
      GROUP BY c.id
    ) t
  ";
  $stmt = $connect->prepare($sqlCount);
  if ($where !== '') $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $total = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
  $stmt->close();

  // PAGE : agrégé par client
  $sqlPage = "
    SELECT *
    FROM (
      SELECT
        c.id                           AS client_id,
        CONCAT(c.prenom,' ',c.nom)     AS client,
        COUNT(*)                       AS lignes,
        COUNT(DISTINCT bdn.id_bidon)   AS nb_bidon_types,
        COALESCE(SUM(dbb.quantite),0)  AS total_quantite,
        COALESCE(SUM(dbb.quantite * dbb.prix_unitaire),0) AS total_montant,
        MAX(bon.created_at)            AS last_created_at
      FROM bon_sortie_bidon bon
      JOIN client c           ON c.id = bon.id_client
      JOIN details_bon_bidon dbb ON dbb.id_bon = bon.id_bon_sortie_bidon
      JOIN bidons bdn         ON bdn.id_bidon = dbb.id_bidon
      $where
      GROUP BY c.id, c.prenom, c.nom
    ) t
    ORDER BY (t.last_created_at IS NULL) ASC, t.last_created_at DESC, t.client ASC
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
      'client_id'       => (int)$r['client_id'],
      'client'          => (string)$r['client'],
      'lignes'          => (int)$r['lignes'],
      'nb_bidon_types'  => (int)$r['nb_bidon_types'],
      'total_quantite'  => (float)$r['total_quantite'],
      'total_montant'   => (float)$r['total_montant'],
      'last_created_at' => $r['last_created_at'],
    ];
  }

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
