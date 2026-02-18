<?php
header('Content-Type: application/json; charset=utf-8');

try {
require_once '../../../config/config.ini.php'; // adjust if needed
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

  $where  = ['1'];
  $types  = '';
  $params = [];

  if ($q !== '') {
    $where[] = '(o.prenom LIKE ? OR o.nom LIKE ? OR CONCAT(o.prenom, " ", o.nom) LIKE ? OR o.cin LIKE ? OR o.tele LIKE ? OR o.fonction LIKE ? OR o.adress LIKE ?)';
    $like = "%{$q}%";
    $params = [$like, $like, $like, $like, $like, $like, $like];
    $types = 'sssssss';
  }

  $whereSql = implode(' AND ', $where);

  // COUNT
  $sqlCount = "SELECT COUNT(*) AS total
               FROM ouvrier o
               WHERE $whereSql";
  $stmt = $connect->prepare($sqlCount);
  if (!$stmt) throw new Exception("Erreur préparation count : " . $connect->error);
  if ($types) stmt_bind_params_array($stmt, $types, $params);
  $stmt->execute();
  $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
  $stmt->close();

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
  $to   = $total ? min($offset + ($perRaw === 'all' ? $limit : (int)$limit), $total) : 0;

  // ---------- IMPORTANT : inclure img_cin et image dans le SELECT ----------
  $sqlData = "SELECT
                o.id_ouvrier,
                o.prenom,
                o.nom,
                o.cin,
                o.img_cin,
                o.image,
                o.tele,
                o.fonction,
                o.adress,
                o.date_ajout
              FROM ouvrier o
              WHERE $whereSql
              ORDER BY o.date_ajout DESC, o.id_ouvrier DESC
              LIMIT ? OFFSET ?";

  $stmt = $connect->prepare($sqlData);
  if (!$stmt) throw new Exception("Erreur préparation data : " . $connect->error);

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
      'id_ouvrier' => (int)$r['id_ouvrier'],
      'prenom'     => (string)($r['prenom'] ?? ''),
      'nom'        => (string)($r['nom'] ?? ''),
      'cin'        => (string)($r['cin'] ?? ''),
      'img_cin'    => (string)($r['img_cin'] ?? ''),
      'image'      => (string)($r['image'] ?? ''),
      'tele'       => (string)($r['tele'] ?? ''),
      'fonction'   => (string)($r['fonction'] ?? ''),
      'adress'     => (string)($r['adress'] ?? ''),
      'date_ajout' => $r['date_ajout'],
      'date_fmt'   => $dt,
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
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
  exit;
}
