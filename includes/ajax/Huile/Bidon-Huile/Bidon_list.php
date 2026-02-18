<?php
// Bidon_list.php — liste paginée + recherche + agrégats de statut des bidons
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_error_handler(function($sev, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $sev, $file, $line);
});

require_once '../../../../config/config.ini.php';

try {
  // Inputs
  $q       = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
  $page    = max(1, (int)($_GET['page'] ?? 1));
  $perPage = (int)($_GET['perPage'] ?? 25);
  $perPage = in_array($perPage, [10,25,50,100], true) ? $perPage : 25;
  $offset  = ($page - 1) * $perPage;

  // WHERE dynamique
  $where  = '';
  $types  = '';
  $params = [];
  if ($q !== '') {
    $where = "WHERE (b.nom_bidon LIKE ? OR b.type_bidon LIKE ? OR CAST(b.prix_bidon AS CHAR) LIKE ?)";
    $like  = "%{$q}%";
    $types = 'sss';
    $params = [$like, $like, $like];
  }

  // ----- TOTAL COUNT -----
  $sqlCount = "SELECT COUNT(*) AS cnt FROM bidons b $where";
  $stmt = $connect->prepare($sqlCount);
  if ($where !== '') {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $total = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
  $stmt->close();

  // ----- PAGE DATA -----
  $sql = "
    SELECT
      b.id_bidon,
      b.nom_bidon,
      b.type_bidon,
      b.prix_bidon,
      b.quantite_bidon AS stock_initial,
      COALESCE(dh.vendu_huile, 0) AS vendu_via_bon_huile,
      COALESCE(db.vendu_bidon, 0) AS vendu_via_bon_bidon,
      (COALESCE(dh.vendu_huile, 0) + COALESCE(db.vendu_bidon, 0)) AS total_vendu_ou_sorti,
      (b.quantite_bidon - COALESCE(dh.vendu_huile, 0) - COALESCE(db.vendu_bidon, 0)) AS stock_restant,
      b.bidon_created_at
    FROM bidons b
    LEFT JOIN (
      SELECT id_bidon, SUM(quantite_bidon) AS vendu_huile
      FROM details_bon_huile
      GROUP BY id_bidon
    ) dh ON dh.id_bidon = b.id_bidon
    LEFT JOIN (
      SELECT id_bidon, SUM(quantite) AS vendu_bidon
      FROM details_bon_bidon
      GROUP BY id_bidon
    ) db ON db.id_bidon = b.id_bidon
    $where
    ORDER BY b.nom_bidon
    LIMIT ? OFFSET ?
  ";

  $stmt = $connect->prepare($sql);
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
      'id_bidon'             => (int)$r['id_bidon'],
      'nom_bidon'            => (string)$r['nom_bidon'],
      'type_bidon'           => (string)$r['type_bidon'],
      'prix_bidon'           => (float)$r['prix_bidon'],
      'stock_initial'        => (float)$r['stock_initial'],
      'vendu_via_bon_huile'  => (float)$r['vendu_via_bon_huile'],
      'vendu_via_bon_bidon'  => (float)$r['vendu_via_bon_bidon'],
      'total_vendu_ou_sorti' => (float)$r['total_vendu_ou_sorti'],
      'stock_restant'        => (float)$r['stock_restant'],
      'bidon_created_at'     => (string)$r['bidon_created_at'],
    ];
  }
  $stmt->close();

  // ----- OUTPUT -----
  if (ob_get_length()) { ob_clean(); }
  echo json_encode([
    'ok'         => true,
    'rows'       => $rows,
    'total'      => $total,
    'page'       => $page,
    'perPage'    => $perPage,
    'totalPages' => (int)ceil($total / max(1, $perPage)),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;

} catch (Throwable $e) {
  if (ob_get_length()) { ob_clean(); }
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
  exit;
}
