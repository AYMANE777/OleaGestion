<?php
header('Content-Type: application/json; charset=utf-8');

try {
  require_once __DIR__ . '/../../config/config.ini.php';
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $connect->set_charset('utf8mb4');
  
  function stmt_bind_params_array(mysqli_stmt $stmt, string $types, array $params): void {
    $bind = array_merge([$types], $params);
    $refs = [];
    foreach ($bind as $k => $v) { $refs[$k] = &$bind[$k]; }
    call_user_func_array([$stmt, 'bind_param'], $refs);
  }

  $q       = trim((string)($_POST['q'] ?? ''));
  $page    = max(1, (int)($_POST['page'] ?? 1));
  $perRaw  = strtolower(trim((string)($_POST['per_page'] ?? '10')));
  $allowed = ['10','25','50','100','all'];
  if (!in_array($perRaw, $allowed, true)) $perRaw = '10';

  $where  = ["sm.description IS NOT NULL", "TRIM(sm.description) <> ''"];
  $types  = '';
  $params = [];

  if ($q !== '') {
    $where[] = "(m.ref_machine LIKE ? OR m.nom_machine LIKE ? OR sm.statut LIKE ? OR sm.description LIKE ?)";
    $like    = "%{$q}%";
    $params  = [$like, $like, $like, $like];
    $types   = 'ssss';
  }
  $whereSql = implode(' AND ', $where);

  // Total
  $sqlCount = "SELECT COUNT(*) AS total
               FROM statut_machine sm
               INNER JOIN machine m ON m.id_machine = sm.id_machine
               WHERE $whereSql";

  $stmt = $connect->prepare($sqlCount);
  if ($types) stmt_bind_params_array($stmt, $types, $params);
  $stmt->execute();
  $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
  $stmt->close();

  // Pagination
  if ($perRaw === 'all') {
    $limit    = max(1, $total);
    $offset   = 0;
    $lastPage = 1;
    $page     = 1;
  } else {
    $perPage  = (int)$perRaw;
    $limit    = $perPage;
    $lastPage = max(1, (int)ceil($total / $perPage));
    if ($page > $lastPage) $page = $lastPage;
    $offset   = ($page - 1) * $perPage;
  }

  $from = $total ? ($offset + 1) : 0;
  $to   = $total ? min($offset + ($perRaw === 'all' ? $limit : (int)$perRaw), $total) : 0;

  $sqlData = "SELECT
                sm.id_statut,
                sm.date_ajout,
                sm.statut,
                sm.description,
                m.ref_machine,
                m.nom_machine
              FROM statut_machine sm
              INNER JOIN machine m ON m.id_machine = sm.id_machine
              WHERE $whereSql
              ORDER BY sm.date_ajout DESC, sm.id_statut DESC
              LIMIT ? OFFSET ?";
  $stmt = $connect->prepare($sqlData);
  if ($types) {
    $types2  = $types . 'ii';
    $params2 = array_merge($params, [$limit, $offset]);
    stmt_bind_params_array($stmt, $types2, $params2);
  } else {
    stmt_bind_params_array($stmt, 'ii', [$limit, $offset]);
  }
  $stmt->execute();
  $res = $stmt->get_result();

  $rows = [];
  while ($r = $res->fetch_assoc()) {
    $dt = $r['date_ajout'] ? date('d/m/Y H:i', strtotime($r['date_ajout'])) : '';
    $rows[] = [
      'id_statut'   => (int)$r['id_statut'],
      'date_ajout'  => $r['date_ajout'],
      'date_fmt'    => $dt,
      'statut'      => (string)($r['statut'] ?? ''),
      'description' => (string)($r['description'] ?? ''),
      'ref_machine' => (string)($r['ref_machine'] ?? ''),
      'nom_machine' => (string)($r['nom_machine'] ?? ''),
    ];
  }
  $stmt->close();

  echo json_encode([
    'ok'        => true,
    'total'     => $total,
    'from'      => $from,
    'to'        => $to,
    'page'      => $page,
    'per_page'  => $perRaw,
    'last_page' => $lastPage,
    'rows'      => $rows,
  ]);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
  exit;
}